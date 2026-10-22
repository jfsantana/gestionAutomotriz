@extends('layouts.app')

@section('title', 'Editar Orden: ' . ($order->order_number ?? '#' . $order->id))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Editar Orden: {{ $order->order_number ?? '#' . $order->id }}</h1>
        <p class="text-muted mb-0 small">
            Vehículo: <strong>{{ $order->vehicle?->plate }}</strong> &bull; Cliente: <strong>{{ $order->customer?->full_name }}</strong>
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('maintenance-orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">
            Ver Detalle
        </a>
        <a href="{{ route('maintenance-orders.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('maintenance-orders.update', $order) }}" enctype="multipart/form-data" novalidate>
            @method('PUT')
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted">Cliente</label>
                    <input type="text" class="form-control bg-light" value="{{ $order->customer?->full_name }}" disabled>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted">Vehículo</label>
                    <input type="text" class="form-control bg-light" value="{{ $order->vehicle?->plate }} - {{ $order->vehicle?->make }} {{ $order->vehicle?->model }}" disabled>
                </div>

                <div class="col-md-6">
                    <label for="service_name_snapshot" class="form-label fw-semibold">Concepto / Nombre del Servicio <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        class="form-control @error('service_name_snapshot') is-invalid @enderror" 
                        id="service_name_snapshot" 
                        name="service_name_snapshot" 
                        value="{{ old('service_name_snapshot', $order->service_name_snapshot) }}" 
                        required
                    >
                    @error('service_name_snapshot')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="maintenance_type" class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                    <select class="form-select @error('maintenance_type') is-invalid @enderror" id="maintenance_type" name="maintenance_type" required>
                        <option value="preventive" @selected(old('maintenance_type', $order->maintenance_type) === 'preventive')>Preventivo</option>
                        <option value="corrective" @selected(old('maintenance_type', $order->maintenance_type) === 'corrective')>Correctivo</option>
                    </select>
                    @error('maintenance_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label fw-semibold">Estado de la Orden <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="open" @selected(old('status', $order->status) === 'open')>Abierta</option>
                        <option value="in_progress" @selected(old('status', $order->status) === 'in_progress')>En Progreso</option>
                        <option value="completed" @selected(old('status', $order->status) === 'completed')>Completada</option>
                        <option value="cancelled" @selected(old('status', $order->status) === 'cancelled')>Cancelada</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="mechanic_id" class="form-label fw-semibold">Mecánico Responsable</label>
                    <select class="form-select @error('mechanic_id') is-invalid @enderror" id="mechanic_id" name="mechanic_id">
                        <option value="">-- Sin asignar --</option>
                        @foreach($mechanics as $m)
                            <option value="{{ $m->id }}" @selected((string)old('mechanic_id', $order->mechanic_id) === (string)$m->id)>
                                {{ $m->name }} ({{ $m->username }})
                            </option>
                        @endforeach
                    </select>
                    @error('mechanic_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="service_date" class="form-label fw-semibold">Fecha del Servicio <span class="text-danger">*</span></label>
                    <input 
                        type="date" 
                        class="form-control @error('service_date') is-invalid @enderror" 
                        id="service_date" 
                        name="service_date" 
                        value="{{ old('service_date', $order->service_date?->format('Y-m-d')) }}" 
                        required
                    >
                    @error('service_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="current_mileage" class="form-label fw-semibold">Kilometraje (km) <span class="text-danger">*</span></label>
                    <input 
                        type="number" 
                        class="form-control @error('current_mileage') is-invalid @enderror" 
                        id="current_mileage" 
                        name="current_mileage" 
                        min="0"
                        value="{{ old('current_mileage', $order->current_mileage) }}" 
                        required
                    >
                    @error('current_mileage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="cost" class="form-label fw-semibold">Costo Total ($)</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0"
                        class="form-control @error('cost') is-invalid @enderror" 
                        id="cost" 
                        name="cost" 
                        value="{{ old('cost', $order->cost) }}" 
                    >
                    @error('cost')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="next_due_date" class="form-label fw-semibold">Próximo Vencimiento (Fecha)</label>
                    <input 
                        type="date" 
                        class="form-control @error('next_due_date') is-invalid @enderror" 
                        id="next_due_date" 
                        name="next_due_date" 
                        value="{{ old('next_due_date', $order->next_due_date?->format('Y-m-d')) }}"
                    >
                    @error('next_due_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="next_due_mileage" class="form-label fw-semibold">Próximo Vencimiento (Km)</label>
                    <input 
                        type="number" 
                        class="form-control @error('next_due_mileage') is-invalid @enderror" 
                        id="next_due_mileage" 
                        name="next_due_mileage" 
                        min="0"
                        value="{{ old('next_due_mileage', $order->next_due_mileage) }}" 
                    >
                    @error('next_due_mileage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="technical_notes" class="form-label fw-semibold">Observaciones Técnicas</label>
                    <textarea 
                        class="form-control @error('technical_notes') is-invalid @enderror" 
                        id="technical_notes" 
                        name="technical_notes" 
                        rows="3"
                    >{{ old('technical_notes', $order->technical_notes) }}</textarea>
                    @error('technical_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="customer_notes" class="form-label fw-semibold">Observaciones para el Cliente</label>
                    <textarea 
                        class="form-control @error('customer_notes') is-invalid @enderror" 
                        id="customer_notes" 
                        name="customer_notes" 
                        rows="3"
                    >{{ old('customer_notes', $order->customer_notes) }}</textarea>
                    @error('customer_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="photos" class="form-label fw-semibold">Agregar Fotografías de Evidencia</label>
                    <input 
                        type="file" 
                        class="form-control @error('photos') is-invalid @enderror" 
                        id="photos" 
                        name="photos[]" 
                        multiple 
                        accept="image/*"
                    >
                    <div class="form-text">Adjunta nuevas fotos si se requirieron repuestos o pruebas adicionales.</div>
                    @error('photos')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('maintenance-orders.show', $order) }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
