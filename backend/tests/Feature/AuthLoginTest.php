<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
        ]);
    }

    public function test_valid_login_returns_jwt(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);

        $this->assertNotEmpty($response->json('access_token'));
        $this->assertEquals('bearer', $response->json('token_type'));
    }

    public function test_invalid_credentials_rejected(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_missing_credentials_rejected(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_invalid_email_format_rejected(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_protected_route_requires_authentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_protected_route_with_valid_token(): void
    {
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
            ]);
    }
}