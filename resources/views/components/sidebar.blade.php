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
            <li class="nav-item">
                <a class="nav-link text-muted" href="#" tabindex="-1" aria-disabled="true">
                    Companies <span class="badge bg-secondary ms-1">Próximamente</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-muted" href="#" tabindex="-1" aria-disabled="true">
                    Vehicles <span class="badge bg-secondary ms-1">Próximamente</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-muted" href="#" tabindex="-1" aria-disabled="true">
                    Maintenance <span class="badge bg-secondary ms-1">Próximamente</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-muted" href="#" tabindex="-1" aria-disabled="true">
                    Billing <span class="badge bg-secondary ms-1">Próximamente</span>
                </a>
            </li>
        </ul>
    </div>
</div>

