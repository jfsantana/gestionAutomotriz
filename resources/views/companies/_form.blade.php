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

    @if($company->exists)
        {{-- En EDICIÓN: El Slug es meramente informativo y protegido (NO editable) --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted">
                Identificador URL (Slug) &bull; <small class="text-danger fw-normal">Inmutable</small>
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted">/</span>
                <div class="form-control bg-light text-secondary d-flex align-items-center">
                    <code class="text-dark fw-bold">{{ $company->slug }}</code>
                </div>
            </div>
            <div class="form-text">El identificador fue generado en el registro y no puede modificarse para proteger los enlaces.</div>
        </div>
    @else
        {{-- En CREACIÓN: Campo autogenerable en vivo --}}
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
    @endif

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

    {{-- SECCIÓN DE IDENTIDAD VISUAL Y LOGOTIPO EN CREACIÓN Y EDICIÓN DE EMPRESAS --}}
    <div class="col-12 mt-4 pt-3 border-top">
        <h5 class="fw-bold text-dark mb-1">Identidad Visual y Logotipo</h5>
        <p class="text-muted small mb-3">Personaliza el logotipo y la paleta de colores institucional para esta empresa.</p>

        <div class="row g-3 align-items-center mb-3">
            <div class="col-md-3 text-center">
                <div class="p-2 bg-light rounded border d-flex justify-content-center align-items-center" style="height: 100px;">
                    @if($company->logo_path)
                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo Empresa" class="img-fluid" style="max-height: 80px; object-fit: contain;">
                    @else
                        <div class="text-muted small text-center">
                            <span class="fs-3 d-block">&#128663;</span>
                            Sin logotipo cargado
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-9">
                <label for="logo" class="form-label fw-semibold">Logotipo del Taller / Empresa (PNG, JPG, SVG)</label>
                <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" accept="image/*">
                <div class="form-text">Se mostrará en la barra de navegación del sistema. Máx 4MB.</div>
                @error('logo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <label class="form-label fw-semibold text-dark">Paleta de Colores Institucional</label>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check p-0 border rounded p-3 h-100 {{ ($company->theme_palette ?? 'ford_blue') === 'ford_blue' ? 'border-primary bg-light' : '' }}">
                    <input class="form-check-input ms-1" type="radio" name="theme_palette" id="pal_ford" value="ford_blue" @checked(old('theme_palette', $company->theme_palette ?? 'ford_blue') === 'ford_blue')>
                    <label class="form-check-label ms-4 w-100" for="pal_ford">
                        <strong class="d-block text-dark">Azul Ford</strong>
                        <div class="d-flex gap-1 my-1">
                            <span class="badge" style="background-color: #002c6c; color: white;">#002c6c</span>
                            <span class="badge" style="background-color: #1c64f2; color: white;">#1c64f2</span>
                        </div>
                        <small class="text-muted">Azul marino corporativo y elegante.</small>
                    </label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check p-0 border rounded p-3 h-100 {{ ($company->theme_palette ?? 'ford_blue') === 'honda_red' ? 'border-danger bg-light' : '' }}">
                    <input class="form-check-input ms-1" type="radio" name="theme_palette" id="pal_honda" value="honda_red" @checked(old('theme_palette', $company->theme_palette) === 'honda_red')>
                    <label class="form-check-label ms-4 w-100" for="pal_honda">
                        <strong class="d-block text-dark">Rojo Honda</strong>
                        <div class="d-flex gap-1 my-1">
                            <span class="badge" style="background-color: #cc0000; color: white;">#cc0000</span>
                            <span class="badge" style="background-color: #1e293b; color: white;">#1e293b</span>
                        </div>
                        <small class="text-muted">Rojo deportivo de alta energía.</small>
                    </label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check p-0 border rounded p-3 h-100 {{ ($company->theme_palette ?? 'ford_blue') === 'workshop_classic' ? 'border-secondary bg-light' : '' }}">
                    <input class="form-check-input ms-1" type="radio" name="theme_palette" id="pal_workshop" value="workshop_classic" @checked(old('theme_palette', $company->theme_palette) === 'workshop_classic')>
                    <label class="form-check-label ms-4 w-100" for="pal_workshop">
                        <strong class="d-block text-dark">Taller Clásico</strong>
                        <div class="d-flex gap-1 my-1">
                            <span class="badge" style="background-color: #2b2d42; color: white;">#2b2d42</span>
                            <span class="badge" style="background-color: #d90429; color: white;">#d90429</span>
                        </div>
                        <small class="text-muted">Gris grafito, rojo freno y plata.</small>
                    </label>
                </div>
            </div>
        </div>
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
