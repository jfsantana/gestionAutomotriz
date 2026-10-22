<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'gestionAutomotriz') }}</title>

    <!-- Bootstrap 5 CSS (CDN oficial) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        body {
            font-size: .875rem;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
        }
        .sidebar .nav-link {
            font-weight: 500;
            padding: .5rem 1rem;
            border-radius: .25rem;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(13, 110, 253, .08);
        }
        .main-content {
            padding-top: 1.5rem;
            padding-bottom: 2rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    @include('components.navbar')

    <div class="container-fluid">
        <div class="row">
            @include('components.sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                @include('components.flash-messages')

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle con Popper (CDN oficial) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
