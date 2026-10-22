@extends('layouts.app')

@section('title', 'Empresas y Talleres')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Empresas y Talleres</h1>
        <p class="text-muted mb-0 small">Administración SaaS de talleres y concesionarios afiliados</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('companies.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Nueva Empresa
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($companies->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay empresas registradas actualmente.</p>
                <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">Registrar la Primera Empresa</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Empresa</th>
                            <th scope="col">RNC / Tax ID</th>
                            <th scope="col">Contacto</th>
                            <th scope="col">Ubicación</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companies as $company)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $company->name }}</div>
                                    <small class="text-muted">slug: <code>{{ $company->slug }}</code></small>
                                </td>
                                <td>{{ $company->tax_id ?? '—' }}</td>
                                <td>
                                    @if($company->email)
                                        <div><small class="text-secondary">{{ $company->email }}</small></div>
                                    @endif
                                    @if($company->phone)
                                        <div><small class="text-secondary">{{ $company->phone }}</small></div>
                                    @endif
                                    @if(!$company->email && !$company->phone)
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $company->city ?? '—' }}{{ $company->state ? ', ' . $company->state : '' }}
                                    @if($company->country)
                                        <span class="badge bg-light text-dark border ms-1">{{ $company->country }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($company->status === 'active')
                                        <span class="badge bg-success">Activo</span>
                                    @elseif($company->status === 'trial')
                                        <span class="badge bg-warning text-dark">Prueba (Trial)</span>
                                    @else
                                        <span class="badge bg-danger">Suspendido</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('companies.show', $company) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                            Ver
                                        </a>
                                        <a href="{{ route('companies.edit', $company) }}" class="btn btn-outline-primary" title="Editar">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('companies.destroy', $company) }}" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta empresa?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($companies->hasPages())
                <div class="p-3 border-top">
                    {{ $companies->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
