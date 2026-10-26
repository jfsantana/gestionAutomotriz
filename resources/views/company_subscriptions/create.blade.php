@extends('layouts.app')

@section('title', 'Asignar Suscripción a Empresa')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="company-subscriptions" class="h3 mb-0">Asignar Suscripción a Empresa</x-page-heading>
        <p class="text-muted mb-0 small">Asocia un plan SaaS y define las fechas de vigencia</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('company-subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('company-subscriptions.store') }}" novalidate>
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="company_id" class="form-label fw-semibold">Empresa / Taller <span class="text-danger">*</span></label>
                    <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                        <option value="">-- Seleccionar Empresa --</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" @selected((string)old('company_id', $subscription->company_id) === (string)$c->id)>
                                {{ $c->name }} (Estado actual: {{ $c->status }})
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="subscription_plan_id" class="form-label fw-semibold">Plan SaaS <span class="text-danger">*</span></label>
                    <select class="form-select @error('subscription_plan_id') is-invalid @enderror" id="subscription_plan_id" name="subscription_plan_id" required>
                        <option value="">-- Seleccionar Plan --</option>
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
                    <label for="status" class="form-label fw-semibold">Estado de la Suscripción <span class="text-danger">*</span></label>
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
                        value="{{ old('starts_at', $subscription->starts_at?->format('Y-m-d') ?? now()->toDateString()) }}" 
                        required
                    >
                    @error('starts_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="ends_at" class="form-label fw-semibold">Fecha de Vencimiento (Fin de acceso)</label>
                    <input 
                        type="date" 
                        class="form-control @error('ends_at') is-invalid @enderror" 
                        id="ends_at" 
                        name="ends_at" 
                        value="{{ old('ends_at', $subscription->ends_at?->format('Y-m-d')) }}"
                    >
                    <div class="form-text">Si se deja vacío, no tendrá límite de expiración.</div>
                    @error('ends_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="notes" class="form-label fw-semibold">Notas Administrativas</label>
                    <input 
                        type="text" 
                        class="form-control @error('notes') is-invalid @enderror" 
                        id="notes" 
                        name="notes" 
                        value="{{ old('notes', $subscription->notes) }}" 
                        placeholder="Ej. Pago por transferencia bancaria ref #12345"
                    >
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('company-subscriptions.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Suscripción</button>
            </div>
        </form>
    </div>
</div>
@endsection
