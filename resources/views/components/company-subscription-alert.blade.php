@if(! request()->routeIs('subscription.status') && $globalCompanySubscription)
    @if($globalCompanySubscription->isInGracePeriod())
        <div class="alert alert-warning shadow-sm border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" role="status">
            <div>
                <strong>Periodo de gracia:</strong>
                tu suscripción venció el {{ $globalCompanySubscription->ends_at->format('d/m/Y') }}.
                Tienes hasta {{ $globalCompanySubscription->graceEndsAt()?->format('d/m/Y') }} para registrar el pago; los módulos siguen disponibles durante el periodo de gracia.
            </div>
            <a href="{{ route('subscription.status') }}" class="btn btn-sm btn-warning text-dark fw-semibold">Registrar renovación</a>
        </div>
    @elseif(! $globalCompanyCanOperate)
        <div class="alert alert-danger shadow-sm border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" role="alert">
            <div>
                <strong>&#9888; Suscripción vencida o inactiva:</strong>
                los módulos operativos están bloqueados. Solo puedes consultar tu suscripción y registrar una solicitud de pago.
            </div>
            <a href="{{ route('subscription.status') }}" class="btn btn-sm btn-danger fw-semibold">Ver suscripción y pagar</a>
        </div>
    @elseif($globalCompanySubscription->daysRemaining() !== null && $globalCompanySubscription->daysRemaining() <= 7)
        <div class="alert alert-warning shadow-sm border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" role="status">
            <div>
                <strong>Aviso de vencimiento:</strong>
                tu plan {{ $globalCompanySubscription->plan?->name }} vence el {{ $globalCompanySubscription->ends_at?->format('d/m/Y') }}.
                Después del vencimiento tendrás 3 días para pagar antes del bloqueo.
            </div>
            <a href="{{ route('subscription.status') }}" class="btn btn-sm btn-warning text-dark fw-semibold">Ver suscripción</a>
        </div>
    @endif
@elseif(! request()->routeIs('subscription.status') && auth()->user()?->company && ! $globalCompanyCanOperate)
    <div class="alert alert-danger shadow-sm border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" role="alert">
        <div><strong>&#9888; Suscripción inactiva:</strong> los módulos operativos están bloqueados. Contacta a AutoBitácora para asignar o reactivar un plan.</div>
        <a href="{{ route('subscription.status') }}" class="btn btn-sm btn-danger fw-semibold">Ver suscripción</a>
    </div>
@endif
