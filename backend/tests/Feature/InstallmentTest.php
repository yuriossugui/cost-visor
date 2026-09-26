<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Installment;
use App\Models\Transaction;
use App\Models\User;
use Tests\TestCase;

class InstallmentTest extends TestCase
{
    protected User $user;
    protected Category $categoryDespesa;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
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

    public function test_create_installment(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/installments', [
            'category_id' => $this->categoryDespesa->id,
            'total_amount' => 1000.00,
            'installment_count' => 10,
            'first_installment_date' => '2026-01-15',
            'description' => 'Laptop purchase',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('total_amount', '1000.00')
            ->assertJsonPath('installment_count', 10)
            ->assertJsonPath('description', 'Laptop purchase');

        $this->assertDatabaseHas('installments', [
            'user_id' => $this->user->id,
            'total_amount' => 1000.00,
        ]);

        $installment = Installment::where('user_id', $this->user->id)->first();
        $this->assertCount(10, $installment->transactions);
    }

    public function test_create_installment_amount_distribution(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/installments', [
            'category_id' => $this->categoryDespesa->id,
            'total_amount' => 100.00,
            'installment_count' => 3,
            'first_installment_date' => '2026-01-15',
            'description' => 'Test installment',
        ]);

        $response->assertStatus(201);

        $installment = Installment::where('user_id', $this->user->id)->first();
        $transactions = $installment->transactions()->orderBy('installment_number')->get();

        $this->assertEquals('33.33', $transactions[0]->amount);
        $this->assertEquals('33.33', $transactions[1]->amount);
        $this->assertEquals('33.34', $transactions[2]->amount);

        $sum = $transactions->sum('amount');
        $this->assertEquals(100.00, $sum);
    }

    public function test_create_installment_exact_division(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/installments', [
            'category_id' => $this->categoryDespesa->id,
            'total_amount' => 300.00,
            'installment_count' => 3,
            'first_installment_date' => '2026-01-15',
            'description' => 'Test installment',
        ]);

        $response->assertStatus(201);

        $installment = Installment::where('user_id', $this->user->id)->first();
        $transactions = $installment->transactions()->orderBy('installment_number')->get();

        $this->assertEquals('100.00', $transactions[0]->amount);
        $this->assertEquals('100.00', $transactions[1]->amount);
        $this->assertEquals('100.00', $transactions[2]->amount);
    }

    public function test_create_installment_dates_are_monthly(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/installments', [
            'category_id' => $this->categoryDespesa->id,
            'total_amount' => 300.00,
            'installment_count' => 3,
            'first_installment_date' => '2026-01-15',
            'description' => 'Test installment',
        ]);

        $response->assertStatus(201);

        $installment = Installment::where('user_id', $this->user->id)->first();
        $transactions = $installment->transactions()->orderBy('installment_number')->get();

        $this->assertEquals('2026-01-15', $transactions[0]->date->format('Y-m-d'));
        $this->assertEquals('2026-02-15', $transactions[1]->date->format('Y-m-d'));
        $this->assertEquals('2026-03-15', $transactions[2]->date->format('Y-m-d'));
    }

    public function test_create_installment_category_must_be_despesa(): void
    {
        $categoryReceita = Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Salary',
            'type' => 'RECEITA',
        ]);

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/installments', [
            'category_id' => $categoryReceita->id,
            'total_amount' => 1000.00,
            'installment_count' => 10,
            'first_installment_date' => '2026-01-15',
            'description' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Installments are only allowed for expense categories');
    }

    public function test_create_installment_category_other_user_rejected(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id, 'type' => 'DESPESA']);

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/installments', [
            'category_id' => $otherCategory->id,
            'total_amount' => 1000.00,
            'installment_count' => 10,
            'first_installment_date' => '2026-01-15',
            'description' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_create_installment_invalid_count_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/installments', [
            'category_id' => $this->categoryDespesa->id,
            'total_amount' => 1000.00,
            'installment_count' => 1,
            'first_installment_date' => '2026-01-15',
            'description' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['installment_count']);
    }

    public function test_create_installment_negative_amount_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/installments', [
            'category_id' => $this->categoryDespesa->id,
            'total_amount' => -100.00,
            'installment_count' => 10,
            'first_installment_date' => '2026-01-15',
            'description' => 'Test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['total_amount']);
    }

    public function test_list_installments(): void
    {
        Installment::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/installments');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_show_installment(): void
    {
        $installment = Installment::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson("/api/installments/{$installment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $installment->id);
    }

    public function test_delete_installment(): void
    {
        $installment = Installment::factory()->create(['user_id' => $this->user->id]);
        $installment->transactions()->create([
            'user_id' => $this->user->id,
            'category_id' => $installment->category_id,
            'amount' => 100,
            'description' => 'Test',
            'date' => '2026-01-15',
            'type' => 'DESPESA',
        ]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/installments/{$installment->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Installment deleted successfully']);

        $this->assertDatabaseMissing('installments', ['id' => $installment->id]);
        $this->assertDatabaseMissing('transactions', ['installment_id' => $installment->id]);
    }

    public function test_user_cannot_access_other_user_installment(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $installment = Installment::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson("/api/installments/{$installment->id}");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_cannot_access_installments(): void
    {
        $response = $this->getJson('/api/installments');
        $response->assertStatus(401);
    }
}