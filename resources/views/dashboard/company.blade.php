@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
{{-- Encabezado Principal --}}
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="dashboard" class="h3 mb-0 fw-bold text-dark">Dashboard</x-page-heading>
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
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-0 fw-semibold text-dark">Próximos Mantenimientos</h5>
            <small class="text-muted">Vencimientos en los próximos 7 días o con kilometraje próximo</small>
        </div>
        @if(isset($upcomingReminders) && $upcomingReminders->total() > 0)
            <span class="badge bg-primary rounded-pill">{{ $upcomingReminders->total() }} registros</span>
        @endif
    </div>
    <div class="card-body p-0">
        @if(!isset($upcomingReminders) || $upcomingReminders->isEmpty())
            <div class="text-center py-5">
                <div class="text-muted mb-2" style="font-size: 2rem;">&#128197;</div>
                <h6 class="fw-semibold text-secondary mb-1">Sin mantenimientos próximos a vencer</h6>
                <p class="text-muted small mb-0">No hay vehículos con vencimiento programado en los siguientes 7 días o con kilometraje crítico.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Vehículo</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Mantenimiento</th>
                            <th scope="col">Tipo de Vencimiento</th>
                            <th scope="col">Valor de Vencimiento</th>
                            <th scope="col" class="pe-4 text-end">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingReminders as $order)
                            @php
                                $rem = $order->reminder;
                                $hasDate = $order->next_due_date !== null;
                                $hasMileage = $order->next_due_mileage !== null;
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark text-uppercase px-2 py-1">{{ $order->vehicle?->plate ?? 'S/P' }}</span>
                                    <div class="fw-semibold text-dark small mt-1">{{ $order->vehicle?->make }} {{ $order->vehicle?->model }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $order->customer?->full_name ?? '—' }}</div>
                                    @if($order->customer?->phone)
                                        <small class="text-muted">{{ $order->customer->phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-dark fw-semibold small">{{ $order->service_name_snapshot ?? ($order->service?->name ?? 'Servicio Programado') }}</div>
                                    <small class="text-muted">Último: {{ $order->service_date?->format('d/m/Y') }}</small>
                                </td>
                                <td>
                                    @if($hasDate && $hasMileage)
                                        <span class="badge bg-light text-dark border">Fecha &bull; Kilometraje</span>
                                    @elseif($hasDate)
                                        <span class="badge bg-light text-dark border">Fecha</span>
                                    @elseif($hasMileage)
                                        <span class="badge bg-light text-dark border">Kilometraje</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($hasDate)
                                        <div class="small fw-semibold text-dark">
                                            &#128197; {{ $order->next_due_date->format('d/m/Y') }}
                                            @if($rem['days_remaining'] !== null)
                                                <small class="text-muted">
                                                    @if($rem['days_remaining'] < 0)
                                                        (hace {{ abs($rem['days_remaining']) }}d)
                                                    @elseif($rem['days_remaining'] === 0)
                                                        (hoy)
                                                    @else
                                                        (en {{ $rem['days_remaining'] }}d)
                                                    @endif
                                                </small>
                                            @endif
                                        </div>
                                    @endif
                                    @if($hasMileage)
                                        <div class="small text-muted">
                                            &#128739; {{ number_format($order->next_due_mileage) }} km
                                            @if($rem['mileage_remaining'] !== null)
                                                <small>
                                                    @if($rem['mileage_remaining'] <= 0)
                                                        (rebasado)
                                                    @else
                                                        (restan {{ number_format($rem['mileage_remaining']) }} km)
                                                    @endif
                                                </small>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <span class="badge bg-{{ $rem['badge_class'] ?? 'secondary' }} px-2 py-1">
                                        {{ $rem['status_label'] ?? 'Programado' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($upcomingReminders->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center bg-light">
                    <small class="text-muted">
                        Mostrando {{ $upcomingReminders->firstItem() }} - {{ $upcomingReminders->lastItem() }} de {{ $upcomingReminders->total() }} registros
                    </small>
                    {{ $upcomingReminders->links() }}
                </div>
            @endif
        @endif
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
