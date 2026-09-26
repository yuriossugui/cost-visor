<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthProfileTest extends TestCase
    {
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

    public function test_user_can_view_profile(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'created_at'],
            ]);
    }

    public function test_user_can_update_name(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/me', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.name', 'New Name');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_user_can_update_email(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/me', [
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_cannot_update_to_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/me', [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_logout(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_logout_invalidates_token_for_future_requests(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/logout');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/me', ['name' => 'New Name']);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');
        $response->assertStatus(401);
    }
}