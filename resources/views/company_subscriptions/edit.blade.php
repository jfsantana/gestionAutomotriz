@extends('layouts.app')

@section('title', 'Editar Suscripción: ' . $subscription->company?->name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Suscripción: {{ $subscription->company?->name }}</h1>
        <p class="text-muted mb-0 small">Modifica fechas, plan, estado comercial y consulta la auditoría</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        {{-- Acción rápida de renovación --}}
        <form method="POST" action="{{ route('company-subscriptions.renew', $subscription) }}" class="d-inline">
            @csrf
            <input type="hidden" name="months" value="1">
            <button type="submit" class="btn btn-sm btn-success fw-semibold">
                &#8635; Renovar +1 Mes
            </button>
        </form>
        <a href="{{ route('company-subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Editar Datos de Suscripción</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('company-subscriptions.update', $subscription) }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted">Empresa</label>
                            <input type="text" class="form-control bg-light" value="{{ $subscription->company?->name }}" disabled>
                        </div>

                        <div class="col-md-6">
                            <label for="subscription_plan_id" class="form-label fw-semibold">Plan SaaS <span class="text-danger">*</span></label>
                            <select class="form-select @error('subscription_plan_id') is-invalid @enderror" id="subscription_plan_id" name="subscription_plan_id" required>
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}" @selected((string)old('subscription_plan_id', $subscription->subscription_plan_id) === (string)$p->id)>
                                        {{ $p->name }} (${{ number_format($p->monthly_price, 2) }}/mes)
                                    </option>
                                @endforeach
                            </select>
                            @error('subscription_plan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="trial" @selected(old('status', $subscription->status) === 'trial')>Prueba (Trial)</option>
                                <option value="active" @selected(old('status', $subscription->status) === 'active')>Activo (Active)</option>
                                <option value="expired" @selected(old('status', $subscription->status) === 'expired')>Vencido (Expired)</option>
                                <option value="suspended" @selected(old('status', $subscription->status) === 'suspended')>Suspendido (Suspended)</option>
                                <option value="cancelled" @selected(old('status', $subscription->status) === 'cancelled')>Cancelado (Cancelled)</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="billing_cycle" class="form-label fw-semibold">Ciclo de Facturación <span class="text-danger">*</span></label>
                            <select class="form-select @error('billing_cycle') is-invalid @enderror" id="billing_cycle" name="billing_cycle" required>
                                <option value="monthly" @selected(old('billing_cycle', $subscription->billing_cycle) === 'monthly')>Mensual</option>
                                <option value="yearly" @selected(old('billing_cycle', $subscription->billing_cycle) === 'yearly')>Anual</option>
                                <option value="trial" @selected(old('billing_cycle', $subscription->billing_cycle) === 'trial')>Prueba (Trial)</option>
                                <option value="custom" @selected(old('billing_cycle', $subscription->billing_cycle) === 'custom')>Personalizado</option>
                            </select>
                            @error('billing_cycle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="starts_at" class="form-label fw-semibold">Fecha de Inicio <span class="text-danger">*</span></label>
                            <input 
                                type="date" 
                                class="form-control @error('starts_at') is-invalid @enderror" 
                                id="starts_at" 
                                name="starts_at" 
                                value="{{ old('starts_at', $subscription->starts_at?->format('Y-m-d')) }}" 
                                required
                            >
                            @error('starts_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="ends_at" class="form-label fw-semibold">Fecha de Vencimiento</label>
                            <input 
                                type="date" 
                                class="form-control @error('ends_at') is-invalid @enderror" 
                                id="ends_at" 
                                name="ends_at" 
                                value="{{ old('ends_at', $subscription->ends_at?->format('Y-m-d')) }}"
                            >
                            @error('ends_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="notes" class="form-label fw-semibold">Notas / Motivo de Cambio</label>
                            <input 
                                type="text" 
                                class="form-control @error('notes') is-invalid @enderror" 
                                id="notes" 
                                name="notes" 
                                value="{{ old('notes', $subscription->notes) }}" 
                                placeholder="Ej. Renovación extendida o cambio de paquete"
                            >
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="{{ route('company-subscriptions.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">Actualizar Suscripción</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Panel Lateral de Historial y Auditoría --}}
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Estado de Acceso</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    Acceso Operativo: 
                    @if($subscription->isOperable())
                        <span class="badge bg-success">Habilitado</span>
                    @else
                        <span class="badge bg-danger">Bloqueado</span>
                    @endif
                </p>
                @if($subscription->ends_at)
                    @php $days = $subscription->daysRemaining(); @endphp
                    <p class="small text-muted mb-0">
                        @if($days < 0)
                            Venció hace <strong class="text-danger">{{ abs($days) }} días</strong>.
                        @else
                            Le restan <strong>{{ $days }} días</strong> de vigencia.
                        @endif
                    </p>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Historial de Transiciones</h5>
            </div>
            <div class="card-body p-0">
                @if($subscription->logs->isEmpty())
                    <p class="text-muted small p-3 mb-0">No hay registros de cambios en el historial.</p>
                @else
                    <ul class="list-group list-group-flush small">
                        @foreach($subscription->logs as $log)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-dark">{{ ucfirst($log->action) }}</strong>
                                    <span class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="text-secondary">
                                    {{ $log->previous_status ?? 'Nuevo' }} &rarr; <strong>{{ $log->new_status }}</strong>
                                </div>
                                @if($log->performer)
                                    <small class="text-muted d-block">Por: {{ $log->performer->username }}</small>
                                @endif
                                @if($log->notes)
                                    <small class="text-muted fst-italic d-block">{{ $log->notes }}</small>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
