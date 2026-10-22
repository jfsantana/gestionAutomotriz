@extends('layouts.app')

@section('title', 'Registrar Personal')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Registrar Personal Operativo</h1>
        <p class="text-muted mb-0 small">
            @if(auth()->user()->role === 'company_admin')
                Nuevo miembro del equipo para <strong>{{ auth()->user()->company?->name }}</strong>
            @else
                Nuevo usuario operativo en el sistema
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('staff.index') }}" class="btn btn-sm btn-outline-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('staff.store') }}" novalidate>
            @include('staff._form')
        </form>
    </div>
</div>
@endsection
