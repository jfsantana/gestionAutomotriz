@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label for="full_name" class="form-label fw-semibold">Nombre Completo / Razón Social <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control @error('full_name') is-invalid @enderror" 
            id="full_name" 
            name="full_name" 
            value="{{ old('full_name', $customer->full_name) }}" 
            placeholder="Ej. Ramón Almonte / Transporte Express SRL"
            required
        >
        @error('full_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="customer_type" class="form-label fw-semibold">Tipo de Cliente <span class="text-danger">*</span></label>
        <select class="form-select @error('customer_type') is-invalid @enderror" id="customer_type" name="customer_type" required>
            <option value="individual" @selected(old('customer_type', $customer->customer_type) === 'individual')>Particular (Individual)</option>
            <option value="fleet_owner" @selected(old('customer_type', $customer->customer_type) === 'fleet_owner')>Propietario de Flota (Fleet Owner)</option>
        </select>
        @error('customer_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
        <input 
            type="email" 
            class="form-control @error('email') is-invalid @enderror" 
            id="email" 
            name="email" 
            value="{{ old('email', $customer->email) }}" 
            placeholder="cliente@ejemplo.com"
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label fw-semibold">Teléfono / Celular</label>
        <input 
            type="text" 
            class="form-control @error('phone') is-invalid @enderror" 
            id="phone" 
            name="phone" 
            value="{{ old('phone', $customer->phone) }}" 
            placeholder="Ej. 809-555-7890"
        >
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="document_type" class="form-label fw-semibold">Tipo de Documento</label>
        <select class="form-select @error('document_type') is-invalid @enderror" id="document_type" name="document_type">
            <option value="">-- No especificado --</option>
            <option value="cedula" @selected(old('document_type', $customer->document_type) === 'cedula')>Cédula de Identidad</option>
            <option value="rnc" @selected(old('document_type', $customer->document_type) === 'rnc')>RNC (Registro Nacional de Contribuyentes)</option>
            <option value="pasaporte" @selected(old('document_type', $customer->document_type) === 'pasaporte')>Pasaporte</option>
        </select>
        @error('document_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="document_number" class="form-label fw-semibold">Número de Documento</label>
        <input 
            type="text" 
            class="form-control @error('document_number') is-invalid @enderror" 
            id="document_number" 
            name="document_number" 
            value="{{ old('document_number', $customer->document_number) }}" 
            placeholder="Ej. 001-1234567-8"
        >
        @error('document_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if(auth()->user()->role === 'super_admin')
        <div class="col-md-12">
            <label for="company_id" class="form-label fw-semibold">Empresa / Taller Vinculado <span class="text-danger">*</span></label>
            <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                <option value="">-- Seleccionar Empresa --</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((string)old('company_id', $customer->company_id) === (string)$company->id)>
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
        <label for="notes" class="form-label fw-semibold">Notas / Observaciones del Cliente</label>
        <textarea 
            class="form-control @error('notes') is-invalid @enderror" 
            id="notes" 
            name="notes" 
            rows="2" 
            placeholder="Detalles sobre preferencias, contacto alternativo, etc."
        >{{ old('notes', $customer->notes) }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if(!$customer->exists || !$customer->user)
        <div class="col-12 pt-2">
            <div class="form-check form-switch p-3 bg-light rounded border">
                <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="enable_portal" name="enable_portal" value="1" @checked(old('enable_portal'))>
                <label class="form-check-label fw-bold text-dark" for="enable_portal">
                    Habilitar acceso al portal del cliente y enviar invitación por correo
                </label>
                <div class="form-text ms-4">
                    Requiere que el cliente tenga un correo electrónico registrado. El sistema enviará un enlace automático para que cree su contraseña.
                </div>
            </div>
        </div>
    @endif
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Cliente</button>
</div>
