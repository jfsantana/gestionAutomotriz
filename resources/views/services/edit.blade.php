@extends('layouts.app')

@section('title', 'Editar Servicio: ' . $service->name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Editar Servicio: {{ $service->name }}</h1>
        <p class="text-muted mb-0 small">Modifica precios de referencia o intervalos sugeridos</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('services.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Catálogo
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('services.update', $service) }}" novalidate>
            @method('PUT')
            @include('services._form')
        </form>
    </div>
</div>
@endsection
