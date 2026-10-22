<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'AutoBitacora') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/autobitacoraLogo.png') }}">

    <!-- Bootstrap 5 CSS (CDN oficial) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        body {
            font-size: .875rem;
            background-color: #f8f9fa;
        }

        /* Layout con Sidebar Flexible / Minimizable */
        #appLayout {
            display: flex;
            min-height: calc(100vh - 56px);
            width: 100%;
            transition: all 0.25s ease-in-out;
        }

        #appSidebar {
            width: 250px;
            min-width: 250px;
            flex-shrink: 0;
            background-color: #ffffff;
            transition: width 0.25s ease-in-out, min-width 0.25s ease-in-out, transform 0.25s ease-in-out;
            z-index: 1020;
        }

        #appContent {
            flex-grow: 1;
            min-width: 0;
            padding-top: 1.5rem;
            padding-bottom: 2.5rem;
            transition: all 0.25s ease-in-out;
        }

        /* Estilos de elementos en Sidebar */
        .sidebar-heading {
            font-size: 0.72rem;
            letter-spacing: 0.05em;
            color: #6c757d;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar .nav-link {
            font-weight: 500;
            padding: 0.55rem 0.75rem;
            border-radius: 0.375rem;
            color: #334155;
            display: flex;
            align-items: center;
            white-space: nowrap;
            transition: all 0.15s ease;
        }

        .sidebar .nav-link .nav-icon {
            font-size: 1.15rem;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 1.75rem;
            margin-right: 0.5rem;
            flex-shrink: 0;
        }

        .sidebar .nav-link:hover {
            background-color: #f1f5f9;
            color: #0d6efd;
        }

        .sidebar .nav-link.active {
            background-color: #e0edff;
            color: #0d6efd;
            font-weight: 600;
        }

        /* PALETAS DE COLORES INSTITUCIONALES DINÁMICAS */
        /* 1. Ford Blue (Azul Ford) */
        body.palette-ford_blue .sidebar .nav-link.active {
            background-color: #e0edff;
            color: #002c6c;
            border-left: 4px solid #002c6c;
        }
        body.palette-ford_blue .sidebar .nav-link:hover {
            color: #002c6c;
        }
        body.palette-ford_blue .btn-primary {
            background-color: #002c6c;
            border-color: #002c6c;
        }
        body.palette-ford_blue .btn-primary:hover {
            background-color: #001f4d;
            border-color: #001f4d;
        }

        /* 2. Honda Red (Rojo Honda) */
        body.palette-honda_red .sidebar .nav-link.active {
            background-color: #fee2e2;
            color: #cc0000;
            border-left: 4px solid #cc0000;
        }
        body.palette-honda_red .sidebar .nav-link:hover {
            color: #cc0000;
        }
        body.palette-honda_red .btn-primary {
            background-color: #cc0000;
            border-color: #cc0000;
        }
        body.palette-honda_red .btn-primary:hover {
            background-color: #990000;
            border-color: #990000;
        }

        /* 3. Taller Clásico (Grafito / Rojo / Blanco) */
        body.palette-workshop_classic .sidebar .nav-link.active {
            background-color: #f1f5f9;
            color: #d90429;
            border-left: 4px solid #d90429;
        }
        body.palette-workshop_classic .sidebar .nav-link:hover {
            color: #d90429;
        }
        body.palette-workshop_classic .btn-primary {
            background-color: #2b2d42;
            border-color: #2b2d42;
        }
        body.palette-workshop_classic .btn-primary:hover {
            background-color: #1a1b29;
            border-color: #1a1b29;
        }

        /* ESTADO MINIMIZADO (Desktop) */
        body.sidebar-minimized #appSidebar {
            width: 68px;
            min-width: 68px;
        }

        body.sidebar-minimized #appSidebar .nav-text,
        body.sidebar-minimized #appSidebar .sidebar-heading {
            display: none !important;
        }

        body.sidebar-minimized #appSidebar .nav-link {
            justify-content: center;
            padding: 0.6rem 0;
        }

        body.sidebar-minimized #appSidebar .nav-link .nav-icon {
            margin-right: 0;
            font-size: 1.3rem;
        }

        /* RESPONSIVE MÓVIL */
        @media (max-width: 767.98px) {
            #appSidebar {
                position: fixed;
                top: 56px;
                bottom: 0;
                left: 0;
                transform: translateX(-100%);
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            }

            body.sidebar-mobile-open #appSidebar {
                transform: translateX(0);
            }

            /* Overlay oscuro al abrir en móvil */
            .sidebar-backdrop {
                display: none;
                position: fixed;
                top: 56px;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.4);
                z-index: 1010;
            }

            body.sidebar-mobile-open .sidebar-backdrop {
                display: block;
            }
        }
    </style>
    @stack('styles')
</head>
@php
    $themeClass = 'palette-' . (auth()->check() && auth()->user()->company ? (auth()->user()->company->theme_palette ?? 'ford_blue') : 'ford_blue');
@endphp
<body class="{{ $themeClass }}">
    @include('components.navbar')

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div id="appLayout">
        @include('components.sidebar')

        <main id="appContent" class="px-md-4 px-3">
            @include('components.flash-messages')

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap 5 Bundle con Popper (CDN oficial) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    {{-- Script para Toggle y Persistencia de Menú Minimizado --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');

            // Cargar preferencia guardada en localStorage
            if (window.innerWidth >= 768) {
                const isMinimized = localStorage.getItem('autobitacora_sidebar_minimized') === 'true';
                if (isMinimized) {
                    document.body.classList.add('sidebar-minimized');
                }
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    if (window.innerWidth >= 768) {
                        // Desktop: minimizar / expandir
                        document.body.classList.toggle('sidebar-minimized');
                        const state = document.body.classList.contains('sidebar-minimized');
                        localStorage.setItem('autobitacora_sidebar_minimized', state);
                    } else {
                        // Móvil: abrir / cerrar drawer
                        document.body.classList.toggle('sidebar-mobile-open');
                    }
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    document.body.classList.remove('sidebar-mobile-open');
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
