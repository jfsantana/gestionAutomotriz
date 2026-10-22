@extends('layouts.app')

@section('title', 'Clientes y Propietarios')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h3 mb-0">Clientes y Propietarios</h1>
        <p class="text-muted mb-0 small">
            @if(auth()->user()->role === 'company_admin')
                Cartera de clientes y vehículos de <strong>{{ auth()->user()->company?->name }}</strong>
            @else
                Gestión global de clientes multiempresa
            @endif
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
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
            <div class="table-responsive">
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
