@extends('layouts.app')

@section('title', 'Identidad y Perfil del Taller')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Identidad y Perfil del Taller</h1>
        <p class="text-muted mb-0 small">Configura el logotipo, la información de contacto y la paleta de colores institucional</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Panel
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('company.profile.update') }}" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')

            <h5 class="fw-bold text-dark mb-3">1. Logotipo y Marca</h5>
            <div class="row g-3 mb-4 align-items-center">
                <div class="col-md-3 text-center">
                    <div class="p-3 bg-light rounded border d-flex justify-content-center align-items-center" style="height: 120px;">
                        @if($company->logo_path)
                            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo Taller" class="img-fluid" style="max-height: 90px; object-fit: contain;">
                        @else
                            <div class="text-muted small text-center">
                                <span class="fs-2 d-block">&#128663;</span>
                                Sin logotipo
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-9">
                    <label for="logo" class="form-label fw-semibold">Subir Logotipo del Taller (PNG, JPG, SVG)</label>
                    <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" accept="image/*">
                    <div class="form-text">
                        Este logotipo se mostrará en el menú principal y en la barra de navegación de tu taller. Tamaño recomendado: 240x80 px (máx 4MB).
                    </div>
                    @error('logo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            <h5 class="fw-bold text-dark mb-3">2. Paleta de Colores Institucional</h5>
            <p class="text-muted small mb-3">Selecciona la combinación de colores que mejor representa tu marca o taller:</p>
            <div class="row g-3 mb-4">
                {{-- Paleta 1: Ford Blue --}}
                <div class="col-md-4">
                    <div class="form-check p-0 border rounded p-3 h-100 {{ $company->theme_palette === 'ford_blue' ? 'border-primary bg-light' : '' }}">
                        <input class="form-check-input ms-1" type="radio" name="theme_palette" id="palette_ford" value="ford_blue" @checked(old('theme_palette', $company->theme_palette) === 'ford_blue')>
                        <label class="form-check-label ms-4 w-100" for="palette_ford">
                            <strong class="d-block text-dark">Azul Ford (Ford Blue)</strong>
                            <div class="d-flex gap-2 my-2">
                                <span class="badge py-2 px-3" style="background-color: #002c6c; color: white;">Azul Marino</span>
                                <span class="badge py-2 px-3" style="background-color: #1c64f2; color: white;">Azul Eléctrico</span>
                                <span class="badge py-2 px-3" style="background-color: #f1f5f9; color: #002c6c; border: 1px solid #ccc;">Blanco Frío</span>
                            </div>
                            <small class="text-muted">Inspirada en tonos ejecutivos y corporativos automotrices.</small>
                        </label>
                    </div>
                </div>

                {{-- Paleta 2: Honda Red --}}
                <div class="col-md-4">
                    <div class="form-check p-0 border rounded p-3 h-100 {{ $company->theme_palette === 'honda_red' ? 'border-danger bg-light' : '' }}">
                        <input class="form-check-input ms-1" type="radio" name="theme_palette" id="palette_honda" value="honda_red" @checked(old('theme_palette', $company->theme_palette) === 'honda_red')>
                        <label class="form-check-label ms-4 w-100" for="palette_honda">
                            <strong class="d-block text-dark">Rojo Honda (Honda Racing)</strong>
                            <div class="d-flex gap-2 my-2">
                                <span class="badge py-2 px-3" style="background-color: #cc0000; color: white;">Rojo Honda</span>
                                <span class="badge py-2 px-3" style="background-color: #1e293b; color: white;">Gris Oscuro</span>
                                <span class="badge py-2 px-3" style="background-color: #ffffff; color: #cc0000; border: 1px solid #ccc;">Blanco</span>
                            </div>
                            <small class="text-muted">Enfoque dinámico, deportivo y de alta visibilidad.</small>
                        </label>
                    </div>
                </div>

                {{-- Paleta 3: Taller Clásico --}}
                <div class="col-md-4">
                    <div class="form-check p-0 border rounded p-3 h-100 {{ $company->theme_palette === 'workshop_classic' ? 'border-secondary bg-light' : '' }}">
                        <input class="form-check-input ms-1" type="radio" name="theme_palette" id="palette_workshop" value="workshop_classic" @checked(old('theme_palette', $company->theme_palette) === 'workshop_classic')>
                        <label class="form-check-label ms-4 w-100" for="palette_workshop">
                            <strong class="d-block text-dark">Taller Mecánico Clásico</strong>
                            <div class="d-flex gap-2 my-2">
                                <span class="badge py-2 px-3" style="background-color: #2b2d42; color: white;">Grafito</span>
                                <span class="badge py-2 px-3" style="background-color: #d90429; color: white;">Rojo Freno</span>
                                <span class="badge py-2 px-3" style="background-color: #edf2f4; color: #2b2d42; border: 1px solid #ccc;">Gris Plata</span>
                            </div>
                            <small class="text-muted">Estilo clásico de taller mecánico y servicio pesado.</small>
                        </label>
                    </div>
                </div>
            </div>

            <hr>

            <h5 class="fw-bold text-dark mb-3">3. Datos de la Empresa (Campos Protegidos y Editables)</h5>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted">Identificador de URL (Slug) &bull; <small class="text-danger">Bloqueado</small></label>
                    <input type="text" class="form-control bg-light" value="{{ $company->slug }}" disabled>
                    <div class="form-text">El slug no puede ser alterado para proteger los enlaces del sistema.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted">RNC / Cédula / Tax ID &bull; <small class="text-danger">Bloqueado</small></label>
                    <input type="text" class="form-control bg-light" value="{{ $company->tax_id ?? 'No registrado' }}" disabled>
                    <div class="form-text">Para cambios en el RNC/cédula contacta al Superadministrador.</div>
                </div>

                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">Nombre Comercial <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $company->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">Correo Electrónico de Contacto</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $company->email) }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label fw-semibold">Teléfono</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $company->phone) }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="whatsapp" class="form-label fw-semibold">WhatsApp de Atención</label>
                    <input type="text" class="form-control @error('whatsapp') is-invalid @enderror" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $company->whatsapp) }}">
                    @error('whatsapp')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="city" class="form-label fw-semibold">Ciudad</label>
                    <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $company->city) }}">
                </div>

                <div class="col-md-4">
                    <label for="state" class="form-label fw-semibold">Provincia / Estado</label>
                    <input type="text" class="form-control @error('state') is-invalid @enderror" id="state" name="state" value="{{ old('state', $company->state) }}">
                </div>

                <div class="col-md-4">
                    <label for="country" class="form-label fw-semibold">País (ISO 2 letras)</label>
                    <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" maxlength="2" value="{{ old('country', $company->country ?? 'DO') }}">
                </div>

                <div class="col-12">
                    <label for="address" class="form-label fw-semibold">Dirección Física del Taller</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $company->address) }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Cambios de Identidad</button>
            </div>
        </form>
    </div>
</div>
@endsection
