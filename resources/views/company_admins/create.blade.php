@extends('layouts.app')

@section('title', 'Nuevo Administrador de Empresa')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Nuevo Administrador de Empresa</h1>
        <p class="text-muted mb-0 small">Registra un usuario con rol <code>company_admin</code> vinculado a un taller</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('company-admins.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('company-admins.store') }}" novalidate>
            @include('company_admins._form')
        </form>
    </div>
</div>
@endsection
