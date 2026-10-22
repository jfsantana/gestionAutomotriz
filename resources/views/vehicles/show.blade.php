@extends('layouts.app')

@section('title', 'Vehículo: ' . $vehicle->plate)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">{{ $vehicle->make }} {{ $vehicle->model }}</h1>
        <p class="text-muted mb-0 small">Ficha del vehículo &bull; Placa: <strong class="text-dark">{{ $vehicle->plate }}</strong></p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-primary">
            Editar Vehículo
        </a>
        <a href="{{ route('customers.show', $vehicle->customer_id) }}" class="btn btn-sm btn-outline-secondary">
            &larr; Ficha del Propietario
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Especificaciones del Vehículo</h5>
                <a href="{{ route('maintenance-orders.create', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-sm btn-success fw-semibold">
                    + Registrar Mantenimiento
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Placa / Matrícula</small>
                        <span class="badge bg-dark text-uppercase fs-6 px-3 py-2">{{ $vehicle->plate }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Número de Chasis (VIN)</small>
                        <code>{{ $vehicle->vin ?? 'No registrado' }}</code>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Marca y Modelo</small>
                        <strong class="text-dark fs-6">{{ $vehicle->make }} {{ $vehicle->model }}</strong>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Año de Fabricación</small>
                        <span>{{ $vehicle->year ?? '—' }}</span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Color</small>
                        <span>{{ $vehicle->color ?? '—' }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Kilometraje Registrado</small>
                        <strong class="fs-6">{{ number_format($vehicle->current_mileage) }}</strong> km
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Tipo de Combustible</small>
                        <span>{{ $vehicle->fuel_type ? ucfirst($vehicle->fuel_type) : 'No especificado' }}</span>
                    </div>
                    @if($vehicle->notes)
                        <div class="col-12">
                            <small class="text-muted d-block">Notas Adicionales</small>
                            <p class="small text-secondary mb-0">{{ $vehicle->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Historial de Mantenimientos del Vehículo --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">
                    Historial de Mantenimientos
                    <span class="badge bg-primary ms-1">{{ $vehicle->maintenanceOrders->count() }}</span>
                </h5>
                <a href="{{ route('maintenance-orders.create', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-sm btn-outline-success">
                    + Registrar Servicio
                </a>
            </div>
            <div class="card-body p-0">
                @if($vehicle->maintenanceOrders->isEmpty())
                    <div class="text-center py-4">
                        <p class="text-muted small mb-2">Este vehículo no registra mantenimientos previos en el taller.</p>
                        <a href="{{ route('maintenance-orders.create', ['vehicle_id' => $vehicle->id]) }}" class="btn btn-sm btn-success">
                            Registrar Primer Mantenimiento
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-3">Orden / Fecha</th>
                                    <th scope="col">Servicio</th>
                                    <th scope="col">Kilometraje</th>
                                    <th scope="col">Mecánico</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="text-end pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicle->maintenanceOrders as $mOrder)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold">{{ $mOrder->order_number ?? '#' . $mOrder->id }}</div>
                                            <small class="text-muted">{{ $mOrder->service_date?->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $mOrder->service_name_snapshot }}</div>
                                            <span class="badge {{ $mOrder->maintenance_type === 'preventive' ? 'bg-success' : 'bg-warning text-dark' }} small">
                                                {{ $mOrder->maintenance_type === 'preventive' ? 'Preventivo' : 'Correctivo' }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($mOrder->current_mileage) }} km</td>
                                        <td>
                                            <small class="text-muted">{{ $mOrder->mechanic?->name ?? '—' }}</small>
                                        </td>
                                        <td>
                                            @if($mOrder->status === 'completed')
                                                <span class="badge bg-success">Completada</span>
                                            @elseif($mOrder->status === 'in_progress')
                                                <span class="badge bg-primary">En Progreso</span>
                                            @elseif($mOrder->status === 'cancelled')
                                                <span class="badge bg-danger">Cancelada</span>
                                            @else
                                                <span class="badge bg-secondary">Abierta</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('maintenance-orders.show', $mOrder) }}" class="btn btn-outline-secondary btn-sm">
                                                Ver
                                            </a>
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

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Propietario Asociado</h5>
            </div>
            <div class="card-body">
                @if($vehicle->customer)
                    <div class="mb-2">
                        <small class="text-muted d-block">Nombre del Propietario</small>
                        <strong class="text-dark fs-6">{{ $vehicle->customer->full_name }}</strong>
                    </div>
                    @if($vehicle->customer->phone)
                        <div class="mb-2">
                            <small class="text-muted d-block">Teléfono</small>
                            <span>{{ $vehicle->customer->phone }}</span>
                        </div>
                    @endif
                    @if($vehicle->customer->document_number)
                        <div class="mb-3">
                            <small class="text-muted d-block">Documento</small>
                            <span class="badge bg-light text-dark border">{{ strtoupper($vehicle->customer->document_type ?? 'DOC') }}: {{ $vehicle->customer->document_number }}</span>
                        </div>
                    @endif
                    <a href="{{ route('customers.show', $vehicle->customer) }}" class="btn btn-outline-primary btn-sm w-100">
                        Ver Ficha Completa del Propietario
                    </a>
                @else
                    <p class="text-muted small mb-0">Sin cliente asignado.</p>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Auditoría</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-1">ID Vehículo: <strong>#{{ $vehicle->id }}</strong></p>
                <p class="small text-muted mb-1">Empresa: <strong>{{ $vehicle->company?->name ?? '—' }}</strong></p>
                <p class="small text-muted mb-1">Registrado el: <strong>{{ $vehicle->created_at?->format('d/m/Y H:i') ?? '—' }}</strong></p>
                <p class="small text-muted mb-3">Última actualización: <strong>{{ $vehicle->updated_at?->format('d/m/Y H:i') ?? '—' }}</strong></p>

                <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" onsubmit="return confirm('¿Confirmas eliminar este vehículo?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        Eliminar Vehículo (Soft Delete)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
