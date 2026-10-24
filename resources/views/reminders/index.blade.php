@extends('layouts.app')

@section('title', 'Recordatorios y Próximos Mantenimientos')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Recordatorios de Mantenimiento</h1>
        <p class="text-muted mb-0 small">
            Control operativo de próximos vencimientos por fecha y kilometraje
            @if(auth()->user()->company)
                en <strong>{{ auth()->user()->company->name }}</strong>
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('maintenance-orders.create') }}" class="btn btn-sm btn-success fw-semibold">
            + Registrar Mantenimiento
        </a>
    </div>
</div>

<div class="alert alert-info small" role="note">
    Los recordatorios por email se envían automáticamente dos días antes del vencimiento, a las 08:00 (hora de Caracas).
    Máximo un correo diario por mantenimiento, incluyendo los envíos manuales.
</div>

{{-- Tarjetas de Resumen Operativo --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-danger border-4">
            <div class="card-body py-3">
                <small class="text-muted text-uppercase fw-semibold d-block">Mantenimientos Vencidos</small>
                <div class="h3 mb-0 text-danger fw-bold">{{ $summary['overdue'] }}</div>
                <small class="text-muted">Requieren atención inmediata</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-warning border-4">
            <div class="card-body py-3">
                <small class="text-muted text-uppercase fw-semibold d-block">Próximos a Vencer</small>
                <div class="h3 mb-0 text-warning text-dark fw-bold">{{ $summary['due_soon'] }}</div>
                <small class="text-muted">En los próximos 30 días o 1,000 km</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-success border-4">
            <div class="card-body py-3">
                <small class="text-muted text-uppercase fw-semibold d-block">En Rango / Futuros</small>
                <div class="h3 mb-0 text-success fw-bold">{{ $summary['upcoming'] }}</div>
                <small class="text-muted">Programados con control activo</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-primary border-4">
            <div class="card-body py-3">
                <small class="text-muted text-uppercase fw-semibold d-block">Total con Control</small>
                <div class="h3 mb-0 text-primary fw-bold">{{ $summary['total_scheduled'] }}</div>
                <small class="text-muted">Órdenes completadas evaluadas</small>
            </div>
        </div>
    </div>
</div>

{{-- Filtros Rápidos --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reminders.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="status" class="form-label small fw-semibold text-muted mb-1">Estado del Recordatorio</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">Todos los Estados</option>
                    <option value="overdue" @selected($statusFilter === 'overdue')>Vencidos (Overdue)</option>
                    <option value="due_soon" @selected($statusFilter === 'due_soon')>Por Vencer (Due Soon)</option>
                    <option value="upcoming" @selected($statusFilter === 'upcoming')>Próximos (Upcoming)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="plate" class="form-label small fw-semibold text-muted mb-1">Placa del Vehículo</label>
                <input type="text" class="form-control form-control-sm text-uppercase" id="plate" name="plate" value="{{ $searchPlate }}" placeholder="Ej. A123456">
            </div>
            <div class="col-md-4">
                <label for="customer" class="form-label small fw-semibold text-muted mb-1">Cliente / Propietario</label>
                <input type="text" class="form-control form-control-sm" id="customer" name="customer" value="{{ $searchCustomer }}" placeholder="Nombre del cliente...">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">Filtrar</button>
                <a href="{{ route('reminders.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla Principal de Recordatorios --}}
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($reminders->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay mantenimientos que coincidan con los criterios seleccionados.</p>
                <a href="{{ route('maintenance-orders.create') }}" class="btn btn-sm btn-primary">Registrar Nueva Orden</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Estado</th>
                            <th scope="col">Vehículo</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Último Servicio</th>
                            <th scope="col">Vencimiento Fecha</th>
                            <th scope="col">Vencimiento Kilometraje</th>
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reminders as $order)
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-{{ $order->reminder['badge_class'] }} px-2 py-1">
                                        {{ $order->reminder['status_label'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-dark text-uppercase px-2 py-1">{{ $order->vehicle?->plate }}</span>
                                    <div class="fw-semibold text-dark small mt-1">{{ $order->vehicle?->make }} {{ $order->vehicle?->model }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $order->customer?->full_name }}</div>
                                    @if($order->customer?->phone)
                                        <small class="text-muted">{{ $order->customer->phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-dark fw-semibold small">{{ $order->service_name_snapshot }}</div>
                                    <small class="text-muted">Realizado: {{ $order->service_date?->format('d/m/Y') }} ({{ number_format($order->current_mileage) }} km)</small>
                                </td>
                                <td>
                                    @if($order->next_due_date)
                                        <strong class="{{ $order->reminder['date_status'] === 'overdue' ? 'text-danger' : ($order->reminder['date_status'] === 'due_soon' ? 'text-warning text-dark' : 'text-dark') }}">
                                            {{ $order->next_due_date->format('d/m/Y') }}
                                        </strong>
                                        <small class="d-block text-muted">
                                            @if($order->reminder['days_remaining'] < 0)
                                                Venció hace {{ abs($order->reminder['days_remaining']) }} días
                                            @elseif($order->reminder['days_remaining'] === 0)
                                                Vence hoy
                                            @else
                                                En {{ $order->reminder['days_remaining'] }} días
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->next_due_mileage)
                                        <strong class="{{ $order->reminder['mileage_status'] === 'overdue' ? 'text-danger' : ($order->reminder['mileage_status'] === 'due_soon' ? 'text-warning text-dark' : 'text-dark') }}">
                                            {{ number_format($order->next_due_mileage) }} km
                                        </strong>
                                        <small class="d-block text-muted">
                                            Actual: {{ number_format($order->vehicle?->current_mileage ?? 0) }} km
                                            @if($order->reminder['mileage_remaining'] !== null)
                                                @if($order->reminder['mileage_remaining'] <= 0)
                                                    <span class="text-danger fw-semibold">({{ number_format(abs($order->reminder['mileage_remaining'])) }} km excedidos)</span>
                                                @else
                                                    (Faltan {{ number_format($order->reminder['mileage_remaining']) }} km)
                                                @endif
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @if($order->vehicle)
                                            <a href="{{ route('maintenance-orders.create', ['vehicle_id' => $order->vehicle->id]) }}" class="btn btn-outline-success" title="Registrar nuevo servicio">
                                                + Servicio
                                            </a>
                                            <a href="{{ route('vehicles.show', $order->vehicle) }}" class="btn btn-outline-secondary" title="Ver historial">
                                                Vehículo
                                            </a>
                                        @endif
                                        <a href="{{ route('maintenance-orders.show', $order) }}" class="btn btn-outline-primary" title="Ver orden original">
                                            Orden
                                        </a>
                                    </div>
                                    @can('send-maintenance-reminder', $order)
                                        <form method="POST" action="{{ route('reminders.send', $order) }}" class="mt-2"
                                            data-email="{{ $order->customer?->email }}"
                                            onsubmit="if (!confirm('¿Enviar un recordatorio de mantenimiento a ' + this.dataset.email + '?')) return false; this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Enviando...';">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary"
                                                @disabled($order->reminder_email_error)
                                                aria-label="Enviar recordatorio de la orden {{ $order->order_number ?? $order->id }}"
                                                title="{{ $order->reminder_email_error ?? 'Enviar recordatorio al email del cliente' }}">
                                                Enviar recordatorio
                                            </button>
                                            @if($order->reminder_email_error)
                                                <small class="d-block text-muted mt-1">{{ $order->reminder_email_error }}</small>
                                            @endif
                                        </form>
                                        @if($order->reminder_sent_at)
                                            <small class="d-block text-muted mt-1">
                                                Último envío: {{ $order->reminder_sent_at->copy()->timezone(\App\Services\MaintenanceReminderNotificationService::TIMEZONE)->format('d/m/Y H:i') }} (Caracas)
                                            </small>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($reminders->hasPages())
                <div class="p-3 border-top">
                    {{ $reminders->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
