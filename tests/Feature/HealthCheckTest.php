<?php

namespace Tests\Feature;

use App\Services\Health\SystemHealthService;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Valida que /health responda JSON con HTTP 200 cuando el servicio reporta ok.
     */
    public function test_health_endpoint_returns_ok_json_response(): void
    {
        $mockService = $this->createMock(SystemHealthService::class);
        $mockService->method('check')->willReturn([
            'status' => 'ok',
            'app_name' => 'gestionAutomotriz',
            'timestamp' => '2026-10-22T12:00:00Z',
            'environment' => 'testing',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'checks' => [
                'app' => ['status' => 'ok', 'message' => 'Operational'],
                'database' => ['status' => 'ok', 'message' => 'Connected'],
                'storage' => ['status' => 'ok', 'message' => 'Writable'],
            ],
        ]);

        $this->app->instance(SystemHealthService::class, $mockService);

        $response = $this->getJson('/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'app_name' => 'gestionAutomotriz',
        ]);
        $response->assertJsonStructure([
            'status',
            'app_name',
            'timestamp',
            'environment',
            'php_version',
            'laravel_version',
            'checks' => [
                'app' => ['status', 'message'],
                'database' => ['status', 'message'],
                'storage' => ['status', 'message'],
            ],
        ]);
    }

    /**
     * Valida que /health responda HTTP 503 cuando el estado del sistema es degradado.
     */
    public function test_health_endpoint_returns_503_when_degraded(): void
    {
        $mockService = $this->createMock(SystemHealthService::class);
        $mockService->method('check')->willReturn([
            'status' => 'degraded',
            'app_name' => 'gestionAutomotriz',
            'timestamp' => '2026-10-22T12:00:00Z',
            'environment' => 'testing',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'checks' => [
                'app' => ['status' => 'ok', 'message' => 'Operational'],
                'database' => ['status' => 'error', 'message' => 'Failed'],
                'storage' => ['status' => 'ok', 'message' => 'Writable'],
            ],
        ]);

        $this->app->instance(SystemHealthService::class, $mockService);

        $response = $this->getJson('/health');

        $response->assertStatus(503);
        $response->assertJson(['status' => 'degraded']);
    }
}


