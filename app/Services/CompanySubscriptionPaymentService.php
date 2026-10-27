<?php

namespace App\Services;

use App\Domain\Billing\Models\CompanyPaymentRequest;
use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Companies\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanySubscriptionPaymentService
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    public function createRequest(Company $company, User $requester, string $billingCycle): CompanyPaymentRequest
    {
        return DB::transaction(function () use ($company, $requester, $billingCycle): CompanyPaymentRequest {
            $subscription = CompanySubscription::query()
                ->where('company_id', $company->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $subscription || in_array($subscription->status, ['suspended', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'billing_cycle' => 'No es posible solicitar una renovación para el estado actual de la suscripción. Contacta a AutoBitácora.',
                ]);
            }

            $plan = $subscription->plan;
            if (! $plan) {
                throw ValidationException::withMessages([
                    'billing_cycle' => 'La suscripción no tiene un plan asignado. Contacta a AutoBitácora.',
                ]);
            }

            $amount = match ($billingCycle) {
                'monthly' => $plan->monthly_price,
                'yearly' => $plan->yearly_price,
                default => null,
            };

            if ($amount === null || (float) $amount <= 0) {
                throw ValidationException::withMessages([
                    'billing_cycle' => 'El plan no tiene una tarifa válida para el periodo seleccionado. Contacta a AutoBitácora.',
                ]);
            }

            $existingRequest = CompanyPaymentRequest::query()
                ->where('company_id', $company->id)
                ->where('status', 'requested')
                ->lockForUpdate()
                ->first();

            if ($existingRequest) {
                $isSameRequest = $existingRequest->company_subscription_id === $subscription->id
                    && $existingRequest->subscription_plan_id === $plan->id
                    && $existingRequest->billing_cycle === $billingCycle
                    && $existingRequest->amount === number_format((float) $amount, 2, '.', '');

                if ($isSameRequest) {
                    throw ValidationException::withMessages([
                        'billing_cycle' => "Ya existe una solicitud de pago pendiente (#{$existingRequest->id}).",
                    ]);
                }

                $existingRequest->update(['status' => 'superseded']);
            }

            return CompanyPaymentRequest::create([
                'company_id' => $company->id,
                'company_subscription_id' => $subscription->id,
                'subscription_plan_id' => $plan->id,
                'requested_by' => $requester->id,
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'requested',
            ]);
        }, attempts: 3);
    }

    public function confirmRequest(CompanyPaymentRequest $companyPaymentRequest, User $confirmer): CompanyPaymentRequest
    {
        return DB::transaction(function () use ($companyPaymentRequest, $confirmer): CompanyPaymentRequest {
            $paymentRequest = CompanyPaymentRequest::query()
                ->lockForUpdate()
                ->findOrFail($companyPaymentRequest->id);

            if ($paymentRequest->status !== 'requested') {
                throw ValidationException::withMessages([
                    'payment' => 'Solo se pueden confirmar solicitudes de pago pendientes.',
                ]);
            }

            $subscription = CompanySubscription::query()
                ->where('company_id', $paymentRequest->company_id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (
                ! $subscription
                || $subscription->id !== $paymentRequest->company_subscription_id
                || $subscription->subscription_plan_id !== $paymentRequest->subscription_plan_id
                || in_array($subscription->status, ['suspended', 'cancelled'], true)
            ) {
                $paymentRequest->update(['status' => 'superseded']);

                return $paymentRequest->refresh();
            }

            $startsAt = $subscription->ends_at
                && ($subscription->ends_at->isToday() || $subscription->ends_at->isFuture())
                ? $subscription->ends_at->copy()
                : now();
            $endsAt = $paymentRequest->billing_cycle === 'yearly'
                ? $startsAt->copy()->addYear()
                : $startsAt->copy()->addMonth();

            $this->subscriptionService->updateSubscription($subscription, [
                'subscription_plan_id' => $paymentRequest->subscription_plan_id,
                'status' => 'active',
                'billing_cycle' => $paymentRequest->billing_cycle,
                'starts_at' => $startsAt->toDateString(),
                'ends_at' => $endsAt->toDateString(),
                'notes' => "Renovación confirmada mediante solicitud de pago #{$paymentRequest->id}.",
            ], $confirmer);

            CompanyPaymentRequest::query()
                ->where('company_id', $paymentRequest->company_id)
                ->where('status', 'requested')
                ->where('id', '<>', $paymentRequest->id)
                ->update(['status' => 'superseded']);

            $paymentRequest->update([
                'status' => 'paid',
                'paid_at' => now(),
                'confirmed_by' => $confirmer->id,
            ]);

            return $paymentRequest->refresh();
        }, attempts: 3);
    }
}
