@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label fw-semibold">Nombre de la Empresa / Taller <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control @error('name') is-invalid @enderror" 
            id="name" 
            name="name" 
            value="{{ old('name', $company->name) }}" 
            placeholder="Ej. Taller Mecánico Los Prados"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="slug" class="form-label fw-semibold">
            Slug (Identificador URL) 
            <small class="text-muted fw-normal">(se autogenera desde el nombre si se deja vacío)</small>
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light text-muted">/</span>
            <input 
                type="text" 
                class="form-control @error('slug') is-invalid @enderror" 
                id="slug" 
                name="slug" 
                value="{{ old('slug', $company->slug) }}" 
                placeholder="ej-taller-los-prados"
            >
            <button class="btn btn-outline-secondary" type="button" id="btn-regenerate-slug" title="Generar automáticamente">
                &#8635; Generar
            </button>
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-text">Solo minúsculas, números y guiones. Se autogenera si se deja en blanco.</div>
    </div>

    <div class="col-md-4">
        <label for="tax_id" class="form-label fw-semibold">RNC / Cédula / Tax ID</label>
        <input 
            type="text" 
            class="form-control @error('tax_id') is-invalid @enderror" 
            id="tax_id" 
            name="tax_id" 
            value="{{ old('tax_id', $company->tax_id) }}" 
            placeholder="Ej. 130123456"
        >
        @error('tax_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="status" class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            <option value="trial" @selected(old('status', $company->status) === 'trial')>Prueba (Trial)</option>
            <option value="active" @selected(old('status', $company->status) === 'active')>Activo (Active)</option>
            <option value="suspended" @selected(old('status', $company->status) === 'suspended')>Suspendido (Suspended)</option>
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
        <input 
            type="email" 
            class="form-control @error('email') is-invalid @enderror" 
            id="email" 
            name="email" 
            value="{{ old('email', $company->email) }}" 
            placeholder="contacto@empresa.com"
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label fw-semibold">Teléfono</label>
        <input 
            type="text" 
            class="form-control @error('phone') is-invalid @enderror" 
            id="phone" 
            name="phone" 
            value="{{ old('phone', $company->phone) }}" 
            placeholder="Ej. 809-555-0101"
        >
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="whatsapp" class="form-label fw-semibold">WhatsApp</label>
        <input 
            type="text" 
            class="form-control @error('whatsapp') is-invalid @enderror" 
            id="whatsapp" 
            name="whatsapp" 
            value="{{ old('whatsapp', $company->whatsapp) }}" 
            placeholder="Ej. 829-555-0102"
        >
        @error('whatsapp')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="website" class="form-label fw-semibold">Sitio Web</label>
        <input 
            type="url" 
            class="form-control @error('website') is-invalid @enderror" 
            id="website" 
            name="website" 
            value="{{ old('website', $company->website) }}" 
            placeholder="https://ejemplo.com"
        >
        @error('website')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="instagram_url" class="form-label fw-semibold">Instagram URL</label>
        <input 
            type="url" 
            class="form-control @error('instagram_url') is-invalid @enderror" 
            id="instagram_url" 
            name="instagram_url" 
            value="{{ old('instagram_url', $company->instagram_url) }}" 
            placeholder="https://instagram.com/taller"
        >
        @error('instagram_url')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="facebook_url" class="form-label fw-semibold">Facebook URL</label>
        <input 
            type="url" 
            class="form-control @error('facebook_url') is-invalid @enderror" 
            id="facebook_url" 
            name="facebook_url" 
            value="{{ old('facebook_url', $company->facebook_url) }}" 
            placeholder="https://facebook.com/taller"
        >
        @error('facebook_url')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="city" class="form-label fw-semibold">Ciudad</label>
        <input 
            type="text" 
            class="form-control @error('city') is-invalid @enderror" 
            id="city" 
            name="city" 
            value="{{ old('city', $company->city) }}" 
            placeholder="Ej. Santo Domingo"
        >
        @error('city')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="state" class="form-label fw-semibold">Provincia / Estado</label>
        <input 
            type="text" 
            class="form-control @error('state') is-invalid @enderror" 
            id="state" 
            name="state" 
            value="{{ old('state', $company->state) }}" 
            placeholder="Ej. Distrito Nacional"
        >
        @error('state')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="country" class="form-label fw-semibold">País (Código ISO 2 letras)</label>
        <input 
            type="text" 
            class="form-control @error('country') is-invalid @enderror" 
            id="country" 
            name="country" 
            maxlength="2"
            value="{{ old('country', $company->country ?? 'DO') }}" 
            placeholder="DO"
        >
        @error('country')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="address" class="form-label fw-semibold">Dirección Física</label>
        <textarea 
            class="form-control @error('address') is-invalid @enderror" 
            id="address" 
            name="address" 
            rows="2"
            placeholder="Av. Principal #123, Sector Industrial"
        >{{ old('address', $company->address) }}</textarea>
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Empresa</button>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        const btnRegenerate = document.getElementById('btn-regenerate-slug');

        function slugify(text) {
            return text
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        let isManualSlug = slugInput.value.trim().length > 0;

        nameInput.addEventListener('input', function () {
            if (!isManualSlug) {
                slugInput.value = slugify(nameInput.value);
            }
        });

        slugInput.addEventListener('input', function () {
            isManualSlug = slugInput.value.trim().length > 0;
        });

        if (btnRegenerate) {
            btnRegenerate.addEventListener('click', function () {
                slugInput.value = slugify(nameInput.value);
                isManualSlug = false;
            });
        }
    });
</script>
@endpush
