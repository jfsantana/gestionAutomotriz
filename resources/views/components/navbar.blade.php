<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top border-bottom border-secondary">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            {{-- Botón para colapsar / minimizar el menú lateral (Desktop y Móvil) --}}
            <button class="btn btn-outline-secondary btn-sm me-2 text-light border-0" type="button" id="sidebarToggle" title="Minimizar / Expandir menú">
                <span class="fs-5">&#9776;</span>
            </button>
            <a class="navbar-brand fw-bold d-flex align-items-center mb-0" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/autobitacoraLogo.png') }}" alt="AutoBitacora Logo" height="30" class="me-2 rounded bg-white p-1">
                <span class="brand-text">AutoBitacora</span> <span class="badge bg-primary ms-2 fs-6">SaaS</span>
            </a>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="topNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
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
