<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Solicitud de renovación de suscripción</title>
</head>
<body>
    <h1>Solicitud de renovación de suscripción</h1>

    <p>Se registró la solicitud <strong>#{{ $paymentRequest->id }}</strong>.</p>
    <ul>
        <li>Empresa: {{ $paymentRequest->company?->name ?? 'Empresa no disponible' }}</li>
        <li>Solicitó: {{ $paymentRequest->requester?->name ?? 'Administrador de la empresa' }}</li>
        <li>Plan: {{ $paymentRequest->plan?->name ?? 'Plan no disponible' }}</li>
        <li>Periodicidad: {{ $paymentRequest->billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}</li>
        <li>Monto solicitado: {{ $paymentRequest->currency }} {{ $paymentRequest->amount }}</li>
        <li>Estado: Solicitud</li>
    </ul>

    @if($isSuperAdmin)
        <p>Contacta a la empresa para coordinar el pago y confirma la solicitud en el panel solo después de recibirlo.</p>
        <p><a href="{{ route('client-subscriptions.index') }}">Revisar solicitudes de pago</a></p>
    @else
        <p>AutoBitácora revisará la solicitud y se pondrá en contacto con tu empresa para coordinar el pago. La renovación se aplicará cuando el pago sea confirmado.</p>
        <p><a href="{{ route('subscription.status') }}">Volver al estado de suscripción</a></p>
    @endif
</body>
</html>
