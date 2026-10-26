@extends('layouts.app')

@section('title', 'Planes de Suscripción SaaS')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center gap-2 pt-2 pb-2 mb-4 border-bottom">
    <div class="text-break">
        <h1 class="h3 mb-0">Planes de Suscripción SaaS</h1>
        <p class="text-muted mb-0 small">Configuración comercial de planes y límites para talleres y concesionarios</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('subscription-plans.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Nuevo Plan
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($plans->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay planes registrados todavía.</p>
                <a href="{{ route('subscription-plans.create') }}" class="btn btn-primary btn-sm">Crear Primer Plan</a>
            </div>
        @else
            <ul class="d-md-none list-unstyled d-flex flex-column gap-2 p-2 mb-0 bg-body-tertiary" aria-label="Planes de Suscripción SaaS">
                @foreach($plans as $plan)
                    <li class="card border p-2 text-break">
                        <h2 class="h6 fw-bold mb-1">{{ $plan->name }}</h2>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            @if($plan->is_trial)
                                <span class="badge bg-info text-dark text-wrap">Prueba ({{ $plan->duration_days ?? 15 }} días)</span>
                            @else
                                <span class="badge bg-primary">Comercial</span>
                            @endif
                            @if($plan->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                            <span class="badge bg-light text-dark text-wrap border">{{ $plan->subscriptions_count }} suscripciones</span>
                        </div>
                        <div class="small">
                            <div><span class="text-muted">slug:</span> <code>{{ $plan->slug }}</code></div>
                            <div class="d-flex flex-wrap gap-2 my-1">
                                <span><strong>${{ number_format($plan->monthly_price, 2) }}</strong> <span class="text-muted">/mes</span></span>
                                @if($plan->yearly_price > 0)
                                    <span class="text-secondary">${{ number_format($plan->yearly_price, 2) }} /año</span>
                                @endif
                            </div>
                            <div><span class="text-muted">Mecánicos:</span> <strong>{{ $plan->max_mechanics ?? 'Ilimitado' }}</strong></div>
                            <div><span class="text-muted">Órdenes/mes:</span> <strong>{{ $plan->max_maintenance_orders_per_month ?? 'Ilimitado' }}</strong></div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('subscription-plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary flex-fill py-2" aria-label="Editar plan {{ $plan->name }}">
                                Editar
                            </a>
                            <form method="POST" action="{{ route('subscription-plans.toggle-status', $plan) }}" class="flex-fill">
                                @csrf
                                @method('PATCH')
                                @if($plan->is_active)
                                    <button type="submit" class="btn btn-sm btn-outline-warning w-100 py-2" aria-label="Desactivar plan {{ $plan->name }}">
                                        Desactivar
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sm btn-outline-success w-100 py-2" aria-label="Activar plan {{ $plan->name }}">
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
                            <th scope="col" class="ps-4">Plan</th>
                            <th scope="col">Precio Mensual / Anual</th>
                            <th scope="col">Límites Sugeridos</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Empresas</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $plan->name }}</div>
                                    <small class="text-muted">slug: <code>{{ $plan->slug }}</code></small>
                                </td>
                                <td>
                                    <strong>${{ number_format($plan->monthly_price, 2) }}</strong> <small class="text-muted">/mes</small>
                                    @if($plan->yearly_price > 0)
                                        <div><small class="text-secondary">${{ number_format($plan->yearly_price, 2) }} /año</small></div>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-secondary d-block">
                                        Mecánicos: <strong>{{ $plan->max_mechanics ?? 'Ilimitado' }}</strong> |
                                        Órdenes/mes: <strong>{{ $plan->max_maintenance_orders_per_month ?? 'Ilimitado' }}</strong>
                                    </small>
                                </td>
                                <td>
                                    @if($plan->is_trial)
                                        <span class="badge bg-info text-dark">Prueba ({{ $plan->duration_days ?? 15 }} días)</span>
                                    @else
                                        <span class="badge bg-primary">Comercial</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $plan->subscriptions_count }} suscripciones</span>
                                </td>
                                <td>
                                    @if($plan->is_active)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('subscription-plans.edit', $plan) }}" class="btn btn-outline-primary">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('subscription-plans.toggle-status', $plan) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            @if($plan->is_active)
                                                <button type="submit" class="btn btn-outline-warning" title="Desactivar">Desactivar</button>
                                            @else
                                                <button type="submit" class="btn btn-outline-success" title="Activar">Activar</button>
                                            @endif
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($plans->hasPages())
                <div class="p-3 border-top">{{ $plans->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
