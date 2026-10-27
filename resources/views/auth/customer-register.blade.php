@extends('layouts.guest')

@section('title', 'Crear cuenta de cliente')

@section('content')
<div class="card auth-card shadow-lg border-0 bg-white p-4">
    <div class="card-body">
        <div class="text-center mb-4">
            <img src="{{ asset('images/autobitacoraLogo.png') }}" alt="AutoBitacora" height="80" class="mb-3 img-fluid">
            <h3 class="fw-bold text-dark mb-1">Crear cuenta de cliente</h3>
            <p class="text-muted small">Registra tus vehículos y administra tu bitácora sin estar asociado a un taller.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('customer.register.store') }}">
            @csrf
            <div class="mb-3">
                <label for="full_name" class="form-label small fw-semibold">Nombre completo</label>
                <input id="full_name" name="full_name" value="{{ old('full_name') }}" class="form-control" required maxlength="150" autocomplete="name">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" required autocomplete="email">
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label small fw-semibold">Teléfono (opcional)</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" class="form-control" maxlength="50" autocomplete="tel">
            </div>
            <div class="mb-3">
                <label for="customer_type" class="form-label small fw-semibold">Tipo de cuenta</label>
                <select id="customer_type" name="customer_type" class="form-select" required>
                    <option value="individual" @selected(old('customer_type', 'individual') === 'individual')>Propietario particular</option>
                    <option value="fleet_owner" @selected(old('customer_type') === 'fleet_owner')>Propietario de flota</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold">Contraseña</label>
                <input id="password" name="password" type="password" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label for="password_confirmation" class="form-label small fw-semibold">Confirmar contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <div class="mb-3 d-flex justify-content-center">
                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.key') }}" data-theme="light"></div>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-semibold">Crear cuenta y verificar correo</button>
        </form>

        <p class="text-center text-muted small mt-3 mb-0">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endpush
