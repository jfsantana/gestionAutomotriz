@extends('layouts.app')

@section('title', 'Cliente: ' . $customer->full_name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">{{ $customer->full_name }}</h1>
        <p class="text-muted mb-0 small">Ficha del cliente y gestión de vehículos asignados</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('customers.vehicles.create', $customer) }}" class="btn btn-sm btn-success fw-semibold">
            + Agregar Vehículo
        </a>
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">
            Editar Cliente
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver a Clientes
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Datos del Cliente</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Tipo de Cliente</small>
                    @if($customer->customer_type === 'fleet_owner')
                        <span class="badge bg-info text-dark">Propietario de Flota</span>
                    @else
                        <span class="badge bg-secondary">Particular (Individual)</span>
                    @endif
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Documento de Identidad</small>
                    <strong>{{ $customer->document_number ? strtoupper($customer->document_type ?? 'DOC') . ': ' . $customer->document_number : 'No registrado' }}</strong>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Teléfono / Celular</small>
                    <span>{{ $customer->phone ?? 'No registrado' }}</span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Correo Electrónico</small>
                    <span>{{ $customer->email ?? 'No registrado' }}</span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Empresa / Taller</small>
                    <span>{{ $customer->company?->name ?? '—' }}</span>
                </div>

                @if($customer->notes)
                    <div class="mb-3">
                        <small class="text-muted d-block">Notas</small>
                        <p class="small text-secondary mb-0">{{ $customer->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">
                    Vehículos Registrados 
                    <span class="badge bg-primary ms-1">{{ $customer->vehicles->count() }}</span>
                </h5>
                <a href="{{ route('customers.vehicles.create', $customer) }}" class="btn btn-sm btn-outline-success">
                    + Agregar Vehículo
                </a>
            </div>
            <div class="card-body p-0">
                @if($customer->vehicles->isEmpty())
                    <div class="text-center py-5">
                        <p class="text-muted mb-3">Este cliente aún no tiene vehículos registrados.</p>
                        <a href="{{ route('customers.vehicles.create', $customer) }}" class="btn btn-success btn-sm">
                            Registrar el Primer Vehículo
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-3">Placa</th>
                                    <th scope="col">Vehículo</th>
                                    <th scope="col">Año / Color</th>
                                    <th scope="col">Kilometraje</th>
                                    <th scope="col">Combustible</th>
                                    <th scope="col" class="text-end pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->vehicles as $vehicle)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-dark text-uppercase px-2 py-1 fs-6">{{ $vehicle->plate }}</span>
                                            @if($vehicle->vin)
                                                <div><small class="text-muted">VIN: {{ $vehicle->vin }}</small></div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                                        </td>
                                        <td>
                                            {{ $vehicle->year ?? '—' }}
                                            @if($vehicle->color)
                                                <small class="text-muted">({{ $vehicle->color }})</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ number_format($vehicle->current_mileage) }}</strong> km
                                        </td>
                                        <td>
                                            <span class="text-secondary small">{{ $vehicle->fuel_type ? ucfirst($vehicle->fuel_type) : '—' }}</span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                                    Ver
                                                </a>
                                                <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-outline-primary" title="Editar">
                                                    Editar
                                                </a>
                                            </div>
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
