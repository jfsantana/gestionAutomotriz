@extends('layouts.app')

@section('title', 'Personal Operativo')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center gap-2 pt-2 pb-2 mb-4 border-bottom">
    <div class="text-break">
        <h1 class="h3 mb-0">Personal Operativo (Mecánicos)</h1>
        <p class="text-muted mb-0 small">
            @if(auth()->user()->role === 'company_admin')
                Gestión del equipo de trabajo de <strong>{{ auth()->user()->company?->name }}</strong>
            @else
                Gestión global de personal operativo multiempresa
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('staff.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Registrar Mecánico
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($staff->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay mecánicos registrados en este taller actualmente.</p>
                <a href="{{ route('staff.create') }}" class="btn btn-primary btn-sm">Registrar Primer Mecánico</a>
            </div>
        @else
            <ul class="d-md-none list-unstyled d-flex flex-column gap-2 p-2 mb-0 bg-body-tertiary" aria-label="Personal Operativo (Mecánicos)">
                @foreach($staff as $member)
                    <li class="card border p-2 text-break">
                        <h2 class="h6 fw-bold mb-1">{{ $member->name }}</h2>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <small class="text-muted">ID: #{{ $member->id }}</small>
                            <span class="badge bg-secondary text-uppercase text-wrap">{{ $member->role }}</span>
                            @if($member->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </div>
                        <div class="small">
                            <div><span class="text-muted">Usuario:</span> <span class="fw-semibold">{{ $member->username }}</span></div>
                            <div class="text-secondary">{{ $member->email }}</div>
                            @if(auth()->user()->role === 'super_admin')
                                <div><span class="text-muted">Empresa:</span> {{ $member->company?->name ?? '—' }}</div>
                            @endif
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('staff.edit', $member) }}" class="btn btn-sm btn-outline-primary flex-fill py-2" aria-label="Editar a {{ $member->name }}">
                                Editar
                            </a>
                            <form method="POST" action="{{ route('staff.toggle-status', $member) }}" class="flex-fill">
                                @csrf
                                @method('PATCH')
                                @if($member->is_active)
                                    <button type="submit" class="btn btn-sm btn-outline-warning w-100 py-2" title="Desactivar acceso" aria-label="Desactivar acceso de {{ $member->name }}">
                                        Desactivar
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sm btn-outline-success w-100 py-2" title="Activar acceso" aria-label="Activar acceso de {{ $member->name }}">
                                        Activar
                                    </button>
                                @endif
                            </form>
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
                            @if(auth()->user()->role === 'super_admin')
                                <th scope="col">Empresa</th>
                            @endif
                            <th scope="col">Rol</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staff as $member)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $member->username }}</div>
                                    <small class="text-muted">ID: #{{ $member->id }}</small>
                                </td>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->email }}</td>
                                @if(auth()->user()->role === 'super_admin')
                                    <td>
                                        @if($member->company)
                                            <span class="badge bg-light text-dark border">{{ $member->company->name }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <span class="badge bg-secondary text-uppercase">{{ $member->role }}</span>
                                </td>
                                <td>
                                    @if($member->is_active)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('staff.edit', $member) }}" class="btn btn-outline-primary">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('staff.toggle-status', $member) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            @if($member->is_active)
                                                <button type="submit" class="btn btn-outline-warning" title="Desactivar acceso">
                                                    Desactivar
                                                </button>
                                            @else
                                                <button type="submit" class="btn btn-outline-success" title="Activar acceso">
                                                    Activar
                                                </button>
                                            @endif
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($staff->hasPages())
                <div class="p-3 border-top">
                    {{ $staff->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
