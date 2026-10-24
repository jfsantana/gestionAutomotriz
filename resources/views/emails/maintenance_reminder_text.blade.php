{{ $order->company->name }} | Recordatorio de mantenimiento

Hola, {{ $order->customer->full_name }}.

{{ $timingMessage }}
Contacta a nuestro equipo para coordinar el próximo servicio.

Servicio: {{ $order->service_name_snapshot }}
Vehículo: {{ $order->vehicle->make }} {{ $order->vehicle->model }}
Placa: {{ $order->vehicle->plate }}
Orden de referencia: {{ $order->order_number ?? '#'.$order->id }}
@if ($order->next_due_date)
Fecha recomendada: {{ $order->next_due_date->format('d/m/Y') }}
@endif
@if ($order->next_due_mileage)
Kilometraje recomendado: {{ number_format($order->next_due_mileage, 0, ',', '.') }} km
@endif
@if ($order->next_due_date && $order->next_due_mileage)
Ten en cuenta la fecha o el kilometraje, lo que ocurra primero.
@endif

@if ($order->company->phone)
Teléfono del taller: {{ $order->company->phone }}
@endif
@if ($order->company->email)
Correo del taller: {{ $order->company->email }}
@endif
@if ($order->company->address)
Dirección: {{ $order->company->address }}
@endif

Este aviso no reserva una cita. Confirma la disponibilidad directamente con el taller.
Si ya realizaste el servicio, avísanos para actualizar tu historial.

Ver mis mantenimientos: {{ $portalUrl }}
Ingresa con tu correo y contraseña. Si aún no tienes acceso al portal, solicita tu invitación al taller.

Gracias por confiar en {{ $order->company->name }}.
AutoBitacora | Tu historial, siempre a mano.
