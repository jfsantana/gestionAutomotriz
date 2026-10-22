@csrf

<input type="hidden" name="customer_id" value="{{ old('customer_id', $vehicle->customer_id ?? $customer->id ?? '') }}">

<div class="row g-3">
    <div class="col-md-6">
        <label for="plate" class="form-label fw-semibold">Placa (Matrícula) <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control text-uppercase @error('plate') is-invalid @enderror" 
            id="plate" 
            name="plate" 
            value="{{ old('plate', $vehicle->plate) }}" 
            placeholder="Ej. A123456"
            required
        >
        <div class="form-text">Debe ser única dentro de la empresa.</div>
        @error('plate')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="vin" class="form-label fw-semibold">VIN / Chasis (Opcional)</label>
        <input 
            type="text" 
            class="form-control text-uppercase @error('vin') is-invalid @enderror" 
            id="vin" 
            name="vin" 
            value="{{ old('vin', $vehicle->vin) }}" 
            placeholder="Ej. 1HGCR2F83HA000000"
        >
        @error('vin')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="make" class="form-label fw-semibold">Marca <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control @error('make') is-invalid @enderror" 
            id="make" 
            name="make" 
            value="{{ old('make', $vehicle->make) }}" 
            placeholder="Ej. Toyota, Honda, Hyundai"
            required
        >
        @error('make')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="model" class="form-label fw-semibold">Modelo <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control @error('model') is-invalid @enderror" 
            id="model" 
            name="model" 
            value="{{ old('model', $vehicle->model) }}" 
            placeholder="Ej. Corolla, Civic, Tucson"
            required
        >
        @error('model')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="year" class="form-label fw-semibold">Año</label>
        <input 
            type="number" 
            class="form-control @error('year') is-invalid @enderror" 
            id="year" 
            name="year" 
            min="1900" 
            max="{{ date('Y') + 2 }}"
            value="{{ old('year', $vehicle->year) }}" 
            placeholder="2020"
        >
        @error('year')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="color" class="form-label fw-semibold">Color</label>
        <input 
            type="text" 
            class="form-control @error('color') is-invalid @enderror" 
            id="color" 
            name="color" 
            value="{{ old('color', $vehicle->color) }}" 
            placeholder="Ej. Blanco, Gris, Azul"
        >
        @error('color')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="current_mileage" class="form-label fw-semibold">Kilometraje Actual (km)</label>
        <input 
            type="number" 
            class="form-control @error('current_mileage') is-invalid @enderror" 
            id="current_mileage" 
            name="current_mileage" 
            min="0"
            value="{{ old('current_mileage', $vehicle->current_mileage ?? 0) }}" 
            placeholder="0"
        >
        @error('current_mileage')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="fuel_type" class="form-label fw-semibold">Tipo de Combustible</label>
        <select class="form-select @error('fuel_type') is-invalid @enderror" id="fuel_type" name="fuel_type">
            <option value="">-- No especificado --</option>
            <option value="gasolina" @selected(old('fuel_type', $vehicle->fuel_type) === 'gasolina')>Gasolina</option>
            <option value="diesel" @selected(old('fuel_type', $vehicle->fuel_type) === 'diesel')>Diésel (Gasoil)</option>
            <option value="glp" @selected(old('fuel_type', $vehicle->fuel_type) === 'glp')>GLP</option>
            <option value="gnc" @selected(old('fuel_type', $vehicle->fuel_type) === 'gnc')>GNC</option>
            <option value="hibrido" @selected(old('fuel_type', $vehicle->fuel_type) === 'hibrido')>Híbrido</option>
            <option value="electrico" @selected(old('fuel_type', $vehicle->fuel_type) === 'electrico')>Eléctrico</option>
        </select>
        @error('fuel_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label fw-semibold">Notas / Observaciones del Vehículo</label>
        <textarea 
            class="form-control @error('notes') is-invalid @enderror" 
            id="notes" 
            name="notes" 
            rows="2" 
            placeholder="Detalles sobre modificaciones, estado de carrocería, etc."
        >{{ old('notes', $vehicle->notes) }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    @if(isset($customer) && $customer)
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">Cancelar</a>
    @elseif($vehicle->customer_id)
        <a href="{{ route('customers.show', $vehicle->customer_id) }}" class="btn btn-outline-secondary">Cancelar</a>
    @else
        <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    @endif
    <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Vehículo</button>
</div>
