<?php

namespace App\Models;

use Database\Factories\HolidayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function newFactory(): HolidayFactory
    {
        return HolidayFactory::new();
    }
}