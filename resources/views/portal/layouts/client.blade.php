<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Mi Portal') - AutoBitacora</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/autobitacoraLogo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        body {
            font-size: .875rem;
            background-color: #f1f5f9;
        }

        .portal-navbar {
            background-color: #0f172a;
        }

        .client-layout {
            display: flex;
            min-height: calc(100vh - 56px);
        }

        .client-sidebar {
            width: 240px;
            min-width: 240px;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
        }

        .client-content {
            flex-grow: 1;
            padding: 1.5rem 2rem;
            min-width: 0;
        }

        .client-nav-link {
            padding: 0.65rem 1rem;
            font-weight: 500;
            color: #334155;
            display: flex;
            align-items: center;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .client-nav-link:hover {
            background-color: #f8fafc;
            color: #0d6efd;
        }

        .client-nav-link.active {
            background-color: #e0edff;
            color: #0d6efd;
            font-weight: 600;
        }

        .client-nav-icon {
            font-size: 1.15rem;
            margin-right: 0.65rem;
            display: inline-flex;
            width: 1.5rem;
            justify-content: center;
        }

        @media (max-width: 767.98px) {
            .client-layout {
                flex-direction: column;
            }
            .client-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }
            .client-content {
                padding: 1rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- BARRA SUPERIOR DEL PORTAL CLIENTE --}}
    <nav class="navbar navbar-expand-lg navbar-dark portal-navbar sticky-top border-bottom border-secondary">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                @php
                    $company = auth()->user()->company;
                @endphp
                @if($company && $company->logo_path)
                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" height="30" class="me-2 rounded bg-white p-1">
                    <span class="navbar-brand fw-bold fs-6 mb-0">{{ $company->name }}</span>
                    <span class="badge bg-secondary ms-2 small d-none d-sm-inline">Portal Cliente</span>
                @else
                    <img src="{{ asset('images/autobitacoraLogo.png') }}" alt="AutoBitacora" height="28" class="me-2 rounded bg-white p-1">
                    <span class="navbar-brand fw-bold fs-6 mb-0">AutoBitacora</span>
                    <span class="badge bg-primary ms-2 small">Portal Cliente</span>
                @endif
            </div>

            <div class="d-flex align-items-center ms-auto">
                <span class="text-light me-3 small d-none d-md-inline">
                    Hola, <strong>{{ auth()->user()->name }}</strong>
                </span>

                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-3">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </nav>

    {{-- ESTRUCTURA DEL PORTAL: SIDEBAR EXCLUSIVO + CONTENIDO --}}
    <div class="client-layout">
        <aside class="client-sidebar p-3">
            <small class="text-muted text-uppercase fw-bold d-block px-2 mb-2" style="font-size: 0.72rem;">Mi Cuenta</small>
            <nav class="nav flex-column">
                <a href="{{ route('portal.dashboard') }}" class="client-nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                    <span class="client-nav-icon">&#128202;</span> Inicio / Resumen
                </a>
                <a href="{{ route('portal.vehicles.index') }}" class="client-nav-link {{ request()->routeIs('portal.vehicles.*') ? 'active' : '' }}">
                    <span class="client-nav-icon">&#128663;</span> Mis Vehículos
                </a>
                <a href="{{ route('portal.maintenance.index') }}" class="client-nav-link {{ request()->routeIs('portal.maintenance.index') ? 'active' : '' }}">
                    <span class="client-nav-icon">&#128221;</span> Historial de Servicios
                </a>
                <a href="{{ route('portal.maintenance.create') }}" class="client-nav-link {{ request()->routeIs('portal.maintenance.create') ? 'active' : '' }}">
                    <span class="client-nav-icon">&#10133;</span> Registrar Servicio Propio
                </a>
                <a href="{{ route('portal.account') }}" class="client-nav-link {{ request()->routeIs('portal.account') ? 'active' : '' }}">
                    <span class="client-nav-icon">&#128100;</span> Mi Cuenta y Clave
                </a>
            </nav>

            <div class="mt-4 pt-3 border-top px-2 text-muted" style="font-size: 0.75rem;">
                <p class="mb-1 fw-semibold text-dark">AutoBitacora SaaS</p>
                <p class="mb-0">Entorno privado y confidencial para propietarios.</p>
            </div>
        </aside>

        <main class="client-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <strong>&#10003; Éxito:</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
