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
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Especificaciones del Vehículo</h5>
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
