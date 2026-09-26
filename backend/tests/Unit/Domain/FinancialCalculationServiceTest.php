<?php

namespace Tests\Unit\Domain;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialCalculationService;
use Carbon\Carbon;

class FinancialCalculationServiceTest extends TestCase
{
    protected FinancialCalculationService $service;
    protected User $user;
    protected Category $categoryReceita;
    protected Category $categoryDespesa1;
    protected Category $categoryDespesa2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FinancialCalculationService();

        $this->user = User::factory()->create();

        $this->categoryReceita = Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Salary',
            'type' => 'RECEITA',
        ]);

        $this->categoryDespesa1 = Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Food',
            'type' => 'DESPESA',
        ]);

        $this->categoryDespesa2 = Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Transport',
            'type' => 'DESPESA',
        ]);
    }

    public function test_calculate_balance(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 5000.00,
            'type' => 'RECEITA',
            'date' => '2026-01-15',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa1->id,
            'amount' => 1500.00,
            'type' => 'DESPESA',
            'date' => '2026-01-20',
        ]);

        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 1, 31);

        $balance = $this->service->calculateBalance($this->user->id, $startDate, $endDate);

        $this->assertEquals(3500.00, $balance);
    }

    public function test_calculate_total_receitas(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 3000.00,
            'type' => 'RECEITA',
            'date' => '2026-01-10',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 2000.00,
            'type' => 'RECEITA',
            'date' => '2026-01-20',
        ]);

        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 1, 31);

        $total = $this->service->calculateTotalReceitas($this->user->id, $startDate, $endDate);

        $this->assertEquals(5000.00, $total);
    }

    public function test_calculate_total_despesas(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa1->id,
            'amount' => 500.00,
            'type' => 'DESPESA',
            'date' => '2026-01-10',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa2->id,
            'amount' => 300.00,
            'type' => 'DESPESA',
            'date' => '2026-01-20',
        ]);

        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 1, 31);

        $total = $this->service->calculateTotalDespesas($this->user->id, $startDate, $endDate);

        $this->assertEquals(800.00, $total);
    }

    public function test_calculate_transaction_count(): void
    {
        Transaction::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'date' => '2026-01-15',
        ]);

        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 1, 31);

        $count = $this->service->calculateTransactionCount($this->user->id, $startDate, $endDate);

        $this->assertEquals(5, $count);
    }

    public function test_calculate_expense_concentration_by_category(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa1->id,
            'amount' => 600.00,
            'type' => 'DESPESA',
            'date' => '2026-01-10',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa2->id,
            'amount' => 400.00,
            'type' => 'DESPESA',
            'date' => '2026-01-20',
        ]);

        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 1, 31);

        $concentration = $this->service->calculateExpenseConcentrationByCategory($this->user->id, $startDate, $endDate);

        $this->assertCount(2, $concentration);

        // Total despesas = 1000
        // Food = 600 (60%)
        // Transport = 400 (40%)
        $food = collect($concentration)->firstWhere('category_name', 'Food');
        $transport = collect($concentration)->firstWhere('category_name', 'Transport');

        $this->assertEquals(60.00, $food['percentage']);
        $this->assertEquals(40.00, $transport['percentage']);
    }

    public function test_calculate_expense_concentration_zero_despesas(): void
    {
        // No expenses, only receitas
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 5000.00,
            'type' => 'RECEITA',
            'date' => '2026-01-15',
        ]);

        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 1, 31);

        $concentration = $this->service->calculateExpenseConcentrationByCategory($this->user->id, $startDate, $endDate);

        $this->assertEmpty($concentration);
    }

    public function test_get_dashboard_data(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 5000.00,
            'type' => 'RECEITA',
            'date' => '2026-01-15',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa1->id,
            'amount' => 1500.00,
            'type' => 'DESPESA',
            'date' => '2026-01-20',
        ]);

        $dashboard = $this->service->getDashboardData($this->user->id, 2026, 1);

        $this->assertEquals(3500.00, $dashboard['balance']);
        $this->assertEquals(5000.00, $dashboard['total_receitas']);
        $this->assertEquals(1500.00, $dashboard['total_despesas']);
        $this->assertEquals(2, $dashboard['transaction_count']);
        $this->assertCount(1, $dashboard['expense_concentration']);
    }

    public function test_user_isolation(): void
    {
        $otherUser = User::factory()->create();

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 5000.00,
            'type' => 'RECEITA',
            'date' => '2026-01-15',
        ]);

        Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => Category::factory()->create(['user_id' => $otherUser->id, 'type' => 'RECEITA'])->id,
            'amount' => 10000.00,
            'type' => 'RECEITA',
            'date' => '2026-01-15',
        ]);

        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 1, 31);

        $balance = $this->service->calculateBalance($this->user->id, $startDate, $endDate);

        $this->assertEquals(5000.00, $balance);
    }
}