<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Models\CustomerPayment;
use App\Domain\Companies\Models\Customer;
use App\Mail\ClientFleetPaymentRequestedMail;
use App\Models\User;
use App\Services\ClientSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ClientSubscriptionController extends Controller
{
    public function __construct(
        protected ClientSubscriptionService $subscriptionService
    ) {}

    public function index(): View
    {
        $payments = CustomerPayment::query()
            ->with(['customer.user', 'confirmer', 'reverser'])
            ->latest('id')
            ->paginate(20);

        return view('client_subscriptions.index', compact('payments'));
    }

    public function upgradeToFleet(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()->customer_id === $customer->id, 404);

        $validated = $request->validate([
            'billing_cycle' => ['required', 'in:monthly,annual'],
        ]);

        $superAdminEmails = User::query()
            ->where('role', 'super_admin')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->pluck('email');
        $payment = $this->subscriptionService->requestFleetUpgrade(
            $customer,
            $validated['billing_cycle'],
        );
        $payment->load('customer.user');

        $emailDeliveryFailed = false;
        $customerEmail = $request->user()->email;

        if ($customerEmail) {
            try {
                Mail::to($customerEmail)->send(new ClientFleetPaymentRequestedMail($payment, false));
            } catch (TransportExceptionInterface $exception) {
                $emailDeliveryFailed = true;
                Log::error('No se pudo enviar al cliente el correo de solicitud de pago.', [
                    'customer_payment_id' => $payment->id,
                    'customer_id' => $customer->id,
                    'exception_class' => $exception::class,
                ]);
            }
        } else {
            $emailDeliveryFailed = true;
            Log::error('No se envió el correo de solicitud de pago porque el usuario cliente no tiene email.', [
                'customer_payment_id' => $payment->id,
                'customer_id' => $customer->id,
            ]);
        }

        if ($superAdminEmails->isEmpty()) {
            $emailDeliveryFailed = true;
            Log::error('No hay superadministradores activos con email para notificar la solicitud de pago.', [
                'customer_payment_id' => $payment->id,
                'customer_id' => $customer->id,
            ]);
        } else {
            foreach ($superAdminEmails as $superAdminEmail) {
                try {
                    Mail::to($superAdminEmail)->send(new ClientFleetPaymentRequestedMail($payment, true));
                } catch (TransportExceptionInterface $exception) {
                    $emailDeliveryFailed = true;
                    Log::error('No se pudo enviar a un superadministrador el correo de solicitud de pago.', [
                        'customer_payment_id' => $payment->id,
                        'customer_id' => $customer->id,
                        'exception_class' => $exception::class,
                    ]);
                }
            }
        }

        if ($emailDeliveryFailed) {
            return back()->with(
                'error',
                "La solicitud #{$payment->id} quedó registrada por USD {$payment->amount}, pero no se pudo enviar uno o más correos. El equipo debe revisar la configuración de correo."
            );
        }

        return back()->with(
            'success',
            "Solicitud de pago #{$payment->id} registrada por USD {$payment->amount}. Enviamos una notificación a tu correo y a AutoBitácora; el plan se activará cuando confirmen el pago."
        );
    }

    public function confirmPayment(CustomerPayment $customer_payment, Request $request): RedirectResponse
    {
        $payment = $this->subscriptionService->confirmFleetPayment($customer_payment, $request->user());

        if ($payment->status === 'superseded') {
            return back()->withErrors([
                'payment' => 'No se pudo confirmar la solicitud porque los vehículos, sus tipos o el precio ya no coinciden con la solicitud original.',
            ]);
        }

        return back()->with('success', "Pago #{$payment->id} confirmado y plan Propietario de Flota activado.");
    }

    public function hideRequest(CustomerPayment $customer_payment): RedirectResponse
    {
        if (! in_array($customer_payment->status, ['requested', 'superseded'], true)) {
            throw ValidationException::withMessages([
                'payment' => 'Solo se pueden ocultar solicitudes pendientes o reemplazadas. Los pagos confirmados se conservan en el reporte.',
            ]);
        }

        $customer_payment->delete();

        return back()->with('success', "La solicitud #{$customer_payment->id} se ocultó del reporte. El registro se conserva en la base de datos.");
    }

    public function reversePayment(CustomerPayment $customer_payment, Request $request): RedirectResponse
    {
        $payment = $this->subscriptionService->reverseFleetPayment($customer_payment, $request->user());

        return back()->with('success', "El pago #{$payment->id} fue revertido. Se actualizó el acceso del cliente cuando este pago correspondía a su suscripción vigente.");
    }
}
