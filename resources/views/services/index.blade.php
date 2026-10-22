@extends('layouts.app')

@section('title', 'Catálogo de Servicios')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
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
            <div class="table-responsive">
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
