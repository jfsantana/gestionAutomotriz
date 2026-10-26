@extends('layouts.app')

@section('title', 'Cliente: ' . $customer->full_name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">{{ $customer->full_name }}</h1>
        <p class="text-muted mb-0 small">Ficha del cliente y gestión de vehículos asignados</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('customers.vehicles.create', $customer) }}" class="btn btn-sm btn-success fw-semibold">
            + Agregar Vehículo
        </a>
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">
            Editar Cliente
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver a Clientes
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Alerta Global de Mantenimientos del Cliente --}}
    @if(isset($hasAlerts) && $hasAlerts)
        <div class="col-12">
            <div class="alert alert-warning d-flex align-items-center mb-0 shadow-sm" role="alert">
                <div>
                    <strong>Atención:</strong> Uno o más vehículos de este cliente tienen mantenimientos vencidos o próximos a vencer en los próximos 30 días / 1,000 km.
                </div>
            </div>
        </div>
    @endif

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Datos del Cliente</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Tipo de Cliente</small>
                    @if($customer->customer_type === 'fleet_owner')
                        <span class="badge bg-info text-dark">Propietario de Flota</span>
                    @else
                        <span class="badge bg-secondary">Particular (Individual)</span>
                    @endif
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Documento de Identidad</small>
                    <strong>{{ $customer->document_number ? strtoupper($customer->document_type ?? 'DOC') . ': ' . $customer->document_number : 'No registrado' }}</strong>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Teléfono / Celular</small>
                    <span>{{ $customer->phone ?? 'No registrado' }}</span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Correo Electrónico</small>
                    <span>{{ $customer->email ?? 'No registrado' }}</span>
                </div>

                {{-- ESTADO DE ACCESO AL PORTAL DEL CLIENTE --}}
                <div class="p-3 mb-3 bg-light rounded border">
                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.72rem;">Acceso al Portal del Cliente</small>
                    @if($customer->user)
                        @if($customer->user->account_status === 'ACTIVE' && $customer->user->is_active)
                            <span class="badge bg-success mb-2">&#10003; Activo</span>
                            <small class="text-muted d-block">Usuario: <code>{{ $customer->user->username }}</code></small>
                            <small class="text-muted d-block">Activado: {{ $customer->user->activated_at?->format('d/m/Y H:i') ?? '—' }}</small>
                        @elseif($customer->user->account_status === 'INVITED')
                            <span class="badge bg-warning text-dark mb-2">&#9203; Invitación Pendiente</span>
                            <small class="text-muted d-block">Esperando que el cliente cree su contraseña por email.</small>
                            <form method="POST" action="{{ route('customers.invite', $customer) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    &#8635; Reenviar Invitación
                                </button>
                            </form>
                        @else
                            <span class="badge bg-danger mb-2">Desactivado</span>
                        @endif
                    @else
                        @if($customer->email)
                            <span class="badge bg-secondary mb-2">Sin Acceso</span>
                            <form method="POST" action="{{ route('customers.invite', $customer) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                                    &#9993; Habilitar y Enviar Invitación
                                </button>
                            </form>
                        @else
                            <span class="badge bg-secondary mb-2">Sin Correo</span>
                            <small class="text-danger d-block">Agrega un email al cliente para habilitarle acceso al portal.</small>
                        @endif
                    @endif
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Empresa / Taller</small>
                    <span>{{ $customer->company?->name ?? '—' }}</span>
                </div>

                @if($customer->notes)
                    <div class="mb-3">
                        <small class="text-muted d-block">Notas</small>
                        <p class="small text-secondary mb-0">{{ $customer->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0 fw-semibold">
                    Vehículos Registrados 
                    <span class="badge bg-primary ms-1">{{ $customer->vehicles->count() }}</span>
                </h5>
                <a href="{{ route('customers.vehicles.create', $customer) }}" class="btn btn-sm btn-outline-success">
                    + Agregar Vehículo
                </a>
            </div>
            <div class="card-body p-0">
                @if($customer->vehicles->isEmpty())
                    <div class="text-center py-5">
                        <p class="text-muted mb-3">Este cliente aún no tiene vehículos registrados.</p>
                        <a href="{{ route('customers.vehicles.create', $customer) }}" class="btn btn-success btn-sm">
                            Registrar el Primer Vehículo
                        </a>
                    </div>
                @else
                    <ul class="d-md-none list-unstyled d-flex flex-column gap-2 p-2 mb-0 bg-body-tertiary" aria-label="Vehículos Registrados">
                        @foreach($customer->vehicles as $vehicle)
                            <li class="card border p-2 text-break">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <span class="badge bg-dark text-uppercase text-wrap px-2 py-1">{{ $vehicle->plate }}</span>
                                    @if(isset($vehicleReminders[$vehicle->id]))
                                        <span class="badge text-wrap bg-{{ $vehicleReminders[$vehicle->id]['evaluation']['badge_class'] }}">
                                            {{ $vehicleReminders[$vehicle->id]['evaluation']['status_label'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    @if($vehicle->photo_path)
                                        <img src="{{ asset('storage/' . $vehicle->photo_path) }}" alt="Foto de {{ $vehicle->make }} {{ $vehicle->model }}" width="42" height="32" class="rounded border object-fit-cover flex-shrink-0" loading="lazy">
                                    @endif
                                    <h3 class="h6 fw-bold mb-0">{{ $vehicle->make }} {{ $vehicle->model }}</h3>
                                </div>
                                <div class="small">
                                    @if($vehicle->vin)
                                        <div class="text-muted">VIN: {{ $vehicle->vin }}</div>
                                    @endif
                                    <div class="d-flex flex-wrap gap-2">
                                        <span><span class="text-muted">Año:</span> {{ $vehicle->year ?? '—' }}</span>
                                        @if($vehicle->color)
                                            <span><span class="text-muted">Color:</span> {{ $vehicle->color }}</span>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span><strong>{{ number_format($vehicle->current_mileage) }}</strong> km</span>
                                        <span><span class="text-muted">Combustible:</span> {{ $vehicle->fuel_type ? ucfirst($vehicle->fuel_type) : '—' }}</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-secondary flex-fill py-2" aria-label="Ver vehículo {{ $vehicle->plate }}">
                                        Ver
                                    </a>
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-primary flex-fill py-2" aria-label="Editar vehículo {{ $vehicle->plate }}">
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
                                    <th scope="col" class="ps-3">Placa</th>
                                    <th scope="col">Vehículo</th>
                                    <th scope="col">Año / Color</th>
                                    <th scope="col">Kilometraje</th>
                                    <th scope="col">Combustible</th>
                                    <th scope="col" class="text-end pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->vehicles as $vehicle)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-dark text-uppercase px-2 py-1 fs-6">{{ $vehicle->plate }}</span>
                                            @if($vehicle->vin)
                                                <div><small class="text-muted">VIN: {{ $vehicle->vin }}</small></div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($vehicle->photo_path)
                                                    <img src="{{ asset('storage/' . $vehicle->photo_path) }}" alt="Foto" width="42" height="32" class="rounded me-2 border object-fit-cover shadow-sm">
                                                @endif
                                                <div class="fw-bold text-dark">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            {{ $vehicle->year ?? '—' }}
                                            @if($vehicle->color)
                                                <small class="text-muted">({{ $vehicle->color }})</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ number_format($vehicle->current_mileage) }}</strong> km
                                        </td>
                                        <td>
                                            <span class="text-secondary small">{{ $vehicle->fuel_type ? ucfirst($vehicle->fuel_type) : '—' }}</span>
                                            @if(isset($vehicleReminders[$vehicle->id]))
                                                <div class="mt-1">
                                                    <span class="badge bg-{{ $vehicleReminders[$vehicle->id]['evaluation']['badge_class'] }}">
                                                        {{ $vehicleReminders[$vehicle->id]['evaluation']['status_label'] }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                                    Ver
                                                </a>
                                                <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-outline-primary" title="Editar">
                                                    Editar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
