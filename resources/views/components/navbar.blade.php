@php
    $currentCompany = auth()->check() ? auth()->user()->company : null;
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top border-bottom border-secondary">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            {{-- Botón para colapsar / minimizar el menú lateral (Desktop y Móvil) --}}
            <button class="btn btn-outline-secondary btn-sm me-2 text-light border-0" type="button" id="sidebarToggle" title="Minimizar / Expandir menú">
                <span class="fs-5">&#9776;</span>
            </button>

            {{-- LOGO DE LA EMPRESA (LADO IZQUIERDO) O MARCA PRINCIPAL --}}
            @if($currentCompany && $currentCompany->logo_path)
                <a class="navbar-brand fw-bold d-flex align-items-center mb-0" href="{{ route('dashboard') }}" title="{{ $currentCompany->name }}">
                    <img src="{{ asset('storage/' . $currentCompany->logo_path) }}" alt="{{ $currentCompany->name }}" height="32" class="me-2 rounded bg-white p-1 shadow-sm">
                    <span class="brand-text text-truncate" style="max-width: 220px;">{{ $currentCompany->name }}</span>
                    {{-- En móvil: mostrar "By AutoBitacora" --}}
                    <span class="d-lg-none badge bg-secondary ms-2 text-wrap" style="font-size: 0.65rem;">By AutoBitacora</span>
                </a>
            @else
                <a class="navbar-brand fw-bold d-flex align-items-center mb-0" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/autobitacoraLogo.png') }}" alt="AutoBitacora Logo" height="30" class="me-2 rounded bg-white p-1">
                    <span class="brand-text">AutoBitacora</span> <span class="badge bg-primary ms-2 fs-6">SaaS</span>
                </a>
            @endif
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="topNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                {{-- LOGO DE AUTOBITACORA DEL LADO DERECHO (EN DESKTOP/LAPTOP SI LA EMPRESA TIENE LOGO PROPIO) --}}
                @if($currentCompany && $currentCompany->logo_path)
                    <li class="nav-item d-none d-lg-flex align-items-center me-3 border-end pe-3 border-secondary">
                        <span class="text-secondary small me-2">Impulsado por</span>
                        <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                            <img src="{{ asset('images/autobitacoraLogo.png') }}" alt="AutoBitacora" height="26" class="rounded bg-white p-1 me-1">
                            <strong class="text-light small">AutoBitacora</strong>
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('health') }}" target="_blank" title="Diagnóstico técnico del sistema">
                        <span class="badge bg-outline-info border text-info">&#9679; Diagnóstico (/health)</span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @auth
                            <strong>{{ auth()->user()->name }}</strong> 
                            <span class="badge bg-primary text-uppercase ms-1">
                                {{ auth()->user()->role === 'super_admin' ? 'Superadmin' : (auth()->user()->role === 'company_admin' ? 'Admin Taller' : 'Mecánico') }}
                            </span>
                        @else
                            Sesión
                        @endauth
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        @auth
                            <li><span class="dropdown-header">Usuario: {{ auth()->user()->username }}</span></li>
                            <li>
                                <span class="dropdown-item-text text-muted small">
                                    Rol: {{ auth()->user()->role === 'super_admin' ? 'Superadministrador' : (auth()->user()->role === 'company_admin' ? 'Administrador de Taller' : 'Mecánico Operativo') }}
                                </span>
                            </li>
                            @if(auth()->user()->company)
                                <li><span class="dropdown-item-text text-muted small">Taller: {{ auth()->user()->company->name }}</span></li>
                                @if(auth()->user()->role === 'company_admin')
                                    <li><a class="dropdown-item text-primary" href="{{ route('company.profile.edit') }}">Identidad y Logo</a></li>
                                @endif
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        Cerrar Sesión
                                    </button>
                                </form>
                            </li>
                        @else
                            <li><a class="dropdown-item" href="{{ route('login') }}">Iniciar Sesión</a></li>
                        @endauth
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
