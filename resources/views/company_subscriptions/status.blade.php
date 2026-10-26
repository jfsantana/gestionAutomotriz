@extends('layouts.app')

@section('title', 'Estado de Cuenta y Suscripción')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="company-subscriptions" class="h3 mb-0">Estado de Cuenta &bull; {{ $company->name }}</x-page-heading>
        <p class="text-muted mb-0 small">Detalles de tu suscripción SaaS y condiciones operativas</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Dashboard
        </a>
    </div>
</div>

@if(! $isOperable)
    <div class="alert alert-danger border-0 shadow-sm p-4 mb-4">
        <div class="d-flex align-items-center">
            <div>
                <h4 class="alert-heading fw-bold mb-1">&#9888; Acceso Operativo Bloqueado</h4>
                <p class="mb-2">
                    Tu suscripción se encuentra 
                    <strong>
                        @if($subscription?->status === 'expired') VENCIDA @elseif($subscription?->status === 'suspended') SUSPENDIDA @else INACTIVA @endif
                    </strong>.
                    Los módulos operativos de clientes, vehículos, servicios y órdenes están temporalmente deshabilitados.
                </p>
                <hr>
                <p class="mb-0 small">
                    Por favor, ponte en contacto con la administración central de <strong>AutoBitacora</strong> para renovar o reactivar tu cuenta.
                </p>
            </div>
        </div>
    </div>
@elseif($subscription && $subscription->daysRemaining() !== null && $subscription->daysRemaining() <= 7)
    <div class="alert alert-warning border-0 shadow-sm p-3 mb-4">
        <strong>Aviso de Vencimiento Próximo:</strong> 
        Tu suscripción vence en <strong>{{ $subscription->daysRemaining() }} día(s)</strong> ({{ $subscription->ends_at->format('d/m/Y') }}). 
        Comunícate con el soporte para renovar y evitar interrupciones en el servicio de tu taller.
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Detalles del Plan Actual</h5>
            </div>
            <div class="card-body">
                @if($subscription && $subscription->plan)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="text-primary fw-bold mb-0">{{ $subscription->plan->name }}</h4>
                            <small class="text-muted">{{ $subscription->plan->description ?? 'Plan para talleres y concesionarios' }}</small>
                        </div>
                        <span class="badge {{ $isOperable ? 'bg-success' : 'bg-danger' }} fs-6 px-3 py-2 text-uppercase">
                            {{ $subscription->status }}
                        </span>
                    </div>

                    <div class="row g-3 pt-2">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Precio Referencial</small>
                            <strong class="fs-6 text-dark">${{ number_format($subscription->plan->monthly_price, 2) }} / mes</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Ciclo Actual</small>
                            <span class="text-uppercase fw-semibold">{{ $subscription->billing_cycle }}</span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Fecha de Inicio</small>
                            <span>{{ $subscription->starts_at?->format('d/m/Y') }}</span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Fecha de Vencimiento</small>
                            <strong class="{{ (! $isOperable) ? 'text-danger' : 'text-dark' }}">
                                {{ $subscription->ends_at ? $subscription->ends_at->format('d/m/Y') : 'Ilimitado / Sin vencimiento' }}
                            </strong>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold text-dark mb-2">Cuotas y Límites Incluidos</h6>
                    <div class="row g-2 small text-secondary">
                        <div class="col-sm-6">
                            &bull; Máximo de Mecánicos: <strong>{{ $subscription->plan->max_mechanics ?? 'Ilimitado' }}</strong>
                        </div>
                        <div class="col-sm-6">
                            &bull; Órdenes Mensuales: <strong>{{ $subscription->plan->max_maintenance_orders_per_month ?? 'Ilimitado' }}</strong>
                        </div>
                        <div class="col-sm-6">
                            &bull; Vehículos Registrados: <strong>{{ $subscription->plan->max_vehicles ?? 'Ilimitado' }}</strong>
                        </div>
                        <div class="col-sm-6">
                            &bull; Usuarios Internos: <strong>{{ $subscription->plan->max_internal_users ?? 'Ilimitado' }}</strong>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">No hay una suscripción registrada para esta empresa.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Contacto y Soporte Administrativo</h5>
            </div>
            <div class="card-body">
                <p class="small text-secondary mb-3">
                    Para pagos manuales por transferencia, renovaciones de ciclo, upgrades de plan o reactivación de acceso, utiliza las siguientes vías de atención:
                </p>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2">
                        <strong>Correo Central:</strong> 
                        <a href="mailto:venecode.contact@gmail.com" class="text-decoration-none">venecode.contact@gmail.com</a>
                    </li>
                    <li class="mb-2">
                        <strong>WhatsApp / Teléfono:</strong> +58 (424) 438-0137
                    </li>
                    <li class="mb-0">
                        <strong>Horario de Atención:</strong> Lunes a Viernes de 8:00 AM a 6:00 PM
                    </li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Datos de la Empresa</h5>
            </div>
            <div class="card-body small">
                <p class="mb-1">Razón Social: <strong>{{ $company->name }}</strong></p>
                <p class="mb-1">Identificador: <code>{{ $company->slug }}</code></p>
                <p class="mb-1">RNC / Tax ID: {{ $company->tax_id ?? '—' }}</p>
                <p class="mb-0">Teléfono: {{ $company->phone ?? '—' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
