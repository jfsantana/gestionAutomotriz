<small class="text-muted text-uppercase fw-bold d-block px-2 mb-2" style="font-size: 0.72rem;">Mi Cuenta</small>
<nav class="nav flex-column">
    <a href="{{ route('portal.dashboard') }}" class="client-nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
        <x-section-icon name="dashboard" class="client-nav-icon" /> Inicio / Resumen
    </a>
    <a href="{{ route('portal.vehicles.index') }}" class="client-nav-link {{ request()->routeIs('portal.vehicles.*') ? 'active' : '' }}">
        <x-section-icon name="vehicles" class="client-nav-icon" /> Mis Vehículos
    </a>
    <a href="{{ route('portal.maintenance.index') }}" class="client-nav-link {{ request()->routeIs('portal.maintenance.index') ? 'active' : '' }}">
        <x-section-icon name="maintenance-orders" class="client-nav-icon" /> Historial de Servicios
    </a>
    <a href="{{ route('portal.maintenance.create') }}" class="client-nav-link {{ request()->routeIs('portal.maintenance.create') ? 'active' : '' }}">
        <x-section-icon name="personal-maintenance" class="client-nav-icon" /> Registrar Servicio Propio
    </a>
    <a href="{{ route('portal.account') }}" class="client-nav-link {{ request()->routeIs('portal.account') ? 'active' : '' }}">
        <x-section-icon name="account" class="client-nav-icon" /> Mi Cuenta y Clave
    </a>
</nav>

{{-- ENLACE FIJO A LAS POLÍTICAS Y CONDICIONES ACEPTADAS --}}
@if(isset($currentPlatformPolicy) && $currentPlatformPolicy)
    <div class="mt-4 pt-3 border-top px-2 text-center">
        <button
            type="button"
            class="btn btn-link p-0 text-decoration-none text-muted small d-flex align-items-center justify-content-center w-100"
            data-bs-toggle="modal"
            data-bs-target="#viewAcceptedPolicyModal"
            title="Ver Políticas de Uso y Condiciones aceptadas"
        >
            <x-section-icon name="policies" class="client-nav-icon" style="font-size: 1rem;" />
            <span style="font-size: 0.78rem;">Políticas y Condiciones</span>
        </button>
        @if(isset($userPolicyAcceptance) && $userPolicyAcceptance)
            <div class="text-muted" style="font-size: 0.68rem; margin-top: 2px;">
                v{{ $currentPlatformPolicy->version }} &bull; Aceptado el {{ $userPolicyAcceptance->accepted_at?->format('d/m/Y') }}
            </div>
        @endif
    </div>
@endif

<div class="mt-4 pt-3 border-top px-2 text-muted" style="font-size: 0.75rem;">
    <p class="mb-1 fw-semibold text-dark">AutoBitacora SaaS</p>
    <p class="mb-0">Entorno privado y confidencial para propietarios.</p>
</div>
