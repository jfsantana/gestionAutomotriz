@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label fw-semibold">Nombre del Servicio <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control @error('name') is-invalid @enderror" 
            id="name" 
            name="name" 
            value="{{ old('name', $service->name) }}" 
            placeholder="Ej. Cambio de Aceite y Filtro"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="maintenance_type" class="form-label fw-semibold">Tipo de Mantenimiento <span class="text-danger">*</span></label>
        <select class="form-select @error('maintenance_type') is-invalid @enderror" id="maintenance_type" name="maintenance_type" required>
            <option value="preventive" @selected(old('maintenance_type', $service->maintenance_type) === 'preventive')>Preventivo (Preventive)</option>
            <option value="corrective" @selected(old('maintenance_type', $service->maintenance_type) === 'corrective')>Correctivo (Corrective)</option>
        </select>
        @error('maintenance_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="default_interval_days" class="form-label fw-semibold">Intervalo Sugerido de Control (Días)</label>
        <input 
            type="number" 
            min="1"
            class="form-control @error('default_interval_days') is-invalid @enderror" 
            id="default_interval_days" 
            name="default_interval_days" 
            value="{{ old('default_interval_days', $service->default_interval_days) }}" 
            placeholder="Ej. 90, 180, 365"
        >
        <div class="form-text">Días estimados recomendados para el próximo control o revisión.</div>
        @error('default_interval_days')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="default_interval_mileage" class="form-label fw-semibold">Intervalo Sugerido de Control (Kilómetros)</label>
        <input 
            type="number" 
            min="1"
            class="form-control @error('default_interval_mileage') is-invalid @enderror" 
            id="default_interval_mileage" 
            name="default_interval_mileage" 
            value="{{ old('default_interval_mileage', $service->default_interval_mileage) }}" 
            placeholder="Ej. 5000, 10000"
        >
        <div class="form-text">Kilómetros sugeridos para la próxima revisión técnica.</div>
        @error('default_interval_mileage')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
        <div class="form-text">Km recomendados para próxima revisión.</div>
        @error('default_interval_mileage')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if(auth()->user()->role === 'super_admin')
        <div class="col-md-12">
            <label for="company_id" class="form-label fw-semibold">Empresa / Taller Vinculado <span class="text-danger">*</span></label>
            <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                <option value="">-- Seleccionar Empresa --</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((string)old('company_id', $service->company_id) === (string)$company->id)>
                        {{ $company->name }} ({{ $company->slug }})
                    </option>
                @endforeach
            </select>
            @error('company_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @endif

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Descripción del Servicio</label>
        <textarea 
            class="form-control @error('description') is-invalid @enderror" 
            id="description" 
            name="description" 
            rows="2" 
            placeholder="Detalles sobre lo que incluye este procedimiento técnico..."
        >{{ old('description', $service->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 pt-2">
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input 
                class="form-check-input" 
                type="checkbox" 
                role="switch" 
                id="is_active" 
                name="is_active" 
                value="1" 
                @checked(old('is_active', $service->is_active ?? true))
            >
            <label class="form-check-label fw-semibold" for="is_active">
                Servicio Activo en el Catálogo
            </label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Servicio</button>
</div>
