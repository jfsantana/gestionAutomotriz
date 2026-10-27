<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Models\CompanyPaymentRequest;
use App\Mail\CompanySubscriptionPaymentRequestedMail;
use App\Models\User;
use App\Services\CompanySubscriptionPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class CompanySubscriptionPaymentController extends Controller
{
    public function __construct(
        private CompanySubscriptionPaymentService $paymentService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;

        abort_unless($company, 404);

        $validated = $request->validate([
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $paymentRequest = $this->paymentService->createRequest(
            $company,
            $user,
            $validated['billing_cycle'],
        );
        $paymentRequest->load(['company', 'plan', 'requester']);

        $companyAdminEmails = $company->admins()
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->pluck('email');
        $superAdminEmails = User::query()
            ->where('role', 'super_admin')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->pluck('email');
        $emailDeliveryFailed = false;

        foreach ([
            [$companyAdminEmails, false],
            [$superAdminEmails, true],
        ] as [$recipients, $isSuperAdmin]) {
            if ($recipients->isEmpty()) {
                $emailDeliveryFailed = true;
                Log::error('No hay destinatarios con correo para notificar la solicitud de renovación del taller.', [
                    'company_payment_request_id' => $paymentRequest->id,
                    'company_id' => $company->id,
                    'recipient_type' => $isSuperAdmin ? 'super_admin' : 'company_admin',
                ]);

                continue;
            }

            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient)->send(new CompanySubscriptionPaymentRequestedMail($paymentRequest, $isSuperAdmin));
                } catch (TransportExceptionInterface $exception) {
                    $emailDeliveryFailed = true;
                    Log::error('No se pudo enviar el correo de solicitud de renovación del taller.', [
                        'company_payment_request_id' => $paymentRequest->id,
                        'company_id' => $company->id,
                        'exception_class' => $exception::class,
                    ]);
                }
            }
        }

        if ($emailDeliveryFailed) {
            return back()->with(
                'error',
                "La solicitud #{$paymentRequest->id} quedó registrada por USD {$paymentRequest->amount}, pero no se pudo enviar uno o más correos. AutoBitácora debe revisar la configuración de correo."
            );
        }

        return back()->with(
            'success',
            "Solicitud #{$paymentRequest->id} registrada por USD {$paymentRequest->amount}. Se notificó a tu empresa y a AutoBitácora."
        );
    }

    public function confirm(CompanyPaymentRequest $company_payment_request, Request $request): RedirectResponse
    {
        $paymentRequest = $this->paymentService->confirmRequest($company_payment_request, $request->user());

        if ($paymentRequest->status === 'superseded') {
            return back()->withErrors([
                'payment' => 'La solicitud se reemplazó porque la suscripción o el plan de la empresa cambió. Registra una nueva solicitud.',
            ]);
        }

        return back()->with('success', "Pago #{$paymentRequest->id} confirmado y suscripción del taller renovada.");
    }

    public function hide(CompanyPaymentRequest $company_payment_request): RedirectResponse
    {
        if (! in_array($company_payment_request->status, ['requested', 'superseded'], true)) {
            throw ValidationException::withMessages([
                'payment' => 'Solo se pueden ocultar solicitudes pendientes o reemplazadas.',
            ]);
        }

        $company_payment_request->delete();

        return back()->with('success', "La solicitud #{$company_payment_request->id} se ocultó del reporte y se conservó en la base de datos.");
    }
}
