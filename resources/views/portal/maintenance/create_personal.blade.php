@extends('portal.layouts.client')

@section('title', 'Registrar Mantenimiento Propio')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
    <div>
        <x-page-heading section="personal-maintenance" class="h4 mb-0 fw-bold text-dark">Registrar Mantenimiento Propio</x-page-heading>
        <p class="text-muted mb-0 small">Lleva tu propio control personal de repuestos, servicios y kilometraje</p>
    </div>
    <div class="btn-toolbar gap-2">
        <a href="{{ route('portal.maintenance.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Historial
        </a>
    </div>
</div>

<div class="card shadow-sm border-0" style="max-width: 800px;">
    <div class="card-body p-4">
        <div class="alert alert-info py-2 px-3 small mb-4">
            <strong>Nota informativa:</strong> Este registro quedará guardado con la etiqueta <em>"Registro Personal (No Verificado)"</em> para tu propio control administrativo.
        </div>

        <form method="POST" action="{{ route('portal.maintenance.store') }}" novalidate>
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="vehicle_id" class="form-label fw-semibold small">Vehículo <span class="text-danger">*</span></label>
                    <select class="form-select @error('vehicle_id') is-invalid @enderror" id="vehicle_id" name="vehicle_id" required>
                        <option value="">-- Seleccionar Vehículo --</option>
                        @foreach($vehicles as $veh)
                            <option value="{{ $veh->id }}" data-km="{{ $veh->current_mileage }}" @selected((string)old('vehicle_id') === (string)$veh->id)>
                                {{ $veh->plate }} &bull; {{ $veh->make }} {{ $veh->model }} ({{ number_format($veh->current_mileage) }} km)
                            </option>
                        @endforeach
                    </select>
                    @error('vehicle_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="maintenance_type" class="form-label fw-semibold small">Tipo de Servicio <span class="text-danger">*</span></label>
                    <select class="form-select @error('maintenance_type') is-invalid @enderror" id="maintenance_type" name="maintenance_type" required>
                        <option value="preventive" @selected(old('maintenance_type') === 'preventive')>Preventivo (Aceite, filtros, rotación...)</option>
                        <option value="corrective" @selected(old('maintenance_type') === 'corrective')>Correctivo (Reparación, cambio de pieza rota...)</option>
                    </select>
                    @error('maintenance_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12">
                    <label for="service_name_snapshot" class="form-label fw-semibold small">Concepto o Descripción Corta <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('service_name_snapshot') is-invalid @enderror" id="service_name_snapshot" name="service_name_snapshot" value="{{ old('service_name_snapshot') }}" placeholder="Ej. Cambio de Aceite 5W-30 comprado en tienda" required>
                    @error('service_name_snapshot')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="service_date" class="form-label fw-semibold small">Fecha Realizada <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('service_date') is-invalid @enderror" id="service_date" name="service_date" value="{{ old('service_date', now()->toDateString()) }}" required>
                    @error('service_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="current_mileage" class="form-label fw-semibold small">Kilometraje al Realizar (km) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('current_mileage') is-invalid @enderror" id="current_mileage" name="current_mileage" value="{{ old('current_mileage') }}" min="0" placeholder="Ej. 45000" required>
                    @error('current_mileage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="cost" class="form-label fw-semibold small">Gasto Estimado ($) (Opcional)</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('cost') is-invalid @enderror" id="cost" name="cost" value="{{ old('cost') }}" placeholder="0.00">
                    @error('cost')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="customer_notes" class="form-label fw-semibold small">Notas Personales / Marcas de Repuestos</label>
                    <textarea class="form-control @error('customer_notes') is-invalid @enderror" id="customer_notes" name="customer_notes" rows="3" placeholder="Ej. Compré 4 cuartos Mobil 1 sintético y filtro Bosch...">{{ old('customer_notes') }}</textarea>
                    @error('customer_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('portal.maintenance.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold">Guardar Mantenimiento Personal</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const vehSelect = document.getElementById('vehicle_id');
        const kmInput = document.getElementById('current_mileage');

        vehSelect.addEventListener('change', function() {
            const opt = vehSelect.options[vehSelect.selectedIndex];
            if (opt && opt.value) {
                const km = opt.getAttribute('data-km');
                if (km && !kmInput.value) {
                    kmInput.value = km;
                }
            }
        });
    });
</script>
@endpush
@endsection
