<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top border-bottom border-secondary">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
            <span class="text-primary">&#9881;</span> gestionAutomotriz <span class="badge bg-secondary fs-6">SaaS</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="topNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('health') }}" target="_blank" title="Endpoint de monitoreo técnico">
                        <span class="badge bg-outline-info border text-info">&#9679; API Health</span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @auth
                            <strong>{{ auth()->user()->name }}</strong> 
                            <span class="badge bg-primary text-uppercase ms-1">{{ auth()->user()->role }}</span>
                        @else
                            Modo Base
                        @endauth
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        @auth
                            <li><span class="dropdown-header">Usuario: {{ auth()->user()->username }}</span></li>
                            <li><span class="dropdown-item-text text-muted small">Rol: {{ auth()->user()->role }}</span></li>
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
