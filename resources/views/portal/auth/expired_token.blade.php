<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enlace No Disponible - AutoBitacora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>body { background-color: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }</style>
</head>
<body>
    <div class="card shadow-lg border-0 bg-white p-4 text-center" style="max-width: 450px;">
        <div class="card-body">
            <span class="fs-1 text-warning d-block mb-3">&#9888;</span>
            <h4 class="fw-bold text-dark">Enlace no disponible</h4>
            <p class="text-secondary small mb-4">
                @if($isExpired)
                    Este enlace de invitación ha expirado porque han pasado más de 48 horas desde su emisión por seguridad.
                @else
                    Este enlace ya fue utilizado anteriormente o no es válido.
                @endif
            </p>
            <p class="small text-muted mb-4">
                Ponte en contacto con el taller mecánico emisor para que te reenvíen una nueva invitación de acceso.
            </p>
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm px-4">Ir a Iniciar Sesión</a>
        </div>
    </div>
</body>
</html>
