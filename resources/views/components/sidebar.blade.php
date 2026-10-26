<aside class="sidebar {{ request()->routeIs('company.dashboard') && auth()->user()?->role === 'company_admin' ? 'sidebar-company' : '' }} border-end d-flex flex-column justify-content-between" id="appSidebar">
    <div class="pt-3 px-2 flex-grow-1 overflow-auto">

        {{-- SECCIÓN 1: PRINCIPAL --}}
        <div class="sidebar-section mb-3">
            <div class="sidebar-heading px-2 text-muted text-uppercase fw-bold">
                <span class="nav-text">Principal</span>
            </div>
            <ul class="nav flex-column mt-1">
                <li class="nav-item mb-1">
                    <a class="nav-link {{ request()->routeIs('dashboard', 'home', 'company.dashboard') ? 'active fw-bold' : '' }}" href="{{ route(auth()->user()?->role === 'company_admin' ? 'company.dashboard' : 'dashboard') }}" title="Panel Principal">
                        <x-section-icon name="dashboard" class="nav-icon" />
                        <span class="nav-text">Panel de Control</span>
                    </a>
                </li>
            </ul>
        </div>

        {{-- SECCIÓN 2: OPERACIONES DEL TALLER (Secuencial: Clientes -> Vehículos -> Catálogo Servicios -> Mantenimientos -> Recordatorios) --}}
        @if(auth()->check() && in_array(auth()->user()->role, ['super_admin', 'company_admin', 'mechanic'], true))
            <div class="sidebar-section mb-3">
                <div class="sidebar-heading px-2 text-muted text-uppercase fw-bold">
                    <span class="nav-text">Operaciones de Taller</span>
                </div>
                <ul class="nav flex-column mt-1">
                    {{-- 1. Clientes (solo admins) --}}
                    @if(in_array(auth()->user()->role, ['super_admin', 'company_admin'], true))
                        <li class="nav-item mb-1">
                            <a class="nav-link {{ request()->routeIs('customers.*') ? 'active fw-bold' : '' }}" href="{{ route('customers.index') }}" title="Clientes y Propietarios">
                                <x-section-icon name="customers" class="nav-icon" />
                                <span class="nav-text">Clientes</span>
                            </a>
                        </li>
                    @endif

                    {{-- 2. Vehículos --}}
                    @if(in_array(auth()->user()->role, ['super_admin', 'company_admin'], true))
                        <li class="nav-item mb-1">
                            <a class="nav-link {{ request()->routeIs('vehicles.*') ? 'active fw-bold' : '' }}" href="{{ route('vehicles.index') }}" title="Inventario de Vehículos">
                                <x-section-icon name="vehicles" class="nav-icon" />
                                <span class="nav-text">Vehículos</span>
                            </a>
                        </li>
                    @endif

                    {{-- 3. Catálogo de Servicios --}}
                    @if(in_array(auth()->user()->role, ['super_admin', 'company_admin'], true))
                        <li class="nav-item mb-1">
                            <a class="nav-link {{ request()->routeIs('services.*') ? 'active fw-bold' : '' }}" href="{{ route('services.index') }}" title="Catálogo de Servicios">
                                <x-section-icon name="services" class="nav-icon" />
                                <span class="nav-text">Servicios</span>
                            </a>
                        </li>
                    @endif

                    {{-- 4. Órdenes de Mantenimiento --}}
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('maintenance-orders.*') ? 'active fw-bold' : '' }}" href="{{ route('maintenance-orders.index') }}" title="Órdenes de Mantenimiento">
                            <x-section-icon name="maintenance-orders" class="nav-icon" />
                            <span class="nav-text">Mantenimientos</span>
                        </a>
                    </li>

                    {{-- 5. Recordatorios de Vencimiento --}}
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('reminders.*') ? 'active fw-bold' : '' }}" href="{{ route('reminders.index') }}" title="Próximos Mantenimientos y Alertas">
                            <x-section-icon name="reminders" class="nav-icon" />
                            <span class="nav-text">Recordatorios</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif

        {{-- SECCIÓN 3: GESTIÓN DE TALLER / EQUIPO (Para Administrador de Taller) --}}
        @if(auth()->check() && auth()->user()->role === 'company_admin')
            <div class="sidebar-section mb-3">
                <div class="sidebar-heading px-2 text-muted text-uppercase fw-bold">
                    <span class="nav-text">Mi Taller</span>
                </div>
                <ul class="nav flex-column mt-1">
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('company.profile.*') ? 'active fw-bold' : '' }}" href="{{ route('company.profile.edit') }}" title="Logo y Colores del Taller">
                            <x-section-icon name="company-profile" class="nav-icon" />
                            <span class="nav-text">Identidad y Logo</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('staff.*') ? 'active fw-bold' : '' }}" href="{{ route('staff.index') }}" title="Personal Técnico y Mecánicos">
                            <x-section-icon name="staff" class="nav-icon" />
                            <span class="nav-text">Personal Operativo</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('subscription.status') ? 'active fw-bold' : '' }}" href="{{ route('subscription.status') }}" title="Estado de Cuenta y Suscripción">
                            <x-section-icon name="company-subscriptions" class="nav-icon" />
                            <span class="nav-text">Mi Suscripción</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif

        {{-- SECCIÓN 4: ADMINISTRACIÓN GLOBAL SAAS (Agrupado para Superadministrador) --}}
        @if(auth()->check() && auth()->user()->role === 'super_admin')
            {{-- Grupo: Empresas y Personal --}}
            <div class="sidebar-section mb-3">
                <div class="sidebar-heading px-2 text-muted text-uppercase fw-bold">
                    <span class="nav-text">Empresas y Usuarios</span>
                </div>
                <ul class="nav flex-column mt-1">
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('companies.*') ? 'active fw-bold' : '' }}" href="{{ route('companies.index') }}" title="Talleres y Concesionarios">
                            <x-section-icon name="companies" class="nav-icon" />
                            <span class="nav-text">Empresas y Talleres</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('company-admins.*') ? 'active fw-bold' : '' }}" href="{{ route('company-admins.index') }}" title="Administradores de Empresas">
                            <x-section-icon name="company-admins" class="nav-icon" />
                            <span class="nav-text">Usuarios de Empresa</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('staff.*') ? 'active fw-bold' : '' }}" href="{{ route('staff.index') }}" title="Mecánicos de todas las empresas">
                            <x-section-icon name="staff" class="nav-icon" />
                            <span class="nav-text">Personal Operativo</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Grupo: Comercial SaaS (Planes y Suscripciones) --}}
            <div class="sidebar-section mb-3">
                <div class="sidebar-heading px-2 text-muted text-uppercase fw-bold">
                    <span class="nav-text">Suscripciones SaaS</span>
                </div>
                <ul class="nav flex-column mt-1">
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('subscription-plans.*') ? 'active fw-bold' : '' }}" href="{{ route('subscription-plans.index') }}" title="Catálogo de Planes SaaS">
                            <x-section-icon name="subscription-plans" class="nav-icon" />
                            <span class="nav-text">Planes de Suscripción</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link {{ request()->routeIs('company-subscriptions.*') ? 'active fw-bold' : '' }}" href="{{ route('company-subscriptions.index') }}" title="Control de Vigencia y Bloqueo">
                            <x-section-icon name="company-subscriptions" class="nav-icon" />
                            <span class="nav-text">Suscripciones Activas</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif

    </div>

    {{-- BORDE INFERIOR: ENLACE FIJO A LAS POLÍTICAS Y CONDICIONES ACEPTADAS --}}
    @if(isset($currentPlatformPolicy) && $currentPlatformPolicy)
        <div class="sidebar-footer border-top p-2 text-center">
            <button 
                type="button" 
                class="btn btn-link p-0 text-decoration-none text-muted small d-flex align-items-center justify-content-center w-100" 
                data-bs-toggle="modal" 
                data-bs-target="#viewAcceptedPolicyModal"
                title="Ver Políticas de Uso y Condiciones aceptadas"
            >
                <x-section-icon name="policies" class="nav-icon fs-6 me-1" />
                <span class="nav-text" style="font-size: 0.78rem;">Políticas y Condiciones</span>
            </button>
            @if(isset($userPolicyAcceptance) && $userPolicyAcceptance)
                <div class="nav-text text-muted" style="font-size: 0.68rem; margin-top: 2px;">
                    v{{ $currentPlatformPolicy->version }} &bull; Aceptado el {{ $userPolicyAcceptance->accepted_at?->format('d/m/Y') }}
                </div>
            @endif
        </div>
    @endif
</aside>
