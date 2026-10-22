@extends('layouts.app')

@section('title', 'Registrar Empresa')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Nueva Empresa o Taller</h1>
        <p class="text-muted mb-0 small">Registra un nuevo tenant en la plataforma SaaS</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('companies.store') }}" enctype="multipart/form-data" novalidate>
            @include('companies._form')
        </form>
    </div>
</div>
@endsection
