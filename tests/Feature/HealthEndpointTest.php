<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_is_successful_when_database_is_available(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['app' => 'ok', 'db' => 'ok']);
    }

    public function test_health_endpoint_fails_closed_when_database_is_unavailable(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new RuntimeException('database unavailable'));
        DB::shouldReceive('table')->andThrow(new RuntimeException('database unavailable'));

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJson(['app' => 'ok', 'db' => 'error']);
    }
}
