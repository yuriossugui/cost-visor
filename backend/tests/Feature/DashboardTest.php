<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected User $user;
    protected Category $categoryReceita;
    protected Category $categoryDespesa1;
    protected Category $categoryDespesa2;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
        ]);

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

        $this->token = $this->getToken();
    }

    protected function getToken(): string
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        return $response->json('access_token');
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_dashboard_defaults_to_current_month(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 5000.00,
            'type' => 'RECEITA',
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period' => ['year', 'month'],
                'indicators' => ['balance', 'total_receitas', 'total_despesas', 'transaction_count'],
                'expense_concentration',
            ]);
    }

    public function test_dashboard_with_specific_month(): void
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

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard?year=2026&month=1');

        $response->assertStatus(200)
            ->assertJsonPath('period.year', 2026)
            ->assertJsonPath('period.month', 1)
            ->assertJsonPath('indicators.balance', 3500)
            ->assertJsonPath('indicators.total_receitas', 5000)
            ->assertJsonPath('indicators.total_despesas', 1500)
            ->assertJsonPath('indicators.transaction_count', 2);
    }

    public function test_dashboard_indicators_calculation(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 10000.00,
            'type' => 'RECEITA',
            'date' => '2026-02-01',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa1->id,
            'amount' => 3000.00,
            'type' => 'DESPESA',
            'date' => '2026-02-10',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa2->id,
            'amount' => 2000.00,
            'type' => 'DESPESA',
            'date' => '2026-02-20',
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard?year=2026&month=2');

        $response->assertStatus(200)
            ->assertJsonPath('indicators.balance', 5000) // 10000 - 5000
            ->assertJsonPath('indicators.total_receitas', 10000)
            ->assertJsonPath('indicators.total_despesas', 5000)
            ->assertJsonPath('indicators.transaction_count', 3);
    }

    public function test_dashboard_expense_concentration(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa1->id,
            'amount' => 600.00,
            'type' => 'DESPESA',
            'date' => '2026-03-10',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryDespesa2->id,
            'amount' => 400.00,
            'type' => 'DESPESA',
            'date' => '2026-03-20',
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard?year=2026&month=3');

        $response->assertStatus(200);

        $concentration = $response->json('expense_concentration');
        $this->assertCount(2, $concentration);

        // Total despesas = 1000
        // Food = 600 (60%)
        // Transport = 400 (40%)
        $food = collect($concentration)->firstWhere('category_name', 'Food');
        $transport = collect($concentration)->firstWhere('category_name', 'Transport');

        $this->assertEquals(60.00, $food['percentage']);
        $this->assertEquals(40.00, $transport['percentage']);
    }

    public function test_dashboard_zero_despesas_empty_concentration(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 5000.00,
            'type' => 'RECEITA',
            'date' => '2026-04-15',
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard?year=2026&month=4');

        $response->assertStatus(200)
            ->assertJsonPath('indicators.total_despesas', 0)
            ->assertJsonPath('expense_concentration', []);
    }

    public function test_dashboard_transactions_pagination(): void
    {
        Transaction::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'date' => '2026-05-15',
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard/transactions?year=2026&month=5&per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period',
                'transactions',
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(10, $response->json('transactions'));
        $this->assertEquals(25, $response->json('pagination.total'));
        $this->assertEquals(3, $response->json('pagination.last_page'));
    }

    public function test_dashboard_transactions_defaults_to_current_month(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period',
                'transactions',
                'pagination',
            ]);
    }

    public function test_dashboard_invalid_month_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard?year=2026&month=13');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Invalid month. Must be between 1 and 12.');
    }

    public function test_dashboard_invalid_year_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard?year=1999&month=1');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Invalid year.');
    }

    public function test_dashboard_user_isolation(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => 'RECEITA']);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->categoryReceita->id,
            'amount' => 5000.00,
            'type' => 'RECEITA',
            'date' => '2026-06-15',
        ]);

        Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'amount' => 10000.00,
            'type' => 'RECEITA',
            'date' => '2026-06-15',
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/dashboard?year=2026&month=6');

        $response->assertStatus(200)
            ->assertJsonPath('indicators.total_receitas', 5000);
    }

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_dashboard_transactions(): void
    {
        $response = $this->getJson('/api/dashboard/transactions');
        $response->assertStatus(401);
    }
}