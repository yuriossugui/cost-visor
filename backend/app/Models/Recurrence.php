<?php

namespace App\Models;

use App\Services\BusinessDayService;
use Carbon\Carbon;
use Database\Factories\RecurrenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Recurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'description',
        'type',
        'frequency',
        'start_date',
        'end_date',
        'is_business_day',
        'business_day_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'type' => 'string',
        'frequency' => 'string',
        'is_business_day' => 'boolean',
        'business_day_number' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'recurrence_id');
    }

    public function generateTransactions(?Carbon $until = null): int
    {
        $until = $until ?? Carbon::now();
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : $until;

        if ($endDate->gt($until)) {
            $endDate = $until;
        }

        if ($this->start_date->gt($endDate)) {
            return 0;
        }

        $lastTransaction = $this->transactions()->latest('date')->first();
        $startFrom = $lastTransaction
            ? Carbon::parse($lastTransaction->date)->addDay()
            : Carbon::parse($this->start_date);

        if ($startFrom->gt($endDate)) {
            return 0;
        }

        $dates = $this->getOccurrenceDates($startFrom, $endDate);

        if (empty($dates)) {
            return 0;
        }

        return DB::transaction(function () use ($dates) {
            $transactions = [];

            foreach ($dates as $date) {
                $transactions[] = [
                    'user_id' => $this->user_id,
                    'category_id' => $this->category_id,
                    'recurrence_id' => $this->id,
                    'amount' => $this->amount,
                    'description' => $this->description,
                    'date' => $date,
                    'type' => $this->type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Transaction::insert($transactions);

            return count($transactions);
        });
    }

    public function getOccurrenceDates(Carbon $start, Carbon $end): array
    {
        // If this is a business day recurrence, use the business day service
        if ($this->is_business_day) {
            return $this->getBusinessDayOccurrenceDates($start, $end);
        }

        $dates = [];
        $current = $start->copy();

        switch ($this->frequency) {
            case 'daily':
                while ($current->lte($end)) {
                    $dates[] = $current->copy();
                    $current->addDay();
                }
                break;

            case 'weekly':
                while ($current->lte($end)) {
                    $dates[] = $current->copy();
                    $current->addWeek();
                }
                break;

            case 'monthly':
                while ($current->lte($end)) {
                    $dates[] = $current->copy();
                    $current->addMonth();
                }
                break;

            case 'yearly':
                while ($current->lte($end)) {
                    $dates[] = $current->copy();
                    $current->addYear();
                }
                break;
        }

        return $dates;
    }

    /**
     * Get occurrence dates for business day recurrences
     * Supports monthly/yearly with N-th business day
     */
    public function getBusinessDayOccurrenceDates(Carbon $start, Carbon $end): array
    {
        $businessDayService = app(BusinessDayService::class);
        $dates = [];

        switch ($this->frequency) {
            case 'daily':
            case 'weekly':
                // For daily/weekly, just skip non-business days
                $dates = $businessDayService->getBusinessDayOccurrenceDates($start, $end, $this->frequency);
                break;

            case 'monthly':
            case 'yearly':
                if ($this->business_day_number) {
                    // N-th business day of the month/year
                    $dates = $this->getNthBusinessDayOccurrences($start, $end, $businessDayService);
                } else {
                    // Just skip non-business days
                    $dates = $businessDayService->getBusinessDayOccurrenceDates($start, $end, $this->frequency);
                }
                break;
        }

        return $dates;
    }

    /**
     * Get occurrences for N-th business day of month/year
     */
    protected function getNthBusinessDayOccurrences(Carbon $start, Carbon $end, BusinessDayService $businessDayService): array
    {
        $dates = [];
        $current = $start->copy()->startOfMonth();

        // If start is after the first day of month, we need to check if we missed the N-th business day
        if ($current->lt($start->copy()->startOfMonth())) {
            $current = $start->copy()->startOfMonth();
        }

        while ($current->lte($end)) {
            // For the first business day (business_day_number = 1), use getFirstBusinessDayOnOrAfter
            // For N-th business day (N > 1), use getNthBusinessDay with n = N-1 from the first business day
            $firstBusinessDay = $businessDayService->getFirstBusinessDayOnOrAfter($current->copy()->startOfMonth());
            
            $nthDay = $firstBusinessDay;
            if ($this->business_day_number > 1) {
                $nthDay = $businessDayService->getNthBusinessDay($firstBusinessDay, $this->business_day_number - 1);
            }

            // Only add if it's within our range
            if ($nthDay->gte($start) && $nthDay->lte($end)) {
                $dates[] = $nthDay;
            }

            // Move to next period
            if ($this->frequency === 'monthly') {
                $current->addMonth();
            } else {
                $current->addYear();
            }
        }

        return $dates;
    }

    protected static function newFactory(): RecurrenceFactory
    {
        return RecurrenceFactory::new();
    }
}