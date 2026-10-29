<div class="card h-100 border-secondary border-2 border-dashed bg-body-secondary shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex align-items-start gap-3 mb-4">
            <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 3.5rem; height: 3.5rem;" aria-hidden="true">
                <span class="fs-3">&#128274;</span>
            </div>
            <div>
                <span class="badge bg-secondary mb-2">Plan Propietario de Flota</span>
                @if($hidden_vehicles_count > 0)
                    <h5 class="card-title fw-bold text-secondary mb-1">Vehículos adicionales bloqueados</h5>
                    <p class="text-secondary mb-0">
                        @if($customer->hasFleetTrialActive())
                            Tienes {{ $hidden_vehicles_count }} vehículos adicionales registrados por el taller.
                        @else
                            Tu período gratuito terminó. Tienes {{ $hidden_vehicles_count }} vehículos registrados que puedes habilitar al suscribirte.
                        @endif
                        Actualiza al plan Propietario de Flota para gestionar toda tu plantilla.
                    </p>
                @else
                    <h5 class="card-title fw-bold text-secondary mb-1">Mejora tu plan</h5>
                    <p class="text-secondary mb-0">
                        Con el plan Propietario de Flota eliminas el límite de vehículos y mantienes acceso continuo a tu historial, sin depender del período gratuito.
                    </p>
                @endif
            </div>
        </div>

        @if($fleetPayment?->status === 'requested')
            <div class="alert alert-warning mb-4" role="status">
                <strong>Tu solicitud está registrada y pendiente de confirmación por AutoBitácora.</strong>
                <span class="d-block">Solicitud #{{ $fleetPayment->id }} · {{ $fleetPayment->billing_cycle === 'annual' ? 'Anual' : 'Mensual' }} · USD {{ $fleetPayment->amount }}</span>
                <span class="d-block">Enviamos un correo a ti y a AutoBitácora. Los vehículos se habilitarán cuando se confirme el pago.</span>
            </div>
        @elseif($fleetPayment?->status === 'superseded')
            <div class="alert alert-secondary mb-4" role="status">
                La solicitud anterior fue reemplazada porque cambió la cantidad o el tipo de vehículos. Registra una nueva solicitud con los datos actuales.
            </div>
        @endif

        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <form method="POST" action="{{ route('portal.customers.upgrade-to-fleet', $customer) }}" class="d-flex flex-column flex-sm-row align-items-sm-end gap-2">
                @csrf
                <div>
                    <label for="billing_cycle" class="form-label fw-semibold mb-1">Periodicidad</label>
                    <select id="billing_cycle" name="billing_cycle" class="form-select" required>
                        <option value="monthly" @selected(old('billing_cycle') === 'monthly')>Mensual</option>
                        <option value="annual" @selected(old('billing_cycle') === 'annual')>Anual (paga 11 meses y cubre 12)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">
                    Registrar solicitud de pago
                </button>
            </form>

            <a
                href="https://nfcvzla.venecode.com/jsantana/"
                class="btn btn-outline-primary flex-shrink-0"
                target="_blank"
                rel="noopener noreferrer"
            >
                Desbloquear Todos los vehículos
                <span class="d-block small">Contactar a AutoBitácora</span>
            </a>
        </div>
        <p class="small text-secondary mt-3 mb-0">
            La solicitud no cobra automáticamente ni desbloquea los vehículos. AutoBitácora debe verificar y confirmar el pago.
        </p>
    </div>
</div>
