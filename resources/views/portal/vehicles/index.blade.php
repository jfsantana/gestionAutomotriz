@extends('portal.layouts.client')

@section('title', 'Mis Vehículos')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
    <div>
        <x-page-heading section="vehicles" class="h4 mb-0 fw-bold text-dark">Mis Vehículos</x-page-heading>
        <p class="text-muted mb-0 small">Consulta los vehículos registrados a tu nombre</p>
    </div>
</div>

@if($vehicles->isEmpty())
    <div class="card shadow-sm border-0 p-5 text-center">
        <span class="fs-1 text-muted mb-2">&#128663;</span>
        <h5 class="fw-bold text-dark">No tienes vehículos activos</h5>
        <p class="text-secondary small mb-0">Comunícate con tu taller o concesionario para vincular un vehículo a tu cuenta.</p>
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
                    <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                        <a href="{{ route('portal.vehicles.show', $veh) }}" class="btn btn-sm btn-primary">
                            Ver Ficha & Historial
                        </a>
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
    </div>
@endif
@endsection
