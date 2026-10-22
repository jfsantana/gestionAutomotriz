@extends('layouts.app')

@section('title', 'Editar Cliente: ' . $customer->full_name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Editar Cliente: {{ $customer->full_name }}</h1>
        <p class="text-muted mb-0 small">Modifica los datos de contacto o documentación</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary">
            Ver Ficha
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('customers.update', $customer) }}" novalidate>
            @method('PUT')
            @include('customers._form')
        </form>
    </div>
</div>
@endsection
