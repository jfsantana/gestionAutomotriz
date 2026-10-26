@extends('layouts.app')

@section('title', 'Usuarios Empresa (Administradores)')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center gap-2 pt-2 pb-2 mb-4 border-bottom">
    <div class="text-break">
        <h1 class="h3 mb-0">Administradores de Empresa</h1>
        <p class="text-muted mb-0 small">Gestión de usuarios con rol <code>company_admin</code> vinculados a talleres y concesionarios</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('company-admins.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Nuevo Administrador
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($admins->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay administradores de empresa registrados aún.</p>
                <a href="{{ route('company-admins.create') }}" class="btn btn-primary btn-sm">Crear Primer Administrador</a>
            </div>
        @else
            <ul class="d-md-none list-unstyled d-flex flex-column gap-2 p-2 mb-0 bg-body-tertiary" aria-label="Administradores de Empresa">
                @foreach($admins as $admin)
                    <li class="card border p-2 text-break">
                        <h2 class="h6 fw-bold mb-1">{{ $admin->name }}</h2>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <small class="text-muted">ID: #{{ $admin->id }}</small>
                            @if($admin->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </div>
                        <div class="small">
                            <div><span class="text-muted">Usuario:</span> <span class="fw-semibold">{{ $admin->username }}</span></div>
                            <div class="text-secondary">{{ $admin->email }}</div>
                            <div>
                                <span class="text-muted">Empresa:</span>
                                @if($admin->company)
                                    <a href="{{ route('companies.show', $admin->company) }}" class="text-decoration-none fw-semibold">
                                        {{ $admin->company->name }}
                                    </a>
                                @else
                                    <span class="text-danger">Sin empresa</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('company-admins.show', $admin) }}" class="btn btn-sm btn-outline-secondary flex-fill py-2" aria-label="Ver administrador {{ $admin->name }}">
                                Ver
                            </a>
                            <a href="{{ route('company-admins.edit', $admin) }}" class="btn btn-sm btn-outline-primary flex-fill py-2" aria-label="Editar administrador {{ $admin->name }}">
                                Editar
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Usuario</th>
                            <th scope="col">Nombre Completo</th>
                            <th scope="col">Correo Electrónico</th>
                            <th scope="col">Empresa Asignada</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($admins as $admin)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $admin->username }}</div>
                                    <small class="text-muted">ID: #{{ $admin->id }}</small>
                                </td>
                                <td>{{ $admin->name }}</td>
                                <td>{{ $admin->email }}</td>
                                <td>
                                    @if($admin->company)
                                        <a href="{{ route('companies.show', $admin->company) }}" class="text-decoration-none fw-semibold">
                                            {{ $admin->company->name }}
                                        </a>
                                    @else
                                        <span class="text-danger small">Sin empresa</span>
                                    @endif
                                </td>
                                <td>
                                    @if($admin->is_active)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('company-admins.show', $admin) }}" class="btn btn-outline-secondary">
                                            Ver
                                        </a>
                                        <a href="{{ route('company-admins.edit', $admin) }}" class="btn btn-outline-primary">
                                            Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($admins->hasPages())
                <div class="p-3 border-top">
                    {{ $admins->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
