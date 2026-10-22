<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse py-3 border-end min-vh-100" id="sidebarMenu">
    <div class="position-sticky pt-2">
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mb-2 text-muted text-uppercase small fw-bold">
            <span>Navegación</span>
        </h6>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard', 'home') ? 'active fw-bold text-primary' : 'text-dark' }}" href="{{ route('dashboard') }}">
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" href="{{ route('health') }}" target="_blank">
                    Health Check
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-3 mb-2 text-muted text-uppercase small fw-bold">
            <span>Módulos</span>
        </h6>
        <ul class="nav flex-column mb-auto">
            @if(auth()->check() && auth()->user()->role === 'super_admin')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('companies.*') ? 'active fw-bold text-primary bg-white shadow-sm' : 'text-dark' }}" href="{{ route('companies.index') }}">
                        Companies
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('company-admins.*') ? 'active fw-bold text-primary bg-white shadow-sm' : 'text-dark' }}" href="{{ route('company-admins.index') }}">
                        Usuarios Empresa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('staff.*') ? 'active fw-bold text-primary bg-white shadow-sm' : 'text-dark' }}" href="{{ route('staff.index') }}">
                        Personal Operativo
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->role === 'company_admin')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('staff.*') ? 'active fw-bold text-primary bg-white shadow-sm' : 'text-dark' }}" href="{{ route('staff.index') }}">
                        Personal (Mecánicos)
                    </a>
                </li>
            @endif

            @if(auth()->check() && in_array(auth()->user()->role, ['super_admin', 'company_admin'], true))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('services.*') ? 'active fw-bold text-primary bg-white shadow-sm' : 'text-dark' }}" href="{{ route('services.index') }}">
                        Servicios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customers.*') ? 'active fw-bold text-primary bg-white shadow-sm' : 'text-dark' }}" href="{{ route('customers.index') }}">
                        Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('vehicles.*') ? 'active fw-bold text-primary bg-white shadow-sm' : 'text-dark' }}" href="{{ route('vehicles.index') }}">
                        Vehículos
                    </a>
                </li>
            @endif

            @if(auth()->check() && in_array(auth()->user()->role, ['super_admin', 'company_admin', 'mechanic'], true))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('maintenance-orders.*') ? 'active fw-bold text-primary bg-white shadow-sm' : 'text-dark' }}" href="{{ route('maintenance-orders.index') }}">
                        Mantenimientos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reminders.*') ? 'active fw-bold text-primary bg-white shadow-sm' : 'text-dark' }}" href="{{ route('reminders.index') }}">
                        Recordatorios
                    </a>
                </li>
            @endif

            <li class="nav-item">
                <a class="nav-link text-muted" href="#" tabindex="-1" aria-disabled="true">
                    Billing <span class="badge bg-secondary ms-1">Próximamente</span>
                </a>
            </li>
        </ul>
    </div>
</div>

