@extends('portal.layouts.client')

@section('title', 'Inicio / Resumen')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
    <div>
        <x-page-heading section="dashboard" class="h4 mb-0 fw-bold text-dark">Bienvenido, {{ $customer->full_name }}</x-page-heading>
        <p class="text-muted mb-0 small">Consulta el estado, kilometraje y mantenimientos de tus vehículos</p>
    </div>
    <div class="btn-toolbar gap-2">
        <a href="{{ route('portal.maintenance.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Registrar Mantenimiento Propio
        </a>
    </div>
</div>

{{-- TARJETAS RESUMEN --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-primary border-4 bg-white">
            <div class="card-body py-3">
                <small class="text-muted text-uppercase fw-semibold d-block">Mis Vehículos Vinculados</small>
                <div class="h3 mb-0 text-primary fw-bold">{{ $vehicles->count() }}</div>
                <small class="text-muted">Activos en mi cuenta</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-warning border-4 bg-white">
            <div class="card-body py-3">
                <small class="text-muted text-uppercase fw-semibold d-block">Próximos Mantenimientos</small>
                <div class="h3 mb-0 text-warning text-dark fw-bold">{{ count($upcomingReminders) }}</div>
                <small class="text-muted">En los próximos 30 días o 1,000 km</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-success border-4 bg-white">
            <div class="card-body py-3">
                <small class="text-muted text-uppercase fw-semibold d-block">Taller Emisor</small>
                <div class="h6 mb-0 text-dark fw-bold text-truncate">{{ $customer->company?->name }}</div>
                <small class="text-muted">Atención: {{ $customer->company?->phone ?? '—' }}</small>
            </div>
        </div>
    </div>
</div>

{{-- ALERTAS DE VENCIMIENTOS --}}
@if(! empty($upcomingReminders))
    <div class="card shadow-sm border-0 mb-4 border-start border-warning border-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="card-title mb-0 fw-bold text-dark">&#9888; Servicios Pendientes o Próximos a Vencer</h6>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                @foreach($upcomingReminders as $item)
                    <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                        <div>
                            <span class="badge bg-dark text-uppercase me-2">{{ $item['vehicle']->plate }}</span>
                            <strong>{{ $item['vehicle']->make }} {{ $item['vehicle']->model }}</strong>
                            <div class="text-muted small mt-1">
                                Próximo servicio: <strong>{{ $item['reminder']['order']->service_name_snapshot }}</strong>
                                @if($item['reminder']['order']->next_due_date)
                                    &bull; Fecha sugerida: {{ $item['reminder']['order']->next_due_date->format('d/m/Y') }}
                                @endif
                                @if($item['reminder']['order']->next_due_mileage)
                                    &bull; Kilometraje: {{ number_format($item['reminder']['order']->next_due_mileage) }} km
                                @endif
                            </div>
                        </div>
                        <span class="badge bg-{{ $item['reminder']['evaluation']['badge_class'] }}">
                            {{ $item['reminder']['evaluation']['status_label'] }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- LISTADO DE VEHÍCULOS --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">Mis Vehículos</h6>
                <a href="{{ route('portal.vehicles.index') }}" class="btn btn-sm btn-outline-secondary">Ver Todos</a>
            </div>
            <div class="card-body p-0">
                @if($vehicles->isEmpty())
                    <p class="text-muted p-4 mb-0 text-center small">No tienes vehículos activos vinculados a tu cuenta.</p>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($vehicles as $veh)
                            <a href="{{ route('portal.vehicles.show', $veh) }}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                                @if($veh->photo_path)
                                    <img src="{{ asset('storage/' . $veh->photo_path) }}" alt="Foto" width="56" height="42" class="rounded me-3 border object-fit-cover shadow-sm">
                                @else
                                    <div class="rounded me-3 border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 56px; height: 42px;">
                                        &#128663;
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark">{{ $veh->make }} {{ $veh->model }}</div>
                                    <small class="text-muted">Placa: <span class="badge bg-dark">{{ $veh->plate }}</span> &bull; {{ number_format($veh->current_mileage) }} km</small>
                                </div>
                                <span class="text-secondary small">&rarr;</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ÚLTIMOS MANTENIMIENTOS --}}
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">Últimos Mantenimientos Registrados</h6>
                <a href="{{ route('portal.maintenance.index') }}" class="btn btn-sm btn-outline-secondary">Ver Historial</a>
            </div>
            <div class="card-body p-0">
                @if($recentOrders->isEmpty())
                    <p class="text-muted p-4 mb-0 text-center small">No hay mantenimientos registrados aún.</p>
                @else
                    <ul class="list-group list-group-flush small">
                        @foreach($recentOrders as $ord)
                            <li class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark">{{ $ord->service_name_snapshot }}</strong>
                                    @if($ord->origin === 'workshop')
                                        <span class="badge bg-success">&#10003; Taller</span>
                                    @else
                                        <span class="badge bg-secondary">👤 Personal</span>
                                    @endif
                                </div>
                                <div class="text-muted">
                                    Vehículo: <strong>{{ $ord->vehicle?->plate }}</strong> ({{ $ord->vehicle?->make }} {{ $ord->vehicle?->model }}) &bull;
                                    Fecha: {{ $ord->service_date?->format('d/m/Y') }} &bull;
                                    {{ number_format($ord->current_mileage) }} km
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
