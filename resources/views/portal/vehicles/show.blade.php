@extends('portal.layouts.client')

@section('title', 'Vehículo ' . $vehicle->plate)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
    <div>
        <x-page-heading section="vehicles" class="h4 mb-0 fw-bold text-dark">{{ $vehicle->make }} {{ $vehicle->model }}</x-page-heading>
        <p class="text-muted mb-0 small">Placa: <strong class="badge bg-dark">{{ $vehicle->plate }}</strong></p>
    </div>
    <div class="btn-toolbar gap-2">
        <a href="{{ route('portal.vehicles.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver a Mis Vehículos
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
            @if($vehicle->photo_path)
                <img src="{{ asset('storage/' . $vehicle->photo_path) }}" alt="Foto" class="card-img-top" style="max-height: 240px; object-fit: cover;">
            @endif
            <div class="card-body p-4">
                <h5 class="fw-bold text-dark mb-3">Datos del Vehículo</h5>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2">Marca / Modelo: <strong>{{ $vehicle->make }} {{ $vehicle->model }}</strong></li>
                    <li class="mb-2">Placa: <strong>{{ $vehicle->plate }}</strong></li>
                    <li class="mb-2">VIN / Chasis: <code>{{ $vehicle->vin ?? 'No especificado' }}</code></li>
                    <li class="mb-2">Año: <strong>{{ $vehicle->year ?? '—' }}</strong></li>
                    <li class="mb-2">Color: <strong>{{ $vehicle->color ?? '—' }}</strong></li>
                    <li class="mb-2">Kilometraje Registrado: <strong>{{ number_format($vehicle->current_mileage) }} km</strong></li>
                    <li class="mb-0">Combustible: <strong>{{ $vehicle->fuel_type ? ucfirst($vehicle->fuel_type) : '—' }}</strong></li>
                </ul>
            </div>
            <div class="card-footer bg-light p-3 border-top">
                <form method="POST" action="{{ route('portal.vehicles.decommission', $vehicle) }}" onsubmit="return confirm('¿Confirmas dar de baja este vehículo de tu cuenta? Se desvinculará de tu perfil.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-semibold">
                        Dar de Baja este Vehículo de mi Cuenta
                    </button>
                </form>
            </div>
        </div>

        {{-- Alerta de Próximo Servicio --}}
        @if(isset($reminder) && $reminder)
            <div class="card shadow-sm border-0 border-start border-{{ $reminder['evaluation']['badge_class'] }} border-4 bg-white p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="text-dark small">Próximo Control Sugerido:</strong>
                    <span class="badge bg-{{ $reminder['evaluation']['badge_class'] }}">
                        {{ $reminder['evaluation']['status_label'] }}
                    </span>
                </div>
                <div class="small text-secondary">
                    {{ $reminder['order']->service_name_snapshot }}<br>
                    @if($reminder['order']->next_due_date)
                        Fecha: <strong>{{ $reminder['order']->next_due_date->format('d/m/Y') }}</strong><br>
                    @endif
                    @if($reminder['order']->next_due_mileage)
                        Kilometraje: <strong>{{ number_format($reminder['order']->next_due_mileage) }} km</strong>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- HISTORIAL DE MANTENIMIENTO DEL VEHÍCULO --}}
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">Historial de Mantenimientos</h6>
                <a href="{{ route('portal.maintenance.create') }}" class="btn btn-sm btn-primary">
                    + Registrar Servicio Personal
                </a>
            </div>
            <div class="card-body p-0">
                @if($vehicle->maintenanceOrders->isEmpty())
                    <p class="text-muted text-center py-5 mb-0 small">No hay órdenes ni mantenimientos registrados para este vehículo.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-3">Servicio / Fecha</th>
                                    <th scope="col">Kilometraje</th>
                                    <th scope="col">Origen</th>
                                    <th scope="col">Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicle->maintenanceOrders as $ord)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark">{{ $ord->service_name_snapshot }}</div>
                                            <small class="text-muted">{{ $ord->service_date?->format('d/m/Y') }}</small>
                                        </td>
                                        <td>{{ number_format($ord->current_mileage) }} km</td>
                                        <td>
                                            @if($ord->origin === 'workshop')
                                                <span class="badge bg-success">&#10003; Taller Verificado</span>
                                            @else
                                                <span class="badge bg-secondary">👤 Registro Personal</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-secondary d-block">
                                                {{ Str::limit($ord->technical_notes ?: $ord->customer_notes ?: 'Sin notas', 40) }}
                                            </small>
                                            @if($ord->cost)
                                                <small class="text-dark fw-bold">${{ number_format($ord->cost, 2) }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
