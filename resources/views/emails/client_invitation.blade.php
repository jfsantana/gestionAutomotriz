<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #334155; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 32px; }
        .header { text-align: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 24px; }
        .btn { display: inline-block; background-color: #0d6efd; color: #ffffff !important; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
        .footer { font-size: 12px; color: #94a3b8; text-align: center; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h2 style="color: #0f172a; margin: 0;">AutoBitacora SaaS</h2>
            <p style="color: #64748b; margin: 5px 0 0;">Portal Privado de Gestión y Mantenimiento Vehicular</p>
        </div>

        <p>Hola, <strong>{{ $customer->full_name }}</strong>,</p>

        <p>El taller/concesionario <strong>{{ $company->name }}</strong> ha habilitado tu acceso exclusivo al <strong>Portal de Clientes de AutoBitacora</strong>.</p>

        <p>Desde tu portal privado podrás:</p>
        <ul>
            <li>Consultar la ficha y datos de tus vehículos vinculados.</li>
            <li>Revisar el historial de mantenimientos realizados por el taller.</li>
            <li>Registrar mantenimientos y gastos personales de tus vehículos.</li>
            <li>Recibir alertas automáticas de próximos vencimientos y servicios.</li>
        </ul>

        <div style="text-align: center;">
            <a href="{{ $activationUrl }}" class="btn">Activar Mi Cuenta y Crear Contraseña</a>
        </div>

        <p style="font-size: 13px; color: #64748b;">
            <em>Este enlace es único y expirará en 48 horas por motivos de seguridad. Si el botón no funciona, copia y pega esta dirección en tu navegador:</em><br>
            <a href="{{ $activationUrl }}" style="word-break: break-all; color: #0d6efd;">{{ $activationUrl }}</a>
        </p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} AutoBitacora &bull; Plataforma Segura Multiempresa.</p>
        </div>
    </div>
</body>
</html>
