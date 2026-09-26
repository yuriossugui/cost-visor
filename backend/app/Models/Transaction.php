<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'installment_id',
        'installment_number',
        'amount',
        'description',
        'date',
        'type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'type' => 'string',
        'installment_number' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}