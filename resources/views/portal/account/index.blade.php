@extends('portal.layouts.client')

@section('title', 'Mi Cuenta y Seguridad')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
    <div>
        <x-page-heading section="account" class="h4 mb-0 fw-bold text-dark">Mi Cuenta y Seguridad</x-page-heading>
        <p class="text-muted mb-0 small">Administra tus datos personales y credenciales de acceso</p>
    </div>
</div>

<div class="row g-4" style="max-width: 900px;">
    {{-- DATOS PERSONALES --}}
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title mb-0 fw-bold">Información Personal</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('portal.account.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Correo Electrónico (Acceso)</label>
                        <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold small">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold small">Teléfono / Celular</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $customer?->phone) }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">Actualizar Datos</button>
                </form>
            </div>
        </div>
    </div>

    {{-- CAMBIO DE CONTRASEÑA --}}
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title mb-0 fw-bold">Cambiar Contraseña</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('portal.account.password') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold small">Contraseña Actual <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">Nueva Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-semibold small">Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>

                    <button type="submit" class="btn btn-outline-primary btn-sm px-3 fw-bold">Cambiar Contraseña</button>
                </form>
            </div>
        </div>
    </div>

    @if($customer && ! $customer->isFleetPremiumActive())
        <div class="col-12">
            @include('portal.vehicles._upgrade_card')
        </div>
    @endif

    <div class="col-12">
        <div class="card border-warning shadow-sm">
            <div class="card-header bg-warning-subtle py-2">
                <strong class="small">Relación con talleres</strong>
            </div>
            <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h6 class="fw-bold mb-1 text-dark">Taller actual: {{ $customer?->company?->name ?? 'Sin taller asociado' }}</h6>
                    <small class="text-muted">
                        Tu historial se conserva como auditoría. Los talleres solo consultan los mantenimientos que ellos registraron.
                    </small>
                </div>
                @if($customer?->company_id)
                    @if($customer->isFleetPremiumActive())
                        <form method="POST" action="{{ route('portal.account.workshop-relationship.end') }}" onsubmit="return confirm('¿Confirmas cerrar inmediatamente la relación con tu taller? Conservarás tus vehículos y tu historial, pero se eliminarán los precios registrados.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning btn-sm fw-bold">
                                Cerrar relación con mi taller
                            </button>
                        </form>
                    @else
                        <small class="text-muted">Para cerrar la relación desde el portal necesitas una suscripción de flota pagada y vigente.</small>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ZONA DE PELIGRO: DAR DE BAJA MI CUENTA --}}
    <div class="col-12">
        <div class="card border-danger shadow-sm">
            <div class="card-header bg-danger text-white py-2">
                <strong class="small">Zona de Peligro &bull; Baja Voluntaria de Cuenta</strong>
            </div>
            <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-1 text-dark">Dar de baja mi cuenta del portal</h6>
                    <small class="text-muted">Desactivará tu acceso al portal de clientes. No podrás volver a ingresar hasta ser reactivado.</small>
                </div>
                <form method="POST" action="{{ route('portal.account.deactivate') }}" onsubmit="return confirm('¿ATENCIÓN: Confirmas dar de baja tu cuenta? Se cerrará tu sesión inmediatamente y tu acceso quedará desactivado.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm fw-bold">
                        Solicitar Baja de Mi Cuenta
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
