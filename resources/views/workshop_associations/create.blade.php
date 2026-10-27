@extends('layouts.app')

@section('title', 'Invitar cliente a asociarse')

@section('content')
<div class="container py-3" style="max-width: 720px;">
    <div class="mb-4">
        <h1 class="h3 fw-bold">Invitar cliente existente</h1>
        <p class="text-muted mb-0">El cliente debe tener una cuenta huérfana y aceptar la invitación desde su correo.</p>
    </div>

    <div class="alert alert-info">
        La asociación no traslada el historial de otros talleres. El cliente conserva su bitácora privada; este taller solo verá los mantenimientos que registre desde que acepte.
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('workshop-associations.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Correo de acceso del cliente</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Por privacidad, el sistema no confirma si ese correo está registrado.</div>
                </div>
                <button type="submit" class="btn btn-primary">Enviar invitación</button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Volver a clientes</a>
            </form>
        </div>
    </div>
</div>
@endsection
