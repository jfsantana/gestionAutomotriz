@extends('layouts.app')

@section('title', 'Dashboard Técnico')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Base Técnica del Sistema</h1>
        <p class="text-muted mb-0 small">SaaS Multiempresa de Gestión Automotriz - Fase 0: Arquitectura y Cimientos</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('health') }}" target="_blank" class="btn btn-sm btn-outline-primary">
            Verificar Endpoint /health (JSON)
        </a>
    </div>
</div>

<!-- Bienvenida Institucional -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h4 class="card-title fw-bold text-dark mb-0">Bienvenido, {{ auth()->user()->name ?? 'Usuario' }}</h4>
                @if(auth()->user()->company)
                    <small class="text-primary fw-semibold">{{ auth()->user()->company->name }}</small>
                @endif
            </div>
            <span class="badge bg-primary text-uppercase px-3 py-2">Rol: {{ auth()->user()->role ?? 'N/A' }}</span>
        </div>
        <p class="card-text text-secondary mb-3">
            Sesión iniciada con el usuario <code>{{ auth()->user()->username ?? '' }}</code>. 
            @if(auth()->user()->role === 'mechanic')
                Panel operativo asignado a tu perfil técnico en taller.
            @else
                El entorno base se encuentra instalado y estructurado siguiendo principios de arquitectura modular orientada a dominio (DDD) y preparado para aislamiento por empresa (Multi-Tenant).
            @endif
        </p>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded border">
                    <strong class="d-block text-dark">Entorno de Ejecución</strong>
                    <span class="text-muted small">WAMP Server &bull; PHP {{ $systemHealth['php_version'] }} &bull; Laravel {{ $systemHealth['laravel_version'] }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded border">
                    <strong class="d-block text-dark">Aislamiento Multiempresa</strong>
                    <span class="text-muted small">Estructura base lista: <code>CompanyScopedInterface</code> &bull; <code>BelongsToCompany</code></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded border">
                    <strong class="d-block text-dark">Compatibilidad Móvil</strong>
                    <span class="text-muted small">Servicios desacoplados y endpoints técnicos listos para futura API Android</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estado Técnico del Sistema -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Estado de Salud del Sistema</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Estado General</span>
                        @if($systemHealth['status'] === 'ok')
                            <span class="badge bg-success">Operativo (OK)</span>
                        @else
                            <span class="badge bg-warning text-dark">Degradado</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Servicio Aplicación Web</span>
                        <span class="badge bg-success">Activo</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Conexión Base de Datos</span>
                        @if(($systemHealth['checks']['database']['status'] ?? null) === 'ok')
                            <span class="badge bg-success">Conectada</span>
                        @else
                            <span class="badge bg-secondary" title="{{ $systemHealth['checks']['database']['message'] ?? '' }}">Sin configurar / En espera</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Almacenamiento Local (Storage)</span>
                        @if(($systemHealth['checks']['storage']['status'] ?? null) === 'ok')
                            <span class="badge bg-success">Operativo</span>
                        @else
                            <span class="badge bg-warning text-dark" title="{{ $systemHealth['checks']['storage']['message'] ?? '' }}">No disponible</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Ambiente de Ejecución</span>
                        <span class="text-muted">{{ $systemHealth['environment'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Marca de Tiempo</span>
                        <span class="text-muted small">{{ $systemHealth['timestamp'] }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos Placeholder -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Accesos Rápidos Arquitectónicos</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Módulos en preparación para fases posteriores (placeholders no funcionales):</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-secondary text-start py-2" type="button" disabled>
                        <strong>Empresas y Talleres</strong> &mdash; <small class="text-muted">Gestión de tenants y configuraciones (Fase 1)</small>
                    </button>
                    <button class="btn btn-outline-secondary text-start py-2" type="button" disabled>
                        <strong>Gestión de Vehículos</strong> &mdash; <small class="text-muted">Registro y expedientes automotrices (Fase 2)</small>
                    </button>
                    <button class="btn btn-outline-secondary text-start py-2" type="button" disabled>
                        <strong>Órdenes de Mantenimiento</strong> &mdash; <small class="text-muted">Flujos de trabajo y checklist (Fase 2)</small>
                    </button>
                    <button class="btn btn-outline-secondary text-start py-2" type="button" disabled>
                        <strong>Facturación y Suscripciones</strong> &mdash; <small class="text-muted">Módulo SaaS y planes (Fase 4)</small>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
