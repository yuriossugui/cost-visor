<?php

namespace Tests\Unit\Domain;

use Tests\TestCase;
use App\Models\Recurrence;

class RecurrenceValidationTest extends TestCase
{
    public function test_valid_recurrence_data(): void
    {
        $recurrence = new Recurrence([
            'amount' => 100.00,
            'description' => 'Monthly subscription',
            'type' => 'DESPESA',
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
        ]);

        $this->assertEquals(100.00, $recurrence->amount);
        $this->assertEquals('Monthly subscription', $recurrence->description);
        $this->assertEquals('DESPESA', $recurrence->type);
        $this->assertEquals('monthly', $recurrence->frequency);
    }

    public function test_recurrence_frequency_validation(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Recurrence::create([
            'user_id' => 1,
            'category_id' => 1,
            'amount' => 100,
            'description' => 'Test',
            'type' => 'DESPESA',
            'frequency' => 'invalid',
            'start_date' => '2026-01-01',
        ]);
    }

    public function test_recurrence_type_validation(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Recurrence::create([
            'user_id' => 1,
            'category_id' => 1,
            'amount' => 100,
            'description' => 'Test',
            'type' => 'INVALID',
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
        ]);
    }
}