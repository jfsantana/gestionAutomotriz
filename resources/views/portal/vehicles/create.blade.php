@extends('portal.layouts.client')

@section('title', 'Registrar vehículo')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h4 fw-bold mb-1">Registrar vehículo</h1>
        <p class="text-muted small mb-0">El vehículo quedará bajo tu cuenta y sin taller asociado.</p>
    </div>
    <a href="{{ route('portal.vehicles.index') }}" class="btn btn-sm btn-outline-secondary">Volver a Mis Vehículos</a>
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
        <form method="POST" action="{{ route('portal.vehicles.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="plate" class="form-label">Placa *</label>
                    <input id="plate" name="plate" value="{{ old('plate') }}" class="form-control" maxlength="30" required>
                </div>
                <div class="col-md-6">
                    <label for="vin" class="form-label">VIN / Chasis</label>
                    <input id="vin" name="vin" value="{{ old('vin') }}" class="form-control" maxlength="50">
                </div>
                <div class="col-md-6">
                    <label for="vehicle_type" class="form-label">Tipo de vehículo *</label>
                    <select id="vehicle_type" name="vehicle_type" class="form-select" required>
                        @foreach($vehicleTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('vehicle_type', 'carro') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="make" class="form-label">Marca *</label>
                    <select id="make" name="make" class="form-select" required>
                        <option value="">Selecciona una marca</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->name }}" data-vehicle-type="{{ $brand->vehicle_type }}" @selected(old('make') === $brand->name)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="model" class="form-label">Modelo *</label>
                    <input id="model" name="model" value="{{ old('model') }}" class="form-control" maxlength="80" required>
                </div>
                <div class="col-md-6">
                    <label for="year" class="form-label">Año</label>
                    <input id="year" name="year" type="number" value="{{ old('year') }}" class="form-control" min="1900" max="{{ date('Y') + 2 }}">
                </div>
                <div class="col-md-6">
                    <label for="color" class="form-label">Color</label>
                    <input id="color" name="color" value="{{ old('color') }}" class="form-control" maxlength="50">
                </div>
                <div class="col-md-6">
                    <label for="current_mileage" class="form-label">Kilometraje actual *</label>
                    <input id="current_mileage" name="current_mileage" type="number" value="{{ old('current_mileage', 0) }}" class="form-control" min="0" required>
                </div>
                <div class="col-md-6">
                    <label for="fuel_type" class="form-label">Combustible</label>
                    <input id="fuel_type" name="fuel_type" value="{{ old('fuel_type') }}" class="form-control" maxlength="30">
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notas</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" maxlength="1000">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar vehículo</button>
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
