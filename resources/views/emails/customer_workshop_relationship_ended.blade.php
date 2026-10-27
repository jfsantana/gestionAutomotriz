<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;color:#334155;line-height:1.6">
    <h2>Relación con el taller cerrada</h2>
    <p>Hola, {{ $customer->full_name }}.</p>
    <p>{{ $company->name }} cerró la relación de servicio con tu cuenta. Tus vehículos y el historial de mantenimiento se conservaron en tu portal.</p>
    <p>Por privacidad, los precios registrados en los mantenimientos anteriores fueron eliminados. Un nuevo taller no tendrá acceso a ese historial.</p>
</body>
</html>
