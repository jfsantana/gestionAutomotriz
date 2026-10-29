@extends('portal.layouts.client')

@section('title', 'Mis Vehículos')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
    <div>
        <x-page-heading section="vehicles" class="h4 mb-0 fw-bold text-dark">Mis Vehículos</x-page-heading>
        <p class="text-muted mb-0 small">Consulta los vehículos registrados a tu nombre</p>
    </div>
    @if($canAddVehicle)
        <a href="{{ route('portal.vehicles.create') }}" class="btn btn-sm btn-primary fw-semibold">+ Registrar vehículo</a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if(session('info'))
    <div class="alert alert-info" role="status">{{ session('info') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($vehicles->isEmpty() && $hidden_vehicles_count === 0)
    <div class="card shadow-sm border-0 p-5 text-center">
        <span class="fs-1 text-muted mb-2">&#128663;</span>
        <h5 class="fw-bold text-dark">No tienes vehículos activos</h5>
        <p class="text-secondary small mb-3">Puedes registrar tus vehículos directamente o aceptar una invitación de un taller.</p>
        @if($canAddVehicle)
            <a href="{{ route('portal.vehicles.create') }}" class="btn btn-primary btn-sm">Registrar mi primer vehículo</a>
        @endif
    </div>
@else
    <div class="row g-4">
        @foreach($vehicles as $veh)
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100 overflow-hidden">
                    @if($veh->photo_path)
                        <img src="{{ asset('storage/' . $veh->photo_path) }}" alt="{{ $veh->make }}" class="card-img-top" style="height: 180px; object-fit: cover;">
                    @else
                        <div class="bg-light text-center py-5 border-bottom text-muted">
                            <span class="fs-1 d-block">&#128663;</span>
                            <small>Sin fotografía</small>
                        </div>
                    @endif
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold text-dark mb-0">{{ $veh->make }} {{ $veh->model }}</h5>
                            <span class="badge bg-dark text-uppercase fs-6">{{ $veh->plate }}</span>
                        </div>
                        <ul class="list-unstyled small text-secondary mb-3">
                            <li>Año: <strong>{{ $veh->year ?? '—' }}</strong> | Color: <strong>{{ $veh->color ?? '—' }}</strong></li>
                            <li>Kilometraje: <strong>{{ number_format($veh->current_mileage) }} km</strong></li>
                            <li>Mantenimientos: <strong>{{ $veh->maintenance_orders_count }} registros</strong></li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center gap-2">
                        <div class="d-flex gap-2">
                            <a href="{{ route('portal.vehicles.show', $veh) }}" class="btn btn-sm btn-primary">
                                Ver Ficha & Historial
                            </a>
                            <a href="{{ route('portal.vehicles.edit', $veh) }}" class="btn btn-sm btn-outline-primary">
                                Editar
                            </a>
                        </div>
                        {{-- Botón Dar de Baja por el Cliente con confirmación --}}
                        <form method="POST" action="{{ route('portal.vehicles.decommission', $veh) }}" onsubmit="return confirm('¿ATENCIÓN: Confirmas dar de baja este vehículo de tu cuenta? Se desvinculará de tu perfil y se limpiará su historial visible para evitar transferencia de datos a futuros compradores.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Dar de baja vehículo">
                                Dar de Baja
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach

        @if(! $customer->isFleetPremiumActive())
            <div class="col-12">
                @include('portal.vehicles._upgrade_card')
            </div>
        @endif
    </div>
@endif
@endsection
