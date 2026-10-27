<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Solicitud de plan Propietario de Flota</title>
</head>
<body>
    <h1>Solicitud de plan Propietario de Flota</h1>

    <p>Se registró la solicitud <strong>#{{ $payment->id }}</strong>.</p>
    <ul>
        <li>Cliente: {{ $payment->customer?->full_name ?? 'Cliente no disponible' }}</li>
        <li>Correo: {{ $payment->customer?->user?->email ?? $payment->customer?->email ?? 'No disponible' }}</li>
        <li>Vehículos incluidos: {{ $payment->vehicle_count }}</li>
        <li>Periodicidad: {{ $payment->billing_cycle === 'annual' ? 'Anual' : 'Mensual' }}</li>
        <li>Monto solicitado: {{ $payment->currency }} {{ $payment->amount }}</li>
        <li>Estado: Solicitud</li>
    </ul>

    @if($isSuperAdmin)
        <p>Contacta al cliente para iniciar el proceso de pago y confirma la solicitud en el panel solo después de recibir el pago.</p>
        <p><a href="{{ route('client-subscriptions.index') }}">Revisar solicitudes de pago</a></p>
    @else
        <p>AutoBitácora revisará tu solicitud y se pondrá en contacto contigo para coordinar el pago. El plan se activará cuando el pago sea confirmado.</p>
        <p><a href="{{ route('portal.vehicles.index') }}">Volver a Mis Vehículos</a></p>
    @endif
</body>
</html>
