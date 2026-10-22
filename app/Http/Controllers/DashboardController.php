<?php

namespace App\Http\Controllers;

use App\Services\Health\SystemHealthService;
use Illuminate\Contracts\View\View;

/**
 * Controlador de la vista principal del Dashboard institucional/técnico.
 */
class DashboardController extends Controller
{
    public function __construct(
        protected SystemHealthService $healthService
    ) {}

    /**
     * Muestra la pantalla del Dashboard con el estado del sistema.
     */
    public function index(): View
    {
        $systemHealth = $this->healthService->check();

        return view('dashboard.index', [
            'systemHealth' => $systemHealth,
        ]);
    }
}
