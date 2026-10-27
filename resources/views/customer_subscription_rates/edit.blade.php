@extends('layouts.app')

@section('title', 'Tarifas de Suscripción de Clientes')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center gap-2 pt-2 pb-2 mb-4 border-bottom">
    <div class="text-break">
        <x-page-heading section="subscription-plans" class="h3 mb-0">Tarifas de Suscripción de Clientes</x-page-heading>
        <p class="text-muted mb-0 small">Define el costo mensual por vehículo para cada tipo de cliente.</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <p class="text-muted">
            Las tarifas anuales se calculan con la política actual de {{ $annualBillingMonths }} meses. Los cambios se aplican a nuevas solicitudes;
            las solicitudes pendientes conservan su importe y se reemplazarán si ya no coincide con la tarifa vigente.
        </p>

        <form method="POST" action="{{ route('customer-subscription-rates.update') }}">
            @csrf
            @method('PUT')

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Tipo de vehículo</th>
                            @foreach($customerTypes as $customerType => $label)
                                <th scope="col">{{ $label }} (USD/mes)</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicleTypes as $vehicleType => $vehicleLabel)
                            <tr>
                                <th scope="row">{{ $vehicleLabel }}</th>
                                @foreach($customerTypes as $customerType => $label)
                                    @php
                                        $field = "rates.{$customerType}.{$vehicleType}";
                                        $storedRate = $rates->get("{$customerType}|{$vehicleType}")?->monthly_price;
                                    @endphp
                                    <td>
                                        <label class="visually-hidden" for="{{ $field }}">{{ $label }} - {{ $vehicleLabel }}</label>
                                        <input
                                            id="{{ $field }}"
                                            name="rates[{{ $customerType }}][{{ $vehicleType }}]"
                                            type="number"
                                            min="0"
                                            max="99999999.99"
                                            step="0.01"
                                            inputmode="decimal"
                                            required
                                            value="{{ old($field, $storedRate) }}"
                                            class="form-control @error($field) is-invalid @enderror"
                                        >
                                        @error($field)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary fw-semibold">Guardar tarifas</button>
            </div>
        </form>
    </div>
</div>
@endsection
