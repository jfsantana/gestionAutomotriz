<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;color:#334155;line-height:1.6">
    <h2>Invitación para asociarte a un taller</h2>
    <p>Hola, {{ $invitation->customer->full_name }}.</p>
    <p><strong>{{ $invitation->company->name }}</strong> te invita a asociar tu cuenta y tus vehículos.</p>
    <p>Debes iniciar sesión y aceptar la invitación. El enlace vence en 48 horas.</p>
    <p><a href="{{ $acceptanceUrl }}">Revisar invitación</a></p>
    <p>El taller no podrá consultar mantenimientos ni precios registrados por talleres anteriores. Tu historial permanecerá privado en tu portal.</p>
</body>
</html>
