@extends('portal.layouts.client')

@section('title', 'Mis Vehículos')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
    <div>
        <x-page-heading section="vehicles" class="h4 mb-0 fw-bold text-dark">Mis Vehículos</x-page-heading>
        <p class="text-muted mb-0 small">Consulta los vehículos registrados a tu nombre</p>
    </div>
    @if($canAddVehicle)
        <a href="{{ route('portal.vehicles.create') }}" class="btn btn-sm btn-primary fw-semibold">+ Registrar vehículo</a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if(session('info'))
    <div class="alert alert-info" role="status">{{ session('info') }}</div>
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

@if($vehicles->isEmpty() && $hidden_vehicles_count === 0)
    <div class="card shadow-sm border-0 p-5 text-center">
        <span class="fs-1 text-muted mb-2">&#128663;</span>
        <h5 class="fw-bold text-dark">No tienes vehículos activos</h5>
        <p class="text-secondary small mb-3">Puedes registrar tus vehículos directamente o aceptar una invitación de un taller.</p>
        @if($canAddVehicle)
            <a href="{{ route('portal.vehicles.create') }}" class="btn btn-primary btn-sm">Registrar mi primer vehículo</a>
        @endif
    </div>
@else
    <div class="row g-4">
        @foreach($vehicles as $veh)
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100 overflow-hidden">
                    @if($veh->photo_path)
                        <img src="{{ asset('storage/' . $veh->photo_path) }}" alt="{{ $veh->make }}" class="card-img-top" style="height: 180px; object-fit: cover;">
                    @else
                        <div class="bg-light text-center py-5 border-bottom text-muted">
                            <span class="fs-1 d-block">&#128663;</span>
                            <small>Sin fotografía</small>
                        </div>
                    @endif
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold text-dark mb-0">{{ $veh->make }} {{ $veh->model }}</h5>
                            <span class="badge bg-dark text-uppercase fs-6">{{ $veh->plate }}</span>
                        </div>
                        <ul class="list-unstyled small text-secondary mb-3">
                            <li>Año: <strong>{{ $veh->year ?? '—' }}</strong> | Color: <strong>{{ $veh->color ?? '—' }}</strong></li>
                            <li>Kilometraje: <strong>{{ number_format($veh->current_mileage) }} km</strong></li>
                            <li>Mantenimientos: <strong>{{ $veh->maintenance_orders_count }} registros</strong></li>
                        </ul>
                    </div>
                    <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                        <a href="{{ route('portal.vehicles.show', $veh) }}" class="btn btn-sm btn-primary">
                            Ver Ficha & Historial
                        </a>
                        {{-- Botón Dar de Baja por el Cliente con confirmación --}}
                        <form method="POST" action="{{ route('portal.vehicles.decommission', $veh) }}" onsubmit="return confirm('¿ATENCIÓN: Confirmas dar de baja este vehículo de tu cuenta? Se desvinculará de tu perfil y se limpiará su historial visible para evitar transferencia de datos a futuros compradores.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Dar de baja vehículo">
                                Dar de Baja
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach

        @if($hidden_vehicles_count > 0)
            <div class="col-12">
                <div class="card h-100 border-secondary border-2 border-dashed bg-body-secondary shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 3.5rem; height: 3.5rem;" aria-hidden="true">
                                <span class="fs-3">&#128274;</span>
                            </div>
                            <div>
                                <span class="badge bg-secondary mb-2">Plan Propietario de Flota</span>
                                <h5 class="card-title fw-bold text-secondary mb-1">Vehículos adicionales bloqueados</h5>
                                <p class="text-secondary mb-0">
                                    @if($customer->hasFleetTrialActive())
                                        Tienes {{ $hidden_vehicles_count }} vehículos adicionales registrados por el taller.
                                    @else
                                        Tu período gratuito terminó. Tienes {{ $hidden_vehicles_count }} vehículos registrados que puedes habilitar al suscribirte.
                                    @endif
                                    Actualiza al plan Propietario de Flota para gestionar toda tu plantilla.
                                </p>
                            </div>
                        </div>

                        @if($fleetPayment?->status === 'requested')
                            <div class="alert alert-warning mb-4" role="status">
                                <strong>Tu solicitud está registrada y pendiente de confirmación por AutoBitácora.</strong>
                                <span class="d-block">Solicitud #{{ $fleetPayment->id }} · {{ $fleetPayment->billing_cycle === 'annual' ? 'Anual' : 'Mensual' }} · USD {{ $fleetPayment->amount }}</span>
                                <span class="d-block">Enviamos un correo a ti y a AutoBitácora. Los vehículos se habilitarán cuando se confirme el pago.</span>
                            </div>
                        @elseif($fleetPayment?->status === 'superseded')
                            <div class="alert alert-secondary mb-4" role="status">
                                La solicitud anterior fue reemplazada porque cambió la cantidad o el tipo de vehículos. Registra una nueva solicitud con los datos actuales.
                            </div>
                        @endif

                        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
                            <form method="POST" action="{{ route('portal.customers.upgrade-to-fleet', $customer) }}" class="d-flex flex-column flex-sm-row align-items-sm-end gap-2">
                                @csrf
                                <div>
                                    <label for="billing_cycle" class="form-label fw-semibold mb-1">Periodicidad</label>
                                    <select id="billing_cycle" name="billing_cycle" class="form-select" required>
                                        <option value="monthly" @selected(old('billing_cycle') === 'monthly')>Mensual</option>
                                        <option value="annual" @selected(old('billing_cycle') === 'annual')>Anual (paga 11 meses y cubre 12)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    Registrar solicitud de pago
                                </button>
                            </form>

                            <a
                                href="https://nfcvzla.venecode.com/jsantana/"
                                class="btn btn-outline-primary flex-shrink-0"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Desbloquear Todos los vehículos
                                <span class="d-block small">Contactar a AutoBitácora</span>
                            </a>
                        </div>
                        <p class="small text-secondary mt-3 mb-0">
                            La solicitud no cobra automáticamente ni desbloquea los vehículos. AutoBitácora debe verificar y confirmar el pago.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
@endsection
