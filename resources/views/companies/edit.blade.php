@extends('layouts.app')

@section('title', 'Editar ' . $company->name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Editar Empresa: {{ $company->name }}</h1>
        <p class="text-muted mb-0 small">Modifica los datos del taller o concesionario</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('companies.show', $company) }}" class="btn btn-sm btn-outline-secondary me-2">
            Ver Detalle
        </a>
        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('companies.update', $company) }}" novalidate>
            @method('PUT')
            @include('companies._form')
        </form>
    </div>
</div>
@endsection
