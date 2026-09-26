<?php

namespace Tests\Unit\Domain;

use Tests\TestCase;

class FinancialCalculationTest extends TestCase
{
    public function test_balance_calculation(): void
    {
        $receitas = 1000.00;
        $despesas = 400.00;
        $saldo = $receitas - $despesas;

        $this->assertEquals(600.00, $saldo);
    }

    public function test_expense_percentage_calculation(): void
    {
        $totalDespesas = 500.00;
        $categoriaDespesa = 150.00;

        $percentual = ($categoriaDespesa / $totalDespesas) * 100;

        $this->assertEquals(30.0, $percentual);
    }

    public function test_zero_expenses_handling(): void
    {
        $totalDespesas = 0.00;
        $categoriaDespesa = 0.00;

        $percentual = $totalDespesas > 0 ? ($categoriaDespesa / $totalDespesas) * 100 : 0;

        $this->assertEquals(0, $percentual);
    }
}