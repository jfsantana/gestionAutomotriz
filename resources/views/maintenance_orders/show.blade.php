@extends('layouts.app')

@section('title', 'Orden: ' . ($order->order_number ?? '#' . $order->id))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="maintenance-orders" class="h3 mb-0">Orden {{ $order->order_number ?? '#' . $order->id }}</x-page-heading>
        <p class="text-muted mb-0 small">
            {{ $order->service_name_snapshot }} &bull; Fecha: <strong>{{ $order->service_date?->format('d/m/Y') }}</strong>
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('maintenance-orders.edit', $order) }}" class="btn btn-sm btn-primary">
            Editar Orden
        </a>
        <a href="{{ route('vehicles.show', $order->vehicle_id) }}" class="btn btn-sm btn-outline-primary">
            Ver Vehículo
        </a>
        <a href="{{ route('maintenance-orders.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Alerta informativa cuando el mantenimiento fue realizado de forma personal/externa por el cliente --}}
    @if($order->origin === 'customer')
        <div class="col-12">
            <div class="alert alert-warning border-warning d-flex align-items-center shadow-sm mb-0" role="alert">
                <div class="fs-3 me-3">&#9888;</div>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">Mantenimiento Particular / Realizado fuera del Taller o Concesionario</h6>
                    <p class="mb-0 small">
                        Este registro fue creado manualmente por el cliente (propietario) desde el Portal de Clientes. 
                        <strong>No fue ejecutado ni verificado por el personal técnico de este taller/concesionario</strong>. Se muestra en el historial para trazabilidad y cálculo de alertas de odómetro/fechas preventivas.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="card-title mb-0 fw-semibold">Detalles del Servicio</h5>
                    @if($order->origin === 'customer')
                        <span class="badge bg-warning text-dark border border-warning-subtle">&#9888; Realizado fuera de taller</span>
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle">&#10003; Realizado en taller</span>
                    @endif
                </div>
                @if($order->status === 'completed')
                    <span class="badge bg-success fs-6">Completada</span>
                @elseif($order->status === 'in_progress')
                    <span class="badge bg-primary fs-6">En Progreso</span>
                @elseif($order->status === 'cancelled')
                    <span class="badge bg-danger fs-6">Cancelada</span>
                @else
                    <span class="badge bg-secondary fs-6">Abierta</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Servicio</small>
                        <strong class="text-dark fs-6">{{ $order->service_name_snapshot }}</strong>
                        @if($order->service_description_snapshot)
                            <p class="small text-muted mb-0">{{ $order->service_description_snapshot }}</p>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Tipo de Mantenimiento</small>
                        <span class="badge {{ $order->maintenance_type === 'preventive' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2">
                            {{ $order->maintenance_type === 'preventive' ? 'Mantenimiento Preventivo' : 'Mantenimiento Correctivo' }}
                        </span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Fecha Realizada</small>
                        <strong>{{ $order->service_date?->format('d/m/Y') }}</strong>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Kilometraje al Realizar</small>
                        <strong>{{ number_format($order->current_mileage) }} km</strong>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Costo Registrado</small>
                        <strong class="text-success fs-6">{{ $order->cost !== null ? '$' . number_format($order->cost, 2) : 'No especificado' }}</strong>
                    </div>

                    <div class="col-sm-6">
                        <small class="text-muted d-block">Próximo Vencimiento (Fecha Estimada)</small>
                        <span>{{ $order->next_due_date ? $order->next_due_date->format('d/m/Y') : 'No establecida' }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Próximo Vencimiento (Kilometraje)</small>
                        <span>{{ $order->next_due_mileage ? number_format($order->next_due_mileage) . ' km' : 'No establecido' }}</span>
                    </div>

                    @if($order->technical_notes)
                        <div class="col-12">
                            <small class="text-muted d-block">Observaciones Técnicas del Taller</small>
                            <div class="p-3 bg-light rounded border small text-dark">{{ $order->technical_notes }}</div>
                        </div>
                    @endif

                    @if($order->customer_notes)
                        <div class="col-12">
                            <small class="text-muted d-block">Observaciones para el Cliente</small>
                            <div class="p-3 bg-light rounded border small text-dark">{{ $order->customer_notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Evidencia Fotográfica --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">
                    Evidencia Visual / Fotografías
                    <span class="badge bg-secondary ms-1">{{ $order->photos->count() }}</span>
                </h5>
            </div>
            <div class="card-body">
                @if($order->photos->isEmpty())
                    <p class="text-muted small mb-0">No se adjuntaron fotografías de evidencia para esta orden.</p>
                @else
                    <div class="row g-3">
                        @foreach($order->photos as $photo)
                            <div class="col-md-4 col-sm-6">
                                <div class="card h-100 border">
                                    <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $photo->file_path) }}" class="card-img-top" alt="Evidencia" style="height: 180px; object-fit: cover;">
                                    </a>
                                    <div class="card-footer bg-white p-2 text-truncate small text-muted">
                                        {{ $photo->original_name ?? 'Foto #' . $photo->id }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Ficha del Vehículo --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Vehículo Asociado</h5>
            </div>
            <div class="card-body">
                @if($order->vehicle)
                    <div class="mb-2">
                        <small class="text-muted d-block">Placa</small>
                        <span class="badge bg-dark text-uppercase fs-6 px-3 py-1">{{ $order->vehicle->plate }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Vehículo</small>
                        <strong class="text-dark">{{ $order->vehicle->make }} {{ $order->vehicle->model }}</strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Odómetro Actual del Vehículo</small>
                        <strong>{{ number_format($order->vehicle->current_mileage) }} km</strong>
                    </div>
                    <a href="{{ route('vehicles.show', $order->vehicle) }}" class="btn btn-outline-primary btn-sm w-100 mt-2">
                        Ver Ficha Completa del Vehículo
                    </a>
                @else
                    <span class="text-muted small">Vehículo no disponible</span>
                @endif
            </div>
        </div>

        {{-- Ficha del Propietario --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Propietario / Cliente</h5>
            </div>
            <div class="card-body">
                @if($order->customer)
                    <div class="mb-2">
                        <small class="text-muted d-block">Nombre</small>
                        <strong class="text-dark">{{ $order->customer->full_name }}</strong>
                    </div>
                    @if($order->customer->phone)
                        <div class="mb-2">
                            <small class="text-muted d-block">Teléfono</small>
                            <span>{{ $order->customer->phone }}</span>
                        </div>
                    @endif
                    <a href="{{ route('customers.show', $order->customer) }}" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                        Ver Ficha del Cliente
                    </a>
                @else
                    <span class="text-muted small">Cliente no disponible</span>
                @endif
            </div>
        </div>

        {{-- Mecánico y Auditoría --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Operador y Taller</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted d-block">Mecánico Asignado</small>
                    @if($order->mechanic)
                        <span class="badge bg-light text-dark border">{{ $order->mechanic->name }} ({{ $order->mechanic->username }})</span>
                    @else
                        <span class="text-muted">Sin mecánico asignado</span>
                    @endif
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Taller / Concesionario</small>
                    <span>{{ $order->company?->name ?? '—' }}</span>
                </div>
                <hr>
                <p class="small text-muted mb-1">Registrado el: <strong>{{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}</strong></p>
                @if($order->completed_at)
                    <p class="small text-muted mb-1">Completado el: <strong>{{ $order->completed_at->format('d/m/Y H:i') }}</strong></p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
