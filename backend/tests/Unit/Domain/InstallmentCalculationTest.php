<?php

namespace Tests\Unit\Domain;

use Tests\TestCase;

class InstallmentCalculationTest extends TestCase
{
    public function test_installment_amount_calculation(): void
    {
        $totalAmount = 1000.00;
        $count = 10;
        $baseAmount = floor(($totalAmount * 100) / $count) / 100;
        $remainder = round($totalAmount - ($baseAmount * $count), 2);

        $this->assertEquals(100.00, $baseAmount);
        $this->assertEquals(0.00, $remainder);
    }

    public function test_installment_amount_calculation_with_remainder(): void
    {
        $totalAmount = 100.00;
        $count = 3;
        $baseAmount = floor(($totalAmount * 100) / $count) / 100;
        $remainder = round($totalAmount - ($baseAmount * $count), 2);

        $this->assertEquals(33.33, $baseAmount);
        $this->assertEquals(0.01, $remainder);
    }

    public function test_installment_amount_calculation_exact_division(): void
    {
        $totalAmount = 300.00;
        $count = 3;
        $baseAmount = floor(($totalAmount * 100) / $count) / 100;
        $remainder = round($totalAmount - ($baseAmount * $count), 2);

        $this->assertEquals(100.00, $baseAmount);
        $this->assertEquals(0.00, $remainder);
    }

    public function test_installment_date_calculation(): void
    {
        $firstDate = \Carbon\Carbon::create(2026, 1, 15);
        $count = 3;

        $dates = [];
        for ($i = 1; $i <= $count; $i++) {
            $dates[] = $firstDate->copy()->addMonths($i - 1)->format('Y-m-d');
        }

        $this->assertEquals(['2026-01-15', '2026-02-15', '2026-03-15'], $dates);
    }

    public function test_installment_total_matches_original(): void
    {
        $totalAmount = 100.00;
        $count = 3;
        $baseAmount = floor(($totalAmount * 100) / $count) / 100;
        $remainder = round($totalAmount - ($baseAmount * $count), 2);

        $sum = 0;
        for ($i = 1; $i <= $count; $i++) {
            $amount = $baseAmount;
            if ($i === $count) {
                $amount += $remainder;
            }
            $sum += $amount;
        }

        $this->assertEquals($totalAmount, round($sum, 2));
    }
}