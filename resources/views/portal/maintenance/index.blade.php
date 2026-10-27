@extends('portal.layouts.client')

@section('title', 'Historial de Mantenimientos')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
    <div>
        <x-page-heading section="maintenance-orders" class="h4 mb-0 fw-bold text-dark">Historial de Mantenimientos</x-page-heading>
        <p class="text-muted mb-0 small">Consulta los servicios registrados por el taller y tus registros personales</p>
    </div>
    <div class="btn-toolbar gap-2">
        <a href="{{ route('portal.maintenance.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Registrar Mantenimiento Propio
        </a>
    </div>
</div>

{{-- PESTAÑAS DE FILTRO --}}
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('portal.maintenance.index', ['tab' => 'all']) }}">
            Todos los Registros
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'workshop' ? 'active' : '' }}" href="{{ route('portal.maintenance.index', ['tab' => 'workshop']) }}">
            &#10003; Realizados por Taller
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'personal' ? 'active' : '' }}" href="{{ route('portal.maintenance.index', ['tab' => 'personal']) }}">
            👤 Mis Registros Personales
        </a>
    </li>
</ul>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($orders->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3 small">No se encontraron registros de mantenimiento en esta categoría.</p>
                <a href="{{ route('portal.maintenance.create') }}" class="btn btn-sm btn-primary">Registrar Mantenimiento Personal</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Servicio / Fecha</th>
                            <th scope="col">Vehículo</th>
                            <th scope="col">Kilometraje</th>
                            <th scope="col">Origen del Registro</th>
                            <th scope="col">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $ord)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $ord->service_name_snapshot }}</div>
                                    <small class="text-muted">{{ $ord->service_date?->format('d/m/Y') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-dark text-uppercase px-2 py-1">{{ $ord->vehicle?->plate }}</span>
                                    <small class="d-block text-secondary">{{ $ord->vehicle?->make }} {{ $ord->vehicle?->model }}</small>
                                </td>
                                <td>
                                    <strong>{{ number_format($ord->current_mileage) }}</strong> km
                                </td>
                                <td>
                                    @if($ord->origin === 'workshop')
                                        <span class="badge bg-success">&#10003; Verificado por Taller</span>
                                        @if($ord->order_number)
                                            <div><small class="text-muted">{{ $ord->order_number }}</small></div>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">👤 Registro Personal</span>
                                        <div><small class="text-muted">No verificado por taller</small></div>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-secondary d-block" style="max-width: 280px;">
                                        {{ $ord->customer_notes ?: $ord->technical_notes ?: 'Sin observaciones' }}
                                    </small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="p-3 border-top">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
