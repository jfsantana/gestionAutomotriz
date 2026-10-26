@extends('layouts.app')

@section('title', 'Catálogo de Servicios')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center gap-2 pt-2 pb-2 mb-4 border-bottom">
    <div class="text-break">
        <h1 class="h3 mb-0">Catálogo de Servicios</h1>
        <p class="text-muted mb-0 small">
            @if(auth()->user()->role === 'company_admin')
                Mantenimientos preventivos y correctivos de <strong>{{ auth()->user()->company?->name }}</strong>
            @else
                Catálogo global de servicios multiempresa
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('services.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Nuevo Servicio
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($services->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay servicios registrados en este catálogo aún.</p>
                <a href="{{ route('services.create') }}" class="btn btn-primary btn-sm">Crear Primer Servicio</a>
            </div>
        @else
            <ul class="d-md-none list-unstyled d-flex flex-column gap-2 p-2 mb-0 bg-body-tertiary" aria-label="Catálogo de Servicios">
                @foreach($services as $item)
                    <li class="card border p-2 text-break">
                        <h2 class="h6 fw-bold mb-1">{{ $item->name }}</h2>
                        @if($item->description)
                            <p class="small text-muted mb-2">{{ Str::limit($item->description, 60) }}</p>
                        @endif
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            @if($item->maintenance_type === 'preventive')
                                <span class="badge bg-success">Preventivo</span>
                            @else
                                <span class="badge bg-warning text-dark">Correctivo</span>
                            @endif
                            @if($item->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </div>
                        <div class="small">
                            <div>
                                <span class="text-muted">Control sugerido:</span>
                                {{ $item->default_interval_days ? $item->default_interval_days . ' días' : '' }}
                                {{ $item->default_interval_days && $item->default_interval_mileage ? ' / ' : '' }}
                                {{ $item->default_interval_mileage ? number_format($item->default_interval_mileage) . ' km' : '' }}
                                @if(!$item->default_interval_days && !$item->default_interval_mileage)
                                    A criterio de uso
                                @endif
                            </div>
                            @if(auth()->user()->role === 'super_admin')
                                <div><span class="text-muted">Empresa:</span> {{ $item->company?->name ?? '—' }}</div>
                            @endif
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('services.edit', $item) }}" class="btn btn-sm btn-outline-primary flex-fill py-2" aria-label="Editar servicio {{ $item->name }}">
                                Editar
                            </a>
                            <form method="POST" action="{{ route('services.toggle-status', $item) }}" class="flex-fill">
                                @csrf
                                @method('PATCH')
                                @if($item->is_active)
                                    <button type="submit" class="btn btn-sm btn-outline-warning w-100 py-2" aria-label="Desactivar servicio {{ $item->name }}">
                                        Desactivar
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sm btn-outline-success w-100 py-2" aria-label="Activar servicio {{ $item->name }}">
                                        Activar
                                    </button>
                                @endif
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Servicio</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Intervalo Sugerido de Control</th>
                            @if(auth()->user()->role === 'super_admin')
                                <th scope="col">Empresa</th>
                            @endif
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($services as $item)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    @if($item->description)
                                        <small class="text-muted">{{ Str::limit($item->description, 60) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($item->maintenance_type === 'preventive')
                                        <span class="badge bg-success">Preventivo</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Correctivo</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="d-block text-secondary">
                                        {{ $item->default_interval_days ? $item->default_interval_days . ' días' : '' }}
                                        {{ $item->default_interval_days && $item->default_interval_mileage ? ' / ' : '' }}
                                        {{ $item->default_interval_mileage ? number_format($item->default_interval_mileage) . ' km' : '' }}
                                        @if(!$item->default_interval_days && !$item->default_interval_mileage)
                                            <span class="text-muted">A criterio de uso</span>
                                        @endif
                                    </small>
                                </td>
                                @if(auth()->user()->role === 'super_admin')
                                    <td>
                                        <small class="text-muted">{{ $item->company?->name ?? '—' }}</small>
                                    </td>
                                @endif
                                <td>
                                    @if($item->is_active)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('services.edit', $item) }}" class="btn btn-outline-primary">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('services.toggle-status', $item) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            @if($item->is_active)
                                                <button type="submit" class="btn btn-outline-warning" title="Desactivar">
                                                    Desactivar
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-outline-success" title="Activar">
                                                    Activar
                                                </button>
                                            @endif
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($services->hasPages())
                <div class="p-3 border-top">
                    {{ $services->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
