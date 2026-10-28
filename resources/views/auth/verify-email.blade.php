@extends('layouts.guest')

@section('title', 'Verificar correo')

@section('content')
<div class="card auth-card shadow-lg border-0 bg-white p-4">
    <div class="card-body text-center">
        <h3 class="fw-bold text-dark mb-3">Verifica tu correo</h3>
        <p class="text-muted">Enviamos un enlace a <strong>{{ auth()->user()->email }}</strong>. Confirma tu dirección para acceder al portal.</p>

        @if ($errors->any())
            <div class="alert alert-danger small" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-success small">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-100">Reenviar enlace de verificación</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-link">Cerrar sesión</button>
        </form>
    </div>
</div>
@endsection
