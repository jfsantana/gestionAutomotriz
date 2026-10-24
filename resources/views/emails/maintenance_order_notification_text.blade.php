AutoBitacora | {{ $order->company->name }}

Hola, {{ $order->customer->full_name }}.

{{ $previousStatus === null ? 'Tu mantenimiento ya está registrado.' : 'Hay novedades de tu mantenimiento.' }}
{{ $statusMessage }}

Estado actual: {{ $statusLabel }}
@if ($previousStatusLabel !== null)
Estado anterior: {{ $previousStatusLabel }}
@endif
Vehículo: {{ $order->vehicle->make }} {{ $order->vehicle->model }}
Placa: {{ $order->vehicle->plate }}
Servicio: {{ $order->service_name_snapshot }}
Orden: {{ $order->order_number }}
Fecha del servicio: {{ $order->service_date->format('d/m/Y') }}

Entra a la aplicación para revisar tus mantenimientos y consultar el historial de tu vehículo.
Ver mis mantenimientos: {{ $portalUrl }}

Ingresa con tu correo y contraseña. Si aún no has activado tu acceso, utiliza la invitación recibida o solicítala al taller/concesionario.

Gracias por confiar en {{ $order->company->name }}.
@if ($order->company->phone)
Para consultas sobre tu mantenimiento: {{ $order->company->phone }}
@endif

Recibes este aviso porque eres el propietario asociado a este mantenimiento.
AutoBitacora - Tu historial, siempre a mano.
