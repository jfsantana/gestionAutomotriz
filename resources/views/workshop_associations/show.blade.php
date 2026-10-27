@extends('portal.layouts.client')

@section('title', 'Invitación de taller')

@section('content')
<div class="card shadow-sm border-0 mx-auto" style="max-width: 720px;">
    <div class="card-body p-4">
        <h1 class="h4 fw-bold">Invitación para asociarte a un taller</h1>
        <p><strong>{{ $invitation->company->name }}</strong> te invita a asociar tu cuenta y tus vehículos.</p>
        <div class="alert alert-info">
            Al aceptar, el taller verá únicamente los mantenimientos que registre desde esta asociación. Tu historial anterior seguirá disponible solo para ti y no mostrará precios.
        </div>
        <p>La invitación vence el {{ $invitation->expires_at->format('d/m/Y H:i') }}.</p>
        <form method="POST" action="{{ route('workshop-associations.accept', ['token' => $token]) }}">
            @csrf
            <button type="submit" class="btn btn-primary">Aceptar invitación</button>
            <a href="{{ route('portal.account') }}" class="btn btn-outline-secondary">Ahora no</a>
        </form>
    </div>
</div>
@endsection
