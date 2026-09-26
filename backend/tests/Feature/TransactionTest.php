<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    protected User $user;
    protected Category $categoryReceita;
    protected Category $categoryDespesa;
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

        $this->categoryDespesa = Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Food',
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

    public function test_create_transaction_receita(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/transactions', [
            'category_id' => $this->categoryReceita->id,
            'amount' => 5000.00,
            'description' => 'Monthly salary',
            'date' => '2026-01-15',
            'type' => 'RECEITA',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('amount', '5000.00')
            ->assertJsonPath('type', 'RECEITA')
            ->assertJsonPath('category.id', $this->categoryReceita->id);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 5000.00,
        ]);
    }

    public function test_create_transaction_despesa(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/transactions', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 150.00,
            'description' => 'Groceries',
            'date' => '2026-01-20',
            'type' => 'DESPESA',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('amount', '150.00')
            ->assertJsonPath('type', 'DESPESA');
    }

    public function test_create_transaction_amount_zero_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/transactions', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 0,
            'description' => 'Test',
            'date' => '2026-01-20',
            'type' => 'DESPESA',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_create_transaction_negative_amount_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/transactions', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => -50,
            'description' => 'Test',
            'date' => '2026-01-20',
            'type' => 'DESPESA',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_create_transaction_missing_required_fields_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/transactions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'amount', 'description', 'date', 'type']);
    }

    public function test_create_transaction_invalid_category_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/transactions', [
            'category_id' => 99999,
            'amount' => 100,
            'description' => 'Test',
            'date' => '2026-01-20',
            'type' => 'DESPESA',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_create_transaction_category_other_user_rejected(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => 'DESPESA']);

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/transactions', [
            'category_id' => $otherCategory->id,
            'amount' => 100,
            'description' => 'Test',
            'date' => '2026-01-20',
            'type' => 'DESPESA',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_create_transaction_receita_with_despesa_category_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/transactions', [
            'category_id' => $this->categoryDespesa->id,
            'amount' => 100,
            'description' => 'Test',
            'date' => '2026-01-20',
            'type' => 'RECEITA',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Category type does not match transaction type');
    }

    public function test_create_transaction_despesa_with_receita_category_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/transactions', [
            'category_id' => $this->categoryReceita->id,
            'amount' => 100,
            'description' => 'Test',
            'date' => '2026-01-20',
            'type' => 'DESPESA',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Category type does not match transaction type');
    }

    public function test_list_transactions(): void
    {
        Transaction::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_list_transactions_filter_by_type(): void
    {
        Transaction::factory()->create(['user_id' => $this->user->id, 'type' => 'RECEITA']);
        Transaction::factory()->create(['user_id' => $this->user->id, 'type' => 'DESPESA']);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/transactions?type=RECEITA');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('RECEITA', $data[0]['type']);
    }

    public function test_list_transactions_filter_by_category(): void
    {
        Transaction::factory()->create(['user_id' => $this->user->id, 'category_id' => $this->categoryReceita->id]);
        Transaction::factory()->create(['user_id' => $this->user->id, 'category_id' => $this->categoryDespesa->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson("/api/transactions?category_id={$this->categoryReceita->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_list_transactions_filter_by_description(): void
    {
        Transaction::factory()->create(['user_id' => $this->user->id, 'description' => 'Salary payment']);
        Transaction::factory()->create(['user_id' => $this->user->id, 'description' => 'Food expense']);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/transactions?description=Salary');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Salary', $data[0]['description']);
    }

    public function test_list_transactions_filter_by_month(): void
    {
        Transaction::factory()->create(['user_id' => $this->user->id, 'date' => '2026-01-15']);
        Transaction::factory()->create(['user_id' => $this->user->id, 'date' => '2026-02-15']);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/transactions?month=2026-01');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringStartsWith('2026-01', $data[0]['date']);
    }

    public function test_list_transactions_filter_by_date_range(): void
    {
        Transaction::factory()->create(['user_id' => $this->user->id, 'date' => '2026-01-10']);
        Transaction::factory()->create(['user_id' => $this->user->id, 'date' => '2026-01-20']);
        Transaction::factory()->create(['user_id' => $this->user->id, 'date' => '2026-02-10']);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/transactions?start_date=2026-01-15&end_date=2026-01-31');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_list_transactions_pagination(): void
    {
        Transaction::factory()->count(25)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/transactions?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('total'));
    }

    public function test_list_transactions_default_order_desc(): void
    {
        Transaction::factory()->create(['user_id' => $this->user->id, 'date' => '2026-01-10', 'description' => 'Older']);
        Transaction::factory()->create(['user_id' => $this->user->id, 'date' => '2026-01-20', 'description' => 'Newer']);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/transactions');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Newer', $data[0]['description']);
    }

    public function test_show_transaction(): void
    {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $transaction->id)
            ->assertJsonPath('description', $transaction->description);
    }

    public function test_update_transaction(): void
    {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id, 'description' => 'Old']);

        $response = $this->withHeaders($this->authHeaders())->putJson("/api/transactions/{$transaction->id}", [
            'description' => 'Updated',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('description', 'Updated');
    }

    public function test_delete_transaction(): void
    {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Transaction deleted successfully']);

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_user_cannot_access_other_user_transaction(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(404);
    }

    public function test_user_cannot_update_other_user_transaction(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())->putJson("/api/transactions/{$transaction->id}", [
            'description' => 'Hacked',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_cannot_delete_other_user_transaction(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_cannot_access_transactions(): void
    {
        $response = $this->getJson('/api/transactions');
        $response->assertStatus(401);
    }
}