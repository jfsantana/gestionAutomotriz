@extends('layouts.app')

@section('title', 'Pagos y Solicitudes')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center gap-2 pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="client-subscriptions" class="h3 mb-0">Pagos de Clientes y Talleres</x-page-heading>
        <p class="text-muted mb-0 small">Solicitudes de pago de clientes finales y renovaciones de talleres/concesionarios. Confirma manualmente solo después de recibir el pago.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="p-3 border-bottom">
            <h2 class="h5 mb-0">Clientes finales</h2>
        </div>
        @if($payments->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-0">No hay solicitudes de pago registradas.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo</th>
                            <th>Cliente</th>
                            <th>Vehículos y tarifa</th>
                            <th>Periodo</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td><span class="badge bg-info text-dark">Cliente</span></td>
                                <td>
                                    <strong>{{ $payment->customer?->full_name ?? 'Cliente eliminado' }}</strong>
                                    <small class="d-block text-muted">{{ $payment->customer_type === 'fleet_owner' ? 'Propietario de Flota' : 'Particular' }}</small>
                                    <small class="d-block text-muted">{{ $payment->customer?->user?->email ?? $payment->customer?->email ?? 'Sin correo registrado' }}</small>
                                </td>
                                <td>
                                    <strong>{{ $payment->vehicle_count }} vehículos</strong>
                                    @foreach($payment->pricing_breakdown['vehicles'] as $type => $detail)
                                        <small class="d-block text-muted">{{ ucfirst(str_replace('_', ' ', $type)) }}: {{ $detail['count'] }} × USD {{ $detail['monthly_unit_price'] }}</small>
                                    @endforeach
                                </td>
                                <td>{{ $payment->billing_cycle === 'annual' ? 'Anual (11 meses cobrados)' : 'Mensual' }}</td>
                                <td><strong>USD {{ $payment->amount }}</strong></td>
                                <td>
                                    @if($payment->status === 'requested')
                                        <span class="badge bg-warning text-dark">Solicitud</span>
                                    @elseif($payment->status === 'paid')
                                        <span class="badge bg-success">Pagado</span>
                                    @elseif($payment->status === 'reversed')
                                        <span class="badge bg-danger">Revertido</span>
                                    @elseif($payment->status === 'superseded')
                                        <span class="badge bg-secondary">Reemplazado</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                    @if($payment->confirmer)
                                        <small class="d-block text-muted">Confirmó: {{ $payment->confirmer->name }}</small>
                                    @endif
                                    @if($payment->reverser)
                                        <small class="d-block text-muted">Revirtió: {{ $payment->reverser->name }}</small>
                                    @endif
                                </td>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    @if(in_array($payment->status, ['requested', 'superseded'], true))
                                        <div class="d-flex justify-content-end gap-2">
                                            <form method="POST" action="{{ route('client-subscriptions.confirm', $payment) }}" onsubmit="return confirm('Confirma que el pago fue recibido y que la solicitud coincide con los vehículos actuales antes de activar el plan.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    {{ $payment->status === 'superseded' ? 'Revisar y confirmar' : 'Confirmar pago' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('client-subscriptions.hide', $payment) }}" onsubmit="return confirm('¿Ocultar esta solicitud del reporte? El registro se conservará en la base de datos.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Ocultar</button>
                                            </form>
                                        </div>
                                    @elseif($payment->status === 'paid')
                                        <form method="POST" action="{{ route('client-subscriptions.reverse', $payment) }}" onsubmit="return confirm('¿Revertir este pago? El acceso de flota se retirará si este pago corresponde a la suscripción vigente. Esta acción no devuelve dinero automáticamente.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Revertir pago</button>
                                        </form>
                                    @else
                                        <span class="small text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="p-3 border-bottom">
            <h2 class="h5 mb-0">Talleres y concesionarios</h2>
            <small class="text-muted">El monto se calcula a partir del plan asignado a cada empresa.</small>
        </div>
        @if($companyPayments->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-0">No hay solicitudes de renovación de talleres registradas.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo</th>
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Periodicidad</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companyPayments as $paymentRequest)
                            <tr>
                                <td><span class="badge bg-primary">Taller/Concesionario</span></td>
                                <td>
                                    <strong>{{ $paymentRequest->company?->name ?? 'Empresa eliminada' }}</strong>
                                    <small class="d-block text-muted">{{ $paymentRequest->requester?->email ?? 'Sin correo registrado' }}</small>
                                </td>
                                <td>{{ $paymentRequest->plan?->name ?? 'Plan eliminado' }}</td>
                                <td>{{ $paymentRequest->billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}</td>
                                <td><strong>{{ $paymentRequest->currency }} {{ $paymentRequest->amount }}</strong></td>
                                <td>
                                    @if($paymentRequest->status === 'requested')
                                        <span class="badge bg-warning text-dark">Solicitud</span>
                                    @elseif($paymentRequest->status === 'paid')
                                        <span class="badge bg-success">Pagado</span>
                                    @else
                                        <span class="badge bg-secondary">Reemplazado</span>
                                    @endif
                                    @if($paymentRequest->confirmer)
                                        <small class="d-block text-muted">Confirmó: {{ $paymentRequest->confirmer->name }}</small>
                                    @endif
                                </td>
                                <td>{{ $paymentRequest->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    @if($paymentRequest->status === 'requested')
                                        <div class="d-flex justify-content-end gap-2">
                                            <form method="POST" action="{{ route('company-subscription-payments.confirm', $paymentRequest) }}" onsubmit="return confirm('Confirma que recibiste el pago antes de renovar la suscripción de esta empresa.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success">Confirmar pago</button>
                                            </form>
                                            <form method="POST" action="{{ route('company-subscription-payments.hide', $paymentRequest) }}" onsubmit="return confirm('¿Ocultar esta solicitud? El registro se conservará en la base de datos.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Ocultar</button>
                                            </form>
                                        </div>
                                    @elseif($paymentRequest->status === 'superseded')
                                        <form method="POST" action="{{ route('company-subscription-payments.hide', $paymentRequest) }}" onsubmit="return confirm('¿Ocultar esta solicitud reemplazada? El registro se conservará en la base de datos.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Ocultar</button>
                                        </form>
                                    @else
                                        <span class="small text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $companyPayments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
