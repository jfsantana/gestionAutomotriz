@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label fw-semibold">Nombre Completo <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control @error('name') is-invalid @enderror" 
            id="name" 
            name="name" 
            value="{{ old('name', $user->name) }}" 
            placeholder="Ej. Roberto Sánchez"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="username" class="form-label fw-semibold">Usuario (Login) <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control @error('username') is-invalid @enderror" 
            id="username" 
            name="username" 
            value="{{ old('username', $user->username) }}" 
            placeholder="ej. rsanchez"
            required
        >
        <div class="form-text">Nombre de usuario único para acceso al sistema.</div>
        @error('username')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label fw-semibold">Correo Electrónico <span class="text-danger">*</span></label>
        <input 
            type="email" 
            class="form-control @error('email') is-invalid @enderror" 
            id="email" 
            name="email" 
            value="{{ old('email', $user->email) }}" 
            placeholder="mecanico@taller.com"
            required
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="role" class="form-label fw-semibold">Rol Operativo <span class="text-danger">*</span></label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
            <option value="mechanic" @selected(old('role', $user->role) === 'mechanic')>Mecánico (Operativo)</option>
        </select>
        @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if(auth()->user()->role === 'super_admin')
        <div class="col-md-6">
            <label for="company_id" class="form-label fw-semibold">Empresa / Taller Vinculado <span class="text-danger">*</span></label>
            <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id" required>
                <option value="">-- Seleccionar Empresa --</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((string)old('company_id', $user->company_id) === (string)$company->id)>
                        {{ $company->name }} ({{ $company->slug }})
                    </option>
                @endforeach
            </select>
            @error('company_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    @else
        {{-- Para company_admin la empresa se asigna automáticamente --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted">Empresa Asignada</label>
            <input type="text" class="form-control bg-light" value="{{ auth()->user()->company?->name ?? 'Mi Empresa' }}" disabled>
            <div class="form-text">Asignado de forma automática a tu taller.</div>
        </div>
    @endif

    <div class="col-md-6">
        <label for="password" class="form-label fw-semibold">
            Contraseña 
            @if($user->exists)
                <small class="text-muted fw-normal">(Dejar en blanco si no deseas cambiarla)</small>
            @else
                <span class="text-danger">*</span>
            @endif
        </label>
        <input 
            type="password" 
            class="form-control @error('password') is-invalid @enderror" 
            id="password" 
            name="password" 
            placeholder="Mínimo 8 caracteres"
            @if(!$user->exists) required @endif
        >
        @error('password')
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
                @checked(old('is_active', $user->is_active ?? true))
            >
            <label class="form-check-label fw-semibold" for="is_active">
                Personal Activo (Permite iniciar sesión en el sistema)
            </label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Personal</button>
</div>
