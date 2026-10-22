<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Servicio técnico desacoplado para verificación del estado del sistema.
 * Valida componentes esenciales (aplicación, base de datos y storage) sin sobrecargar controladores.
 */
class SystemHealthService
{
    /**
     * Ejecuta el diagnóstico de los componentes del sistema.
     *
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $databaseCheck = $this->checkDatabase();
        $storageCheck = $this->checkStorage();

        $isHealthy = $databaseCheck['status'] === 'ok' && $storageCheck['status'] === 'ok';

        return [
            'status' => $isHealthy ? 'ok' : 'degraded',
            'app_name' => config('app.name', 'AutoBitacora'),
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'checks' => [
                'app' => [
                    'status' => 'ok',
                    'message' => 'Application runtime operational.',
                ],
                'database' => $databaseCheck,
                'storage' => $storageCheck,
            ],
        ];
    }

    /**
     * Valida la conectividad a la base de datos de manera segura.
     *
     * @return array<string, string>
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'status' => 'ok',
                'message' => 'Database connection established.',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $exception->getMessage(),
            ];
        }
    }

    /**
     * Valida la disponibilidad y permisos de escritura en el disco local de storage.
     *
     * @return array<string, string>
     */
    protected function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . uniqid() . '.tmp';
            Storage::disk('local')->put($testFile, 'health-check');
            Storage::disk('local')->delete($testFile);

            return [
                'status' => 'ok',
                'message' => 'Storage disk is writable.',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed: ' . $exception->getMessage(),
            ];
        }
    }
}

