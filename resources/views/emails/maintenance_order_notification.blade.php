<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $previousStatus === null ? 'Mantenimiento registrado' : 'Novedades de tu mantenimiento' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; color: #334155; font-family: Arial, Helvetica, sans-serif; line-height: 1.6;">
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        Tu mantenimiento en {{ $order->company->name }}: {{ $statusLabel }}. Consulta los detalles en AutoBitacora.
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9;">
        <tr>
            <td align="center" style="padding: 24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                    <tr>
                        <td style="padding: 28px 24px; background-color: #0f172a; border-radius: 16px 16px 0 0;">
                            <p style="margin: 0; color: #93c5fd; font-size: 13px; font-weight: bold;">AutoBitacora</p>
                            <p style="margin: 6px 0 0; color: #ffffff; font-size: 20px; font-weight: bold;">{{ $order->company->name }}</p>
                            <p style="margin: 4px 0 0; color: #cbd5e1; font-size: 14px;">Te mantenemos al tanto de tu vehículo</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px 24px;">
                            <p style="margin: 0 0 16px;">Hola, <strong>{{ $order->customer->full_name }}</strong>.</p>
                            <h1 style="margin: 0 0 12px; color: #0f172a; font-size: 24px; line-height: 1.3;">
                                {{ $previousStatus === null ? 'Tu mantenimiento ya está registrado' : 'Hay novedades de tu mantenimiento' }}
                            </h1>
                            <p style="margin: 0 0 20px;">{{ $statusMessage }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 10px; color: #1e40af; font-size: 18px;"><strong>Estado actual: {{ $statusLabel }}</strong></p>
                                        @if ($previousStatusLabel !== null)
                                            <p style="margin: 0 0 12px; color: #64748b; font-size: 14px;">Estado anterior: {{ $previousStatusLabel }}</p>
                                        @endif
                                        <p style="margin: 6px 0;"><strong>Vehículo:</strong> {{ $order->vehicle->make }} {{ $order->vehicle->model }}</p>
                                        <p style="margin: 6px 0;"><strong>Placa:</strong> {{ $order->vehicle->plate }}</p>
                                        <p style="margin: 6px 0;"><strong>Servicio:</strong> {{ $order->service_name_snapshot }}</p>
                                        <p style="margin: 6px 0;"><strong>Orden:</strong> {{ $order->order_number }}</p>
                                        <p style="margin: 6px 0 0;"><strong>Fecha del servicio:</strong> {{ $order->service_date->format('d/m/Y') }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 22px 0 16px;">Entra a la aplicación para revisar tus mantenimientos y consultar el historial de tu vehículo, cuando lo necesites.</p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td align="center" bgcolor="#1d4ed8" style="border-radius: 8px;">
                                        <a href="{{ $portalUrl }}" style="display: inline-block; padding: 14px 24px; border: 1px solid #1d4ed8; border-radius: 8px; color: #ffffff; font-size: 16px; font-weight: bold; text-decoration: none;">Ver mis mantenimientos</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 18px 0; color: #64748b; font-size: 13px;">Ingresa con tu correo y contraseña. Si aún no has activado tu acceso, utiliza la invitación recibida o solicítala al taller/concesionario.</p>
                            <p style="margin: 0; color: #64748b; font-size: 13px;">Si el botón no funciona, copia este enlace en tu navegador:<br><a href="{{ $portalUrl }}" style="color: #1d4ed8; word-break: break-all;">{{ $portalUrl }}</a></p>

                            <p style="margin: 24px 0 0;">Gracias por confiar en <strong>{{ $order->company->name }}</strong>.</p>
                            @if ($order->company->phone)
                                <p style="margin: 4px 0 0; font-size: 14px;">Para consultas sobre tu mantenimiento: {{ $order->company->phone }}</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 18px 24px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 12px; text-align: center;">
                            Recibes este aviso porque eres el propietario asociado a este mantenimiento.<br>
                            AutoBitacora &bull; Tu historial, siempre a mano.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
