@extends('layouts.app')

@section('title', 'Nuevo Plan SaaS')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <x-page-heading section="subscription-plans" class="h3 mb-0">Crear Plan de Suscripción</x-page-heading>
        <p class="text-muted mb-0 small">Define precios, límites y características para comercializar la plataforma</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('subscription-plans.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver a Planes
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('subscription-plans.store') }}" novalidate>
            @include('subscription_plans._form')
        </form>
    </div>
</div>
@endsection
