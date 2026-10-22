@extends('layouts.app')

@section('title', 'Nuevo Cliente')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Registrar Cliente</h1>
        <p class="text-muted mb-0 small">
            @if(auth()->user()->role === 'company_admin')
                Agrega un cliente particular o propietario de flota a <strong>{{ auth()->user()->company?->name }}</strong>
            @else
                Alta de cliente en el sistema
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('customers.store') }}" novalidate>
            @include('customers._form')
        </form>
    </div>
</div>
@endsection
