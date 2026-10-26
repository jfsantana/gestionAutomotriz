@extends('layouts.app')

@section('title', 'Detalle: ' . $company->name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="companies" class="h3 mb-0">{{ $company->name }}</x-page-heading>
        <p class="text-muted mb-0 small">Ficha institucional del taller / concesionario</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-primary">
            Editar Empresa
        </a>
        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Resumen Operativo (Personal y Vehículos) --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm border-start border-primary border-4 bg-white">
                    <div class="card-body py-3">
                        <small class="text-muted text-uppercase fw-semibold d-block">Vehículos Registrados</small>
                        <div class="h3 mb-0 text-primary fw-bold">{{ $company->vehicles_count }}</div>
                        <small class="text-muted">En el taller</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm border-start border-info border-4 bg-white">
                    <div class="card-body py-3">
                        <small class="text-muted text-uppercase fw-semibold d-block">Clientes Registrados</small>
                        <div class="h3 mb-0 text-info text-dark fw-bold">{{ $company->customers_count }}</div>
                        <small class="text-muted">Propietarios y flotas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm border-start border-success border-4 bg-white">
                    <div class="card-body py-3">
                        <small class="text-muted text-uppercase fw-semibold d-block">Mecánicos / Personal</small>
                        <div class="h3 mb-0 text-success fw-bold">{{ $company->mechanics_count }}</div>
                        <small class="text-muted">Equipo operativo</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Información General</h5>
                @if($company->logo_path)
                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo Empresa" height="36" class="rounded border p-1">
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Nombre Comercial</small>
                        <strong class="text-dark fs-6">{{ $company->name }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Slug Identificador</small>
                        <code>{{ $company->slug }}</code>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">RNC / Cédula / Tax ID</small>
                        <span>{{ $company->tax_id ?? 'No especificado' }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Estado Operativo</small>
                        @if($company->status === 'active')
                            <span class="badge bg-success">Activo</span>
                        @elseif($company->status === 'trial')
                            <span class="badge bg-warning text-dark">Prueba (Trial)</span>
                        @else
                            <span class="badge bg-danger">Suspendido</span>
                        @endif
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Dirección Física</small>
                        <span>{{ $company->address ?? 'No registrada' }}</span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Ciudad</small>
                        <span>{{ $company->city ?? '—' }}</span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Provincia / Estado</small>
                        <span>{{ $company->state ?? '—' }}</span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">País</small>
                        <span>{{ $company->country ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Administrador(es) de la Empresa --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Administrador de la Empresa</h5>
                @if(auth()->user()->role === 'super_admin')
                    <a href="{{ route('company-admins.create', ['company_id' => $company->id]) }}" class="btn btn-sm btn-outline-primary">
                        + Asignar Administrador
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                @if($company->admins->isEmpty())
                    <p class="text-muted small p-3 mb-0">Esta empresa aún no tiene un usuario con rol <code>company_admin</code> asignado.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($company->admins as $adminUser)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark">{{ $adminUser->name }}</strong>
                                    <small class="text-muted d-block">Usuario: <code>{{ $adminUser->username }}</code> &bull; Correo: {{ $adminUser->email }}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $adminUser->is_active ? 'bg-success' : 'bg-danger' }}">
                                        {{ $adminUser->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                    @if(auth()->user()->role === 'super_admin')
                                        <a href="{{ route('company-admins.show', $adminUser) }}" class="btn btn-sm btn-outline-secondary">
                                            Ver Perfil
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Personal Operativo (Mecánicos) --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Personal Operativo (Mecánicos)</h5>
            </div>
            <div class="card-body p-0">
                @if($company->mechanics->isEmpty())
                    <p class="text-muted small p-3 mb-0">No hay mecánicos registrados en este taller.</p>
                @else
                    <ul class="list-group list-group-flush small">
                        @foreach($company->mechanics as $mech)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark">{{ $mech->name }}</strong> ({{ $mech->username }})
                                    <span class="text-muted d-block">{{ $mech->email }}</span>
                                </div>
                                <span class="badge {{ $mech->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $mech->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Presencia Digital y Redes</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Sitio Web</small>
                        @if($company->website)
                            <a href="{{ $company->website }}" target="_blank" class="text-decoration-none">{{ $company->website }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Instagram</small>
                        @if($company->instagram_url)
                            <a href="{{ $company->instagram_url }}" target="_blank" class="text-decoration-none">{{ $company->instagram_url }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Facebook</small>
                        @if($company->facebook_url)
                            <a href="{{ $company->facebook_url }}" target="_blank" class="text-decoration-none">{{ $company->facebook_url }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Contacto Directo</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">Correo Electrónico</small>
                        <span>{{ $company->email ?? 'No registrado' }}</span>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">Teléfono</small>
                        <span>{{ $company->phone ?? 'No registrado' }}</span>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">WhatsApp</small>
                        <span>{{ $company->whatsapp ?? 'No registrado' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Auditoría del Registro</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-1">ID interno: <strong>#{{ $company->id }}</strong></p>
                <p class="small text-muted mb-1">Registrado el: <strong>{{ $company->created_at?->format('d/m/Y H:i') ?? '—' }}</strong></p>
                <p class="small text-muted mb-3">Última actualización: <strong>{{ $company->updated_at?->format('d/m/Y H:i') ?? '—' }}</strong></p>

                <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('¿Confirmas eliminar esta empresa del sistema?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        Eliminar Empresa (Soft Delete)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
