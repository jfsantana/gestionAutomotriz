@extends('layouts.app')

@section('title', 'Clientes y Propietarios')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center gap-2 pt-2 pb-2 mb-4 border-bottom">
    <div class="text-break">
        <x-page-heading section="customers" class="h3 mb-0">Clientes y Propietarios</x-page-heading>
        <p class="text-muted mb-0 small">
            @if(auth()->user()->role === 'company_admin')
                Cartera de clientes y vehículos de <strong>{{ auth()->user()->company?->name }}</strong>
            @else
                Gestión global de clientes multiempresa
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        @if(auth()->user()->role === 'company_admin')
            <a href="{{ route('workshop-associations.create') }}" class="btn btn-sm btn-outline-primary fw-semibold">
                Invitar cliente existente
            </a>
        @endif
        <a href="{{ route('customers.create') }}" class="btn btn-sm btn-primary fw-semibold">
            + Nuevo Cliente
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        @if($customers->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted mb-3">No hay clientes registrados en esta empresa todavía.</p>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Registrar Primer Cliente</a>
            </div>
        @else
            <ul class="d-md-none list-unstyled d-flex flex-column gap-2 p-2 mb-0 bg-body-tertiary" aria-label="Clientes y Propietarios">
                @foreach($customers as $customer)
                    <li class="card border p-2 text-break">
                        <h2 class="h6 fw-bold mb-1">{{ $customer->full_name }}</h2>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <small class="text-muted">ID: #{{ $customer->id }}</small>
                            @if($customer->customer_type === 'fleet_owner')
                                <span class="badge bg-info text-dark">Flota</span>
                            @else
                                <span class="badge bg-secondary">Particular</span>
                            @endif
                            <span class="badge {{ $customer->vehicles_count > 0 ? 'bg-primary' : 'bg-light text-secondary border' }}">
                                {{ $customer->vehicles_count }} {{ $customer->vehicles_count === 1 ? 'vehículo' : 'vehículos' }}
                            </span>
                        </div>
                        <div class="small">
                            @if($customer->phone)
                                <div><span class="text-muted">Tel.:</span> {{ $customer->phone }}</div>
                            @endif
                            @if($customer->email)
                                <div class="text-muted">{{ $customer->email }}</div>
                            @endif
                            @if(!$customer->phone && !$customer->email)
                                <div class="text-muted">Sin datos de contacto</div>
                            @endif
                            @if($customer->document_number)
                                <div><span class="text-muted">{{ strtoupper($customer->document_type ?? 'DOC') }}:</span> {{ $customer->document_number }}</div>
                            @endif
                            @if(auth()->user()->role === 'super_admin')
                                <div><span class="text-muted">Empresa:</span> {{ $customer->company?->name ?? '—' }}</div>
                            @endif
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary flex-fill py-2" aria-label="Ver ficha de {{ $customer->full_name }}">
                                Ver Ficha
                            </a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary flex-fill py-2" aria-label="Editar a {{ $customer->full_name }}">
                                Editar
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">Cliente</th>
                            <th scope="col">Contacto</th>
                            <th scope="col">Documento</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Vehículos</th>
                            @if(auth()->user()->role === 'super_admin')
                                <th scope="col">Empresa</th>
                            @endif
                            <th scope="col" class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $customer->full_name }}</div>
                                    <small class="text-muted">ID: #{{ $customer->id }}</small>
                                </td>
                                <td>
                                    @if($customer->phone)
                                        <div><small class="text-dark">{{ $customer->phone }}</small></div>
                                    @endif
                                    @if($customer->email)
                                        <div><small class="text-muted">{{ $customer->email }}</small></div>
                                    @endif
                                    @if(!$customer->phone && !$customer->email)
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($customer->document_number)
                                        <span class="badge bg-light text-dark border">
                                            {{ strtoupper($customer->document_type ?? 'DOC') }}: {{ $customer->document_number }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($customer->customer_type === 'fleet_owner')
                                        <span class="badge bg-info text-dark">Flota</span>
                                    @else
                                        <span class="badge bg-secondary">Particular</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $customer->vehicles_count > 0 ? 'bg-primary' : 'bg-light text-secondary border' }}">
                                        {{ $customer->vehicles_count }} {{ $customer->vehicles_count === 1 ? 'vehículo' : 'vehículos' }}
                                    </span>
                                </td>
                                @if(auth()->user()->role === 'super_admin')
                                    <td>
                                        <small class="text-muted">{{ $customer->company?->name ?? '—' }}</small>
                                    </td>
                                @endif
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">
                                            Ver Ficha
                                        </a>
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary">
                                            Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="p-3 border-top">
                    {{ $customers->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
