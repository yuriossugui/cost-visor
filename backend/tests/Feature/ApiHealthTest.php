<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiHealthTest extends TestCase
{
    public function test_api_health_endpoint(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_web_health_endpoint(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }
}