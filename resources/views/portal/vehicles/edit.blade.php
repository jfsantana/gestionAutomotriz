@extends('portal.layouts.client')

@section('title', 'Editar vehículo')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h4 fw-bold mb-1">Editar vehículo</h1>
        <p class="text-muted small mb-0">Actualiza los datos de tu vehículo con placa <strong class="badge bg-dark">{{ $vehicle->plate }}</strong>.</p>
    </div>
    <a href="{{ route('portal.vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-secondary">Volver a la Ficha</a>
</div>

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('portal.vehicles.update', $vehicle) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="plate" class="form-label">Placa *</label>
                    <input id="plate" name="plate" value="{{ old('plate', $vehicle->plate) }}" class="form-control" maxlength="30" required>
                </div>
                <div class="col-md-6">
                    <label for="vin" class="form-label">VIN / Chasis</label>
                    <input id="vin" name="vin" value="{{ old('vin', $vehicle->vin) }}" class="form-control" maxlength="50">
                </div>
                <div class="col-md-6">
                    <label for="vehicle_type" class="form-label">Tipo de vehículo *</label>
                    <select id="vehicle_type" name="vehicle_type" class="form-select" required>
                        @foreach($vehicleTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('vehicle_type', $vehicle->vehicle_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="make" class="form-label">Marca *</label>
                    <select id="make" name="make" class="form-select" required>
                        <option value="">Selecciona una marca</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->name }}" data-vehicle-type="{{ $brand->vehicle_type }}" @selected(old('make', $vehicle->make) === $brand->name)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="model" class="form-label">Modelo *</label>
                    <input id="model" name="model" value="{{ old('model', $vehicle->model) }}" class="form-control" maxlength="80" required>
                </div>
                <div class="col-md-6">
                    <label for="year" class="form-label">Año</label>
                    <input id="year" name="year" type="number" value="{{ old('year', $vehicle->year) }}" class="form-control" min="1900" max="{{ date('Y') + 2 }}">
                </div>
                <div class="col-md-6">
                    <label for="color" class="form-label">Color</label>
                    <input id="color" name="color" value="{{ old('color', $vehicle->color) }}" class="form-control" maxlength="50">
                </div>
                <div class="col-md-6">
                    <label for="current_mileage" class="form-label">Kilometraje actual *</label>
                    <input id="current_mileage" name="current_mileage" type="number" value="{{ old('current_mileage', $vehicle->current_mileage) }}" class="form-control" min="0" required>
                </div>
                <div class="col-md-6">
                    <label for="fuel_type" class="form-label">Combustible</label>
                    <select id="fuel_type" name="fuel_type" class="form-select">
                        <option value="">-- No especificado --</option>
                        <option value="gasolina" @selected(old('fuel_type', $vehicle->fuel_type) === 'gasolina')>Gasolina</option>
                        <option value="diesel" @selected(old('fuel_type', $vehicle->fuel_type) === 'diesel')>Diésel (Gasoil)</option>
                        <option value="glp" @selected(old('fuel_type', $vehicle->fuel_type) === 'glp')>GLP</option>
                        <option value="gnc" @selected(old('fuel_type', $vehicle->fuel_type) === 'gnc')>GNC</option>
                        <option value="hibrido" @selected(old('fuel_type', $vehicle->fuel_type) === 'hibrido')>Híbrido</option>
                        <option value="electrico" @selected(old('fuel_type', $vehicle->fuel_type) === 'electrico')>Eléctrico</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="photo" class="form-label">Fotografía del vehículo (opcional)</label>
                    @if($vehicle->photo_path)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $vehicle->photo_path) }}" alt="Foto del vehículo" height="80" class="rounded border p-1">
                            <small class="text-muted ms-2">Foto actual registrada. Sube una nueva para reemplazarla.</small>
                        </div>
                    @endif
                    <input id="photo" name="photo" type="file" class="form-control" accept="image/*">
                    <div class="form-text">Sube una fotografía del vehículo para su ficha técnica (PNG, JPG, máx 5MB).</div>
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notas</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" maxlength="1000">{{ old('notes', $vehicle->notes) }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('portal.vehicles.show', $vehicle) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const typeSelect = document.getElementById('vehicle_type');
    const brandSelect = document.getElementById('make');

    function filterBrands() {
        const selectedType = typeSelect.value;
        Array.from(brandSelect.options).forEach((option) => {
            if (option.value) {
                option.hidden = option.dataset.vehicleType !== selectedType;
            }
        });
        if (brandSelect.selectedOptions[0]?.hidden) {
            brandSelect.value = '';
        }
    }

    typeSelect.addEventListener('change', filterBrands);
    filterBrands();
</script>
@endpush
