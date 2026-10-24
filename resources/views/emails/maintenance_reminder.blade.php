<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recordatorio de mantenimiento</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; color: #334155; font-family: Arial, Helvetica, sans-serif; line-height: 1.6;">
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        {{ $timingMessage }} {{ $order->vehicle->plate }} &middot; {{ $order->company->name }}
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                    <tr>
                        <td style="padding: 28px 24px; background-color: #0f172a; border-radius: 16px 16px 0 0;">
                            <p style="margin: 0; color: #93c5fd; font-size: 13px; font-weight: bold;">AutoBitacora &middot; Recordatorio de mantenimiento</p>
                            <p style="margin: 8px 0 0; color: #ffffff; font-size: 22px; font-weight: bold;">{{ $order->company->name }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px 24px;">
                            <p style="margin: 0 0 16px;">Hola, <strong>{{ $order->customer->full_name }}</strong>.</p>
                            <h1 style="margin: 0 0 16px; color: #0f172a; font-size: 26px; line-height: 1.3;">Un pequeño cuidado hoy, más tranquilidad en el camino</h1>
                            <p style="margin: 0 0 20px;">Queremos ayudarte a mantener tu vehículo al día. Contacta a nuestro equipo para coordinar el próximo servicio.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px; color: #1e40af; font-size: 18px; font-weight: bold;">{{ $timingMessage }}</p>
                                        @if ($order->next_due_date)
                                            <p style="margin: 6px 0;"><strong>Fecha recomendada:</strong> {{ $order->next_due_date->format('d/m/Y') }}</p>
                                        @endif
                                        @if ($order->next_due_mileage)
                                            <p style="margin: 6px 0;"><strong>Kilometraje recomendado:</strong> {{ number_format($order->next_due_mileage, 0, ',', '.') }} km</p>
                                        @endif
                                        <p style="margin: 6px 0;"><strong>Servicio:</strong> {{ $order->service_name_snapshot }}</p>
                                        <p style="margin: 6px 0;"><strong>Vehículo:</strong> {{ $order->vehicle->make }} {{ $order->vehicle->model }}</p>
                                        <p style="margin: 6px 0;"><strong>Placa:</strong> {{ $order->vehicle->plate }}</p>
                                        <p style="margin: 6px 0 0;"><strong>Orden de referencia:</strong> {{ $order->order_number ?? '#'.$order->id }}</p>
                                    </td>
                                </tr>
                            </table>
                            @if ($order->next_due_date && $order->next_due_mileage)
                                <p style="margin: 12px 0; font-size: 14px;">Ten en cuenta la fecha o el kilometraje, lo que ocurra primero.</p>
                            @endif
                            <p style="margin: 24px 0 12px; color: #0f172a; font-weight: bold;">Estamos para ayudarte</p>
                            @if ($order->company->phone)
                                <p style="margin: 4px 0;">Teléfono del taller: <strong>{{ $order->company->phone }}</strong></p>
                            @endif
                            @if ($order->company->email)
                                <p style="margin: 4px 0;">Correo del taller: {{ $order->company->email }}</p>
                            @endif
                            @if ($order->company->address)
                                <p style="margin: 4px 0;">Dirección: {{ $order->company->address }}</p>
                            @endif
                            <p style="margin: 16px 0 20px;">Este aviso no reserva una cita. Confirma la disponibilidad directamente con el taller. Si ya realizaste el servicio, avísanos para actualizar tu historial.</p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td align="center" bgcolor="#1d4ed8" style="border-radius: 8px;">
                                        <a href="{{ $portalUrl }}" style="display: inline-block; padding: 14px 24px; border-radius: 8px; color: #ffffff; font-size: 16px; font-weight: bold; text-decoration: none;">Ver mis mantenimientos</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 18px 0 12px; color: #64748b; font-size: 13px;">Ingresa con tu correo y contraseña. Si aún no tienes acceso al portal, solicita tu invitación al taller.</p>
                            <p style="margin: 0; color: #64748b; font-size: 13px;">Si el botón no funciona, copia este enlace:<br><a href="{{ $portalUrl }}" style="color: #1d4ed8; word-break: break-all;">{{ $portalUrl }}</a></p>
                            <p style="margin: 24px 0 0;">Gracias por confiar en <strong>{{ $order->company->name }}</strong>.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 18px 24px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 12px; text-align: center;">
                            Recibes este recordatorio por el próximo mantenimiento de tu vehículo.<br>
                            AutoBitacora &middot; Tu historial, siempre a mano.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
