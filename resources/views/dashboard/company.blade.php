@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
{{-- Encabezado Principal --}}
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0 fw-bold text-dark">Dashboard</h1>
        <p class="text-muted mb-0 small">
            Panel de control operativo y trazabilidad vehicular del taller
            @if(auth()->user()?->company)
                &mdash; <strong class="text-primary">{{ auth()->user()->company->name }}</strong>
            @endif
        </p>
    </div>
</div>

{{-- SECCIÓN 1: RESUMEN GENERAL --}}
<div class="mb-4">
    <div class="d-flex align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">Resumen General</h5>
    </div>

    <div class="row g-3">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-primary border-4 bg-white h-100">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase fw-semibold d-block">Vehículos Registrados</small>
                    <div class="h3 mb-0 text-primary fw-bold">{{ $vehiclesCount }}</div>
                    <small class="text-muted">Inventario del taller</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-info border-4 bg-white h-100">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase fw-semibold d-block">Órdenes Abiertas</small>
                    <div class="h3 mb-0 text-info text-dark fw-bold">{{ $openOrdersCount }}</div>
                    <small class="text-muted">En proceso u abiertas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-warning border-4 bg-white h-100">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase fw-semibold d-block">Alertas de Servicio</small>
                    <div class="h3 mb-0 text-warning text-dark fw-bold">{{ $serviceAlertsCount }}</div>
                    <small class="text-muted">Alertas pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm border-start border-success border-4 bg-white h-100">
                <div class="card-body py-3">
                    <small class="text-muted text-uppercase fw-semibold d-block">Clientes Registrados</small>
                    <div class="h3 mb-0 text-success fw-bold">{{ $customersCount }}</div>
                    <small class="text-muted">Propietarios y flotas</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SECCIÓN 2: PRÓXIMOS MANTENIMIENTOS --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="card-title mb-0 fw-semibold text-dark">Próximos Mantenimientos</h5>
    </div>
    <div class="card-body text-center py-5">
        <div class="text-muted mb-2" style="font-size: 2rem;">&#128197;</div>
        <h6 class="fw-semibold text-secondary mb-1">Sin mantenimientos programados visibles</h6>
        <p class="text-muted small mb-0">Esta sección mostrará los recordatorios y mantenimientos próximos a vencer por fecha o kilometraje.</p>
    </div>
</div>

{{-- SECCIÓN 3 Y 4: ESTADÍSTICAS Y ACTIVIDAD RECIENTE --}}
<div class="row g-4 mb-4">
    {{-- SECCIÓN 3: ESTADÍSTICAS --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold text-dark">Estadísticas</h5>
            </div>
            <div class="card-body text-center py-5">
                <div class="text-muted mb-2" style="font-size: 2rem;">&#128200;</div>
                <h6 class="fw-semibold text-secondary mb-1">Módulo de estadísticas en preparación</h6>
                <p class="text-muted small mb-0">Próximamente se visualizarán métricas de rendimiento, órdenes completadas y servicios más solicitados.</p>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 4: ACTIVIDAD RECIENTE --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold text-dark">Actividad Reciente</h5>
            </div>
            <div class="card-body text-center py-5">
                <div class="text-muted mb-2" style="font-size: 2rem;">&#128340;</div>
                <h6 class="fw-semibold text-secondary mb-1">Sin actividad reciente registrada</h6>
                <p class="text-muted small mb-0">Aquí se listará el historial de cambios de estado en órdenes, ingresos de vehículos y atenciones del taller.</p>
            </div>
        </div>
    </div>
</div>
@endsection
