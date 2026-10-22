@extends('layouts.app')

@section('title', 'Agregar Vehículo a ' . $customer->full_name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Nuevo Vehículo</h1>
        <p class="text-muted mb-0 small">
            Asignado al cliente: <strong>{{ $customer->full_name }}</strong> 
            @if($customer->document_number)
                ({{ strtoupper($customer->document_type ?? 'DOC') }}: {{ $customer->document_number }})
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Cliente
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('vehicles.store') }}" novalidate>
            @include('vehicles._form')
        </form>
    </div>
</div>
@endsection
