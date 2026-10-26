@extends('layouts.app')

@section('title', 'Editar Administrador: ' . $user->username)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="company-admins" class="h3 mb-0">Editar Administrador: {{ $user->username }}</x-page-heading>
        <p class="text-muted mb-0 small">Actualiza los datos del usuario o restablece su contraseña</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('company-admins.show', $user) }}" class="btn btn-sm btn-outline-secondary">
            Ver Detalle
        </a>
        <a href="{{ route('company-admins.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('company-admins.update', $user) }}" novalidate>
            @method('PUT')
            @include('company_admins._form')
        </form>
    </div>
</div>
@endsection
