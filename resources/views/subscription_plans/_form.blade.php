@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label fw-semibold">Nombre del Plan <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control @error('name') is-invalid @enderror" 
            id="name" 
            name="name" 
            value="{{ old('name', $plan->name) }}" 
            placeholder="Ej. Plan Taller Pro"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="slug" class="form-label fw-semibold">Slug (Identificador URL)</label>
        <input 
            type="text" 
            class="form-control @error('slug') is-invalid @enderror" 
            id="slug" 
            name="slug" 
            value="{{ old('slug', $plan->slug) }}" 
            placeholder="ej-plan-taller-pro"
        >
        <div class="form-text">Se autogenera si se deja vacío.</div>
        @error('slug')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="monthly_price" class="form-label fw-semibold">Precio Mensual ($) <span class="text-danger">*</span></label>
        <input 
            type="number" 
            step="0.01" 
            min="0"
            class="form-control @error('monthly_price') is-invalid @enderror" 
            id="monthly_price" 
            name="monthly_price" 
            value="{{ old('monthly_price', $plan->monthly_price ?? 0) }}" 
            required
        >
        @error('monthly_price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="yearly_price" class="form-label fw-semibold">Precio Anual ($)</label>
        <input 
            type="number" 
            step="0.01" 
            min="0"
            class="form-control @error('yearly_price') is-invalid @enderror" 
            id="yearly_price" 
            name="yearly_price" 
            value="{{ old('yearly_price', $plan->yearly_price ?? 0) }}" 
        >
        @error('yearly_price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="duration_days" class="form-label fw-semibold">Duración en Días (Trial)</label>
        <input 
            type="number" 
            min="1"
            class="form-control @error('duration_days') is-invalid @enderror" 
            id="duration_days" 
            name="duration_days" 
            value="{{ old('duration_days', $plan->duration_days) }}" 
            placeholder="Ej. 15, 30"
        >
        @error('duration_days')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="max_mechanics" class="form-label fw-semibold">Máximo de Mecánicos</label>
        <input 
            type="number" 
            min="1"
            class="form-control @error('max_mechanics') is-invalid @enderror" 
            id="max_mechanics" 
            name="max_mechanics" 
            value="{{ old('max_mechanics', $plan->max_mechanics) }}" 
            placeholder="Dejar vacío para ilimitado"
        >
        @error('max_mechanics')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="max_maintenance_orders_per_month" class="form-label fw-semibold">Órdenes Máximas / Mes</label>
        <input 
            type="number" 
            min="1"
            class="form-control @error('max_maintenance_orders_per_month') is-invalid @enderror" 
            id="max_maintenance_orders_per_month" 
            name="max_maintenance_orders_per_month" 
            value="{{ old('max_maintenance_orders_per_month', $plan->max_maintenance_orders_per_month) }}" 
            placeholder="Dejar vacío para ilimitado"
        >
        @error('max_maintenance_orders_per_month')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="max_vehicles" class="form-label fw-semibold">Vehículos Máximos</label>
        <input 
            type="number" 
            min="1"
            class="form-control @error('max_vehicles') is-invalid @enderror" 
            id="max_vehicles" 
            name="max_vehicles" 
            value="{{ old('max_vehicles', $plan->max_vehicles) }}" 
            placeholder="Dejar vacío para ilimitado"
        >
        @error('max_vehicles')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Descripción del Plan</label>
        <textarea 
            class="form-control @error('description') is-invalid @enderror" 
            id="description" 
            name="description" 
            rows="2" 
            placeholder="Detalles sobre beneficios, soporte incluido, etc."
        >{{ old('description', $plan->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 pt-2">
        <div class="form-check form-switch">
            <input type="hidden" name="is_trial" value="0">
            <input 
                class="form-check-input" 
                type="checkbox" 
                role="switch" 
                id="is_trial" 
                name="is_trial" 
                value="1" 
                @checked(old('is_trial', $plan->is_trial ?? false))
            >
            <label class="form-check-label fw-semibold" for="is_trial">
                Es Plan de Prueba Gratuito (Trial)
            </label>
        </div>
    </div>

    <div class="col-md-6 pt-2">
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input 
                class="form-check-input" 
                type="checkbox" 
                role="switch" 
                id="is_active" 
                name="is_active" 
                value="1" 
                @checked(old('is_active', $plan->is_active ?? true))
            >
            <label class="form-check-label fw-semibold" for="is_active">
                Plan Activo y Disponible para Asignación
            </label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    <a href="{{ route('subscription-plans.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Plan</button>
</div>
