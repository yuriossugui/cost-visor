<?php

namespace Tests\Unit\Domain;

use Tests\TestCase;
use App\Models\Transaction;

class TransactionValidationTest extends TestCase
{
    public function test_valid_transaction_data(): void
    {
        $transaction = new Transaction([
            'amount' => 100.50,
            'description' => 'Test transaction',
            'date' => '2026-01-15',
            'type' => 'DESPESA',
        ]);

        $this->assertEquals(100.50, $transaction->amount);
        $this->assertEquals('Test transaction', $transaction->description);
        $this->assertEquals('DESPESA', $transaction->type);
    }

    public function test_amount_must_be_positive(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Transaction::create([
            'user_id' => 1,
            'category_id' => 1,
            'amount' => -10,
            'description' => 'Test',
            'date' => '2026-01-15',
            'type' => 'DESPESA',
        ]);
    }

    public function test_transaction_type_validation(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Transaction::create([
            'user_id' => 1,
            'category_id' => 1,
            'amount' => 100,
            'description' => 'Test',
            'date' => '2026-01-15',
            'type' => 'INVALID',
        ]);
    }
}