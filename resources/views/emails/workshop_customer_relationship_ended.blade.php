<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;color:#334155;line-height:1.6">
    <h2>Un cliente cerró la relación con tu taller</h2>
    <p>El cliente <strong>{{ $customer->full_name }}</strong> cerró su relación con <strong>{{ $company->name }}</strong>.</p>
    <p>Sus vehículos y el historial se conservaron para el cliente. Por privacidad, se eliminaron los precios de los mantenimientos asociados.</p>
</body>
</html>
