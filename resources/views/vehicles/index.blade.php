@extends('layouts.app')

@section('title', 'Vehículos Registrados')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Inventario de Vehículos</h1>
        <p class="text-muted mb-0 small">
            @if(auth()->user()->role === 'company_admin')
                Vehículos de clientes registrados en <strong>{{ auth()->user()->company?->name }}</strong>
            @else
                Vehículos registrados en la plataforma SaaS
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-primary me-2">
            Ver Clientes
        </a>
        <a href="{{ route('customers.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Nuevo Cliente y Vehículo
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($vehicles->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay vehículos registrados en este taller aún.</p>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Registrar Cliente y Vehículo</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Placa</th>
                            <th scope="col">Vehículo</th>
                            <th scope="col">Propietario / Cliente</th>
                            <th scope="col">Año / Color</th>
                            <th scope="col">Kilometraje</th>
                            @if(auth()->user()->role === 'super_admin')
                                <th scope="col">Empresa</th>
                            @endif
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicles as $vehicle)
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark text-uppercase px-2 py-1 fs-6">{{ $vehicle->plate }}</span>
                                    @if($vehicle->vin)
                                        <div><small class="text-muted">VIN: {{ $vehicle->vin }}</small></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($vehicle->photo_path)
                                            <img src="{{ asset('storage/' . $vehicle->photo_path) }}" alt="Foto" width="48" height="36" class="rounded me-2 border object-fit-cover shadow-sm">
                                        @endif
                                        <div>
                                            <div class="fw-bold text-dark">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                                            <small class="text-secondary">{{ $vehicle->fuel_type ? ucfirst($vehicle->fuel_type) : '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($vehicle->customer)
                                        <a href="{{ route('customers.show', $vehicle->customer) }}" class="text-decoration-none fw-semibold">
                                            {{ $vehicle->customer->full_name }}
                                        </a>
                                        @if($vehicle->customer->phone)
                                            <div><small class="text-muted">{{ $vehicle->customer->phone }}</small></div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
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
                                @if(auth()->user()->role === 'super_admin')
                                    <td>
                                        <small class="text-muted">{{ $vehicle->company?->name ?? '—' }}</small>
                                    </td>
                                @endif
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-outline-secondary">
                                            Ver
                                        </a>
                                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-outline-primary">
                                            Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($vehicles->hasPages())
                <div class="p-3 border-top">
                    {{ $vehicles->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
