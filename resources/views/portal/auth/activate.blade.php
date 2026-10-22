<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activar Cuenta - AutoBitacora</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/autobitacoraLogo.png') }}">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-activate { width: 100%; max-width: 480px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="card card-activate shadow-lg border-0 bg-white p-4">
        <div class="card-body">
            <div class="text-center mb-4">
                <img src="{{ asset('images/autobitacoraLogo.png') }}" alt="AutoBitacora" height="48" class="mb-2">
                <h4 class="fw-bold text-dark mb-1">Activar Cuenta de Cliente</h4>
                <p class="text-muted small">Crea tu contraseña segura para acceder a tus vehículos</p>
            </div>

            <div class="alert alert-info py-2 small mb-3">
                Invitación del taller: <strong>{{ $invitation->company?->name }}</strong><br>
                Cliente: <strong>{{ $invitation->customer?->full_name }}</strong>
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2 small">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('portal.activate.submit', $token) }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Correo Electrónico</label>
                    <input type="text" class="form-control bg-light" value="{{ $invitation->customer?->email }}" disabled>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small text-secondary">Nueva Contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Mínimo 8 caracteres" required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label fw-semibold small text-secondary">Confirmar Contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Repite la contraseña" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    Crear Contraseña y Entrar al Portal &rarr;
                </button>
            </form>
        </div>
    </div>
</body>
</html>
