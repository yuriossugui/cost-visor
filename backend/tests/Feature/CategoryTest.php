<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
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

    public function test_create_category(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/categories', [
            'name' => 'Salary',
            'color' => '#6366f1',
            'type' => 'RECEITA',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Salary')
            ->assertJsonPath('color', '#6366f1')
            ->assertJsonPath('type', 'RECEITA')
            ->assertJsonPath('user_id', $this->user->id);

        $this->assertDatabaseHas('categories', [
            'name' => 'Salary',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_category_invalid_type_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/categories', [
            'name' => 'Test',
            'color' => '#6366f1',
            'type' => 'INVALID',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_create_category_missing_name_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/categories', [
            'color' => '#6366f1',
            'type' => 'RECEITA',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_category_invalid_color_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/categories', [
            'name' => 'Test',
            'color' => 'invalid-color',
            'type' => 'RECEITA',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    }

    public function test_create_category_missing_color_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/categories', [
            'name' => 'Test',
            'type' => 'RECEITA',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    }

    public function test_list_categories(): void
    {
        Category::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_show_category(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $category->id)
            ->assertJsonPath('name', $category->name);
    }

    public function test_update_category(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Old Name']);

        $response = $this->withHeaders($this->authHeaders())->putJson("/api/categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'New Name');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
        ]);
    }

    public function test_delete_category(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Category deleted successfully']);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_access_other_user_category(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $category = Category::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())->getJson("/api/categories/{$category->id}");

        $response->assertStatus(404);
    }

    public function test_user_cannot_update_other_user_category(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $category = Category::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())->putJson("/api/categories/{$category->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_cannot_delete_other_user_category(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $category = Category::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(404);
    }

    public function test_delete_category_without_transactions(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Category deleted successfully']);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_delete_category_with_transactions_rejected(): void
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        Transaction::factory()->create(['user_id' => $this->user->id, 'category_id' => $category->id]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete category with associated transactions');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_delete_other_user_category_with_transactions(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $category = Category::factory()->create(['user_id' => $otherUser->id]);
        Transaction::factory()->create(['user_id' => $otherUser->id, 'category_id' => $category->id]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_cannot_access_categories(): void
    {
        $response = $this->getJson('/api/categories');
        $response->assertStatus(401);
    }
}