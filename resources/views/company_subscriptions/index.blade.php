@extends('layouts.app')

@section('title', 'Suscripciones por Empresa')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Suscripciones por Empresa</h1>
        <p class="text-muted mb-0 small">Control centralizado de planes, vigencia y acceso operativo de talleres</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('company-subscriptions.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Asignar Suscripción
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($companies->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay empresas registradas en el sistema.</p>
                <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">Registrar Primera Empresa</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Empresa / Taller</th>
                            <th scope="col">Plan Asignado</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Ciclo</th>
                            <th scope="col">Vigencia (Inicio / Fin)</th>
                            <th scope="col">Acceso</th>
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companies as $company)
                            @php
                                $sub = $company->currentSubscription;
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $company->name }}</div>
                                    <small class="text-muted">slug: <code>{{ $company->slug }}</code></small>
                                </td>
                                <td>
                                    @if($sub && $sub->plan)
                                        <span class="fw-semibold text-dark">{{ $sub->plan->name }}</span>
                                        <div><small class="text-muted">${{ number_format($sub->plan->monthly_price, 2) }}/mes</small></div>
                                    @else
                                        <span class="text-danger small">Sin plan asignado</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sub)
                                        @if($sub->status === 'active')
                                            <span class="badge bg-success">Activo</span>
                                        @elseif($sub->status === 'trial')
                                            <span class="badge bg-info text-dark">Prueba (Trial)</span>
                                        @elseif($sub->status === 'expired')
                                            <span class="badge bg-warning text-dark">Vencido</span>
                                        @elseif($sub->status === 'suspended')
                                            <span class="badge bg-danger">Suspendido</span>
                                        @else
                                            <span class="badge bg-secondary">Cancelado</span>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-dark border">Sin registro</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-uppercase text-secondary">{{ $sub?->billing_cycle ?? '—' }}</small>
                                </td>
                                <td>
                                    @if($sub)
                                        <small class="d-block text-dark">Desde: {{ $sub->starts_at?->format('d/m/Y') }}</small>
                                        <small class="d-block text-muted">
                                            Hasta: {{ $sub->ends_at ? $sub->ends_at->format('d/m/Y') : 'Sin límite' }}
                                            @if($sub->ends_at)
                                                @php $days = $sub->daysRemaining(); @endphp
                                                @if($days < 0)
                                                    <span class="text-danger">({{ abs($days) }}d vencidos)</span>
                                                @elseif($days <= 7)
                                                    <span class="text-warning text-dark fw-bold">({{ $days }}d restantes)</span>
                                                @else
                                                    <span>({{ $days }}d)</span>
                                                @endif
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sub && $sub->isOperable())
                                        <span class="badge bg-success small">Habilitado</span>
                                    @else
                                        <span class="badge bg-danger small">Bloqueado</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($sub)
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('company-subscriptions.edit', $sub) }}" class="btn btn-outline-primary" title="Editar suscripción">
                                                Editar
                                            </a>
                                            <form method="POST" action="{{ route('company-subscriptions.toggle-suspension', $sub) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                @if($sub->status === 'suspended')
                                                    <button type="submit" class="btn btn-outline-success" title="Reactivar acceso">Reactivar</button>
                                                @else
                                                    <button type="submit" class="btn btn-outline-danger" title="Suspender acceso">Suspender</button>
                                                @endif
                                            </form>
                                        </div>
                                    @else
                                        <a href="{{ route('company-subscriptions.create', ['company_id' => $company->id]) }}" class="btn btn-sm btn-outline-primary">
                                            Asignar Plan
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($companies->hasPages())
                <div class="p-3 border-top">{{ $companies->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
