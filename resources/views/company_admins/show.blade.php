@extends('layouts.app')

@section('title', 'Detalle: ' . $user->username)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="company-admins" class="h3 mb-0">{{ $user->name }}</x-page-heading>
        <p class="text-muted mb-0 small">Ficha del usuario administrador de empresa</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('company-admins.edit', $user) }}" class="btn btn-sm btn-primary">
            Editar Administrador
        </a>
        <a href="{{ route('company-admins.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Información del Usuario</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Nombre Completo</small>
                        <strong class="text-dark fs-6">{{ $user->name }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Usuario (Login)</small>
                        <code>{{ $user->username }}</code>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Correo Electrónico</small>
                        <span>{{ $user->email }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Rol en el Sistema</small>
                        <span class="badge bg-primary text-uppercase">{{ $user->role }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Estado de la Cuenta</small>
                        @if($user->is_active)
                            <span class="badge bg-success">Activo (Acceso habilitado)</span>
                        @else
                            <span class="badge bg-danger">Inactivo (Acceso bloqueado)</span>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Último Inicio de Sesión</small>
                        <span>{{ $user->last_login_at?->format('d/m/Y H:i:s') ?? 'Nunca ha iniciado sesión' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Taller / Concesionario Asignado</h5>
            </div>
            <div class="card-body">
                @if($user->company)
                    <div class="mb-2">
                        <small class="text-muted d-block">Nombre de la Empresa</small>
                        <strong class="text-dark">{{ $user->company->name }}</strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Identificador (Slug)</small>
                        <code>{{ $user->company->slug }}</code>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Estado de la Empresa</small>
                        @if($user->company->status === 'active')
                            <span class="badge bg-success">Activo</span>
                        @elseif($user->company->status === 'trial')
                            <span class="badge bg-warning text-dark">Prueba (Trial)</span>
                        @else
                            <span class="badge bg-danger">Suspendido</span>
                        @endif
                    </div>
                    <a href="{{ route('companies.show', $user->company) }}" class="btn btn-outline-primary btn-sm w-100">
                        Ver Ficha de la Empresa
                    </a>
                @else
                    <p class="text-danger small mb-0">Sin empresa vinculada.</p>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Auditoría del Registro</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-1">ID de Usuario: <strong>#{{ $user->id }}</strong></p>
                <p class="small text-muted mb-1">Registrado el: <strong>{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</strong></p>
                <p class="small text-muted mb-0">Última actualización: <strong>{{ $user->updated_at?->format('d/m/Y H:i') ?? '—' }}</strong></p>
            </div>
        </div>
    </div>
</div>
@endsection
