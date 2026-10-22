<?php

namespace App\Http\Controllers;

use App\Services\Health\SystemHealthService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de endpoint /health.
 * Diseñado como endpoint público, rápido y en JSON, apto para monitoreo y futuras apps móviles.
 */
class HealthController extends Controller
{
    public function __construct(
        protected SystemHealthService $healthService
    ) {}

    /**
     * Responde con el estado técnico del sistema.
     */
    public function __invoke(): JsonResponse
    {
        $health = $this->healthService->check();
        $httpStatus = $health['status'] === 'ok' ? 200 : 503;

        return response()->json($health, $httpStatus);
    }
}
