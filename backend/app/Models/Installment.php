<?php

namespace App\Models;

use Database\Factories\InstallmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'total_amount',
        'installment_count',
        'first_installment_date',
        'description',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'first_installment_date' => 'date',
        'installment_count' => 'integer',
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
        return $this->hasMany(Transaction::class, 'installment_id');
    }

    protected static function newFactory(): InstallmentFactory
    {
        return InstallmentFactory::new();
    }
}