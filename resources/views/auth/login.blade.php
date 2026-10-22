@extends('layouts.guest')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="card auth-card shadow-lg border-0 bg-white p-4">
    <div class="card-body">
        <div class="text-center mb-4">
            <img src="{{ asset('images/autobitacoraLogo.png') }}" alt="AutoBitacora" height="58" class="mb-2">
            <h3 class="fw-bold text-dark mb-1">AutoBitacora</h3>
            <p class="text-muted small">SaaS de Gestión Vehicular &bull; Acceso al Sistema</p>
        </div>

        @if (session('info'))
            <div class="alert alert-info py-2 small" role="alert">
                {{ session('info') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label for="username" class="form-label fw-semibold text-secondary small">Usuario</label>
                <input 
                    type="text" 
                    class="form-control @error('username') is-invalid @enderror" 
                    id="username" 
                    name="username" 
                    value="{{ old('username') }}" 
                    placeholder="Ej. jsantana"
                    required 
                    autofocus
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold text-secondary small">Contraseña</label>
                <input 
                    type="password" 
                    class="form-control @error('password') is-invalid @enderror" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••"
                    required
                >
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label text-muted small" for="remember">Recordar mi sesión</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                Iniciar Sesión
            </button>
        </form>

        <div class="text-center mt-4 border-top pt-3">
            <span class="text-muted small">Plataforma Segura Multiempresa</span>
        </div>
    </div>
</div>
@endsection
