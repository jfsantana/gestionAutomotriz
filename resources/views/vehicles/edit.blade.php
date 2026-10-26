@extends('layouts.app')

@section('title', 'Editar Vehículo: ' . $vehicle->plate)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="vehicles" class="h3 mb-0">Editar Vehículo: {{ $vehicle->plate }}</x-page-heading>
        <p class="text-muted mb-0 small">
            Propietario: <strong>{{ $vehicle->customer?->full_name }}</strong> &bull; {{ $vehicle->make }} {{ $vehicle->model }}
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-secondary">
            Ver Ficha
        </a>
        <a href="{{ route('customers.show', $vehicle->customer_id) }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Cliente
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('vehicles.update', $vehicle) }}" enctype="multipart/form-data" novalidate>
            @method('PUT')
            @include('vehicles._form')
        </form>
    </div>
</div>
@endsection
