@extends('layouts.app')

@section('title', 'Órdenes de Mantenimiento')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center gap-2 pt-2 pb-2 mb-4 border-bottom">
    <div class="text-break">
        <h1 class="h3 mb-0">Órdenes de Mantenimiento</h1>
        <p class="text-muted mb-0 small">
            @if(auth()->user()->role === 'company_admin')
                Registro y trazabilidad de servicios de <strong>{{ auth()->user()->company?->name }}</strong>
            @else
                Gestión operativa de órdenes de mantenimiento
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('maintenance-orders.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Nueva Orden
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($orders->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay órdenes de mantenimiento registradas aún.</p>
                <a href="{{ route('maintenance-orders.create') }}" class="btn btn-primary btn-sm">Crear Primera Orden</a>
            </div>
        @else
            <ul class="d-md-none list-unstyled d-flex flex-column gap-2 p-2 mb-0 bg-body-tertiary" aria-label="Órdenes de Mantenimiento">
                @foreach($orders as $ord)
                    <li class="card border p-2 text-break">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1 small">
                            <strong>{{ $ord->order_number ?? '#' . $ord->id }}</strong>
                            <span class="text-muted">{{ $ord->service_date?->format('d/m/Y') }}</span>
                        </div>
                        <h2 class="h6 fw-bold mb-1">{{ $ord->service_name_snapshot }}</h2>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge {{ $ord->maintenance_type === 'preventive' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $ord->maintenance_type === 'preventive' ? 'Preventivo' : 'Correctivo' }}
                            </span>
                            @if($ord->status === 'completed')
                                <span class="badge bg-success">Completada</span>
                            @elseif($ord->status === 'in_progress')
                                <span class="badge bg-primary">En Progreso</span>
                            @elseif($ord->status === 'cancelled')
                                <span class="badge bg-danger">Cancelada</span>
                            @else
                                <span class="badge bg-secondary">Abierta</span>
                            @endif
                        </div>
                        <div class="small">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="badge bg-dark text-uppercase text-wrap px-2 py-1">{{ $ord->vehicle?->plate }}</span>
                                <span class="text-secondary">{{ $ord->vehicle?->make }} {{ $ord->vehicle?->model }}</span>
                            </div>
                            <div><span class="text-muted">Cliente:</span> <span class="fw-semibold">{{ $ord->customer?->full_name }}</span></div>
                            @if($ord->customer?->phone)
                                <div><span class="text-muted">Tel.:</span> {{ $ord->customer->phone }}</div>
                            @endif
                            <div class="my-1">
                                @if($ord->origin === 'customer')
                                    <span class="badge text-wrap bg-warning text-dark border border-warning-subtle" title="Mantenimiento gestionado fuera del taller/concesionario por el propio cliente">
                                        &#9888; Cliente (Fuera de taller)
                                    </span>
                                    <small class="d-block text-muted">No verificado por taller</small>
                                @else
                                    <span class="badge text-wrap bg-success-subtle text-success border border-success-subtle">
                                        &#10003; Realizado en Taller
                                    </span>
                                @endif
                            </div>
                            <div>
                                <span class="text-muted">Mecánico:</span>
                                @if($ord->mechanic)
                                    {{ $ord->mechanic->name }}
                                @elseif($ord->origin === 'customer')
                                    Externo / Particular
                                @else
                                    —
                                @endif
                            </div>
                            <div>
                                <span class="text-muted">Costo:</span>
                                @if($ord->cost !== null)
                                    <strong>${{ number_format($ord->cost, 2) }}</strong>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('maintenance-orders.show', $ord) }}" class="btn btn-sm btn-outline-secondary flex-fill py-2" aria-label="Ver orden {{ $ord->order_number ?? '#' . $ord->id }}">
                                Ver
                            </a>
                            <a href="{{ route('maintenance-orders.edit', $ord) }}" class="btn btn-sm btn-outline-primary flex-fill py-2" aria-label="Editar orden {{ $ord->order_number ?? '#' . $ord->id }}">
                                Editar
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">No. Orden / Fecha</th>
                            <th scope="col">Vehículo</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Servicio Realizado</th>
                            <th scope="col">Origen / Realizado por</th>
                            <th scope="col">Mecánico</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Costo</th>
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $ord)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $ord->order_number ?? '#' . $ord->id }}</div>
                                    <small class="text-muted">{{ $ord->service_date?->format('d/m/Y') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-dark text-uppercase px-2 py-1">{{ $ord->vehicle?->plate }}</span>
                                    <small class="d-block text-secondary">{{ $ord->vehicle?->make }} {{ $ord->vehicle?->model }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $ord->customer?->full_name }}</div>
                                    <small class="text-muted">{{ $ord->customer?->phone }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $ord->service_name_snapshot }}</div>
                                    <span class="badge {{ $ord->maintenance_type === 'preventive' ? 'bg-success' : 'bg-warning text-dark' }} small">
                                        {{ $ord->maintenance_type === 'preventive' ? 'Preventivo' : 'Correctivo' }}
                                    </span>
                                </td>
                                <td>
                                    @if($ord->origin === 'customer')
                                        <span class="badge bg-warning text-dark border border-warning-subtle" title="Mantenimiento gestionado fuera del taller/concesionario por el propio cliente">
                                            &#9888; Cliente (Fuera de taller)
                                        </span>
                                        <div><small class="text-muted" style="font-size: 0.72rem;">No verificado por taller</small></div>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            &#10003; Realizado en Taller
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($ord->mechanic)
                                        <span class="badge bg-light text-dark border">{{ $ord->mechanic->name }}</span>
                                    @elseif($ord->origin === 'customer')
                                        <span class="text-muted small">Externo / Particular</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ord->status === 'completed')
                                        <span class="badge bg-success">Completada</span>
                                    @elseif($ord->status === 'in_progress')
                                        <span class="badge bg-primary">En Progreso</span>
                                    @elseif($ord->status === 'cancelled')
                                        <span class="badge bg-danger">Cancelada</span>
                                    @else
                                        <span class="badge bg-secondary">Abierta</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ord->cost !== null)
                                        <strong>${{ number_format($ord->cost, 2) }}</strong>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('maintenance-orders.show', $ord) }}" class="btn btn-outline-secondary">
                                            Ver
                                        </a>
                                        <a href="{{ route('maintenance-orders.edit', $ord) }}" class="btn btn-outline-primary">
                                            Editar
                                        </a>
                                    </div>
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
