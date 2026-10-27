<?php

namespace App\Services;

use App\Domain\Billing\Models\CustomerPayment;
use App\Domain\Billing\Models\CustomerSubscriptionRate;
use App\Domain\Companies\Models\Customer;
use App\Domain\Vehicles\Enums\VehicleType;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientSubscriptionService
{
    public function requestFleetUpgrade(Customer $customer, string $billingCycle): CustomerPayment
    {
        return DB::transaction(function () use ($customer, $billingCycle): CustomerPayment {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);

            if ($customer->isFleetPremiumActive()) {
                throw ValidationException::withMessages([
                    'billing_cycle' => 'El cliente ya tiene una suscripción Propietario de Flota activa.',
                ]);
            }

            $vehicles = $customer->vehicles()->lockForUpdate()->get(['id', 'vehicle_type']);
            $vehicleLimit = $customer->vehicle_limit ?? 3;

            $trialExpired = $customer->fleet_trial_started_at !== null
                && ! $customer->hasFleetTrialActive();

            if ($vehicles->isEmpty() || ($vehicles->count() <= $vehicleLimit && ! $trialExpired)) {
                throw ValidationException::withMessages([
                    'vehicles' => $trialExpired
                        ? 'El cliente necesita al menos un vehículo activo para solicitar el plan.'
                        : "El cliente debe superar el límite actual de {$vehicleLimit} vehículos para solicitar el plan.",
                ]);
            }

            $pricing = $this->calculatePricing($customer, $vehicles, $billingCycle);
            $existingRequest = $customer->payments()
                ->where('status', 'requested')
                ->lockForUpdate()
                ->first();

            if ($existingRequest) {
                if (
                    $existingRequest->vehicle_count === $vehicles->count()
                    && $existingRequest->billing_cycle === $billingCycle
                    && $existingRequest->amount === $pricing['amount']
                    && $this->normalizePricingBreakdown($existingRequest->pricing_breakdown) === $this->normalizePricingBreakdown($pricing['breakdown'])
                ) {
                    throw ValidationException::withMessages([
                        'billing_cycle' => 'El cliente ya tiene una solicitud de pago para estos vehículos y periodo.',
                    ]);
                }

                $existingRequest->update(['status' => 'superseded']);
            }

            return $customer->payments()->create([
                'customer_type' => $customer->customer_type,
                'vehicle_count' => $vehicles->count(),
                'billing_cycle' => $billingCycle,
                'amount' => $pricing['amount'],
                'currency' => 'USD',
                'status' => 'requested',
                'pricing_breakdown' => $pricing['breakdown'],
            ]);
        }, attempts: 3);
    }

    public function confirmFleetPayment(CustomerPayment $customerPayment, User $confirmer): CustomerPayment
    {
        return DB::transaction(function () use ($customerPayment, $confirmer): CustomerPayment {
            $payment = CustomerPayment::query()->lockForUpdate()->findOrFail($customerPayment->id);

            if (! in_array($payment->status, ['requested', 'superseded'], true)) {
                throw ValidationException::withMessages([
                    'payment' => 'Solo se pueden confirmar solicitudes activas o revisar solicitudes reemplazadas.',
                ]);
            }

            $customer = Customer::query()->lockForUpdate()->findOrFail($payment->customer_id);
            $vehicles = $customer->vehicles()->lockForUpdate()->get(['id', 'vehicle_type']);
            try {
                $pricing = $this->calculatePricing($customer, $vehicles, $payment->billing_cycle);
            } catch (ValidationException) {
                $payment->update(['status' => 'superseded']);

                return $payment->refresh();
            }

            if (
                $payment->vehicle_count !== $vehicles->count()
                || $payment->amount !== $pricing['amount']
                || $this->normalizePricingBreakdown($payment->pricing_breakdown) !== $this->normalizePricingBreakdown($pricing['breakdown'])
            ) {
                $payment->update(['status' => 'superseded']);

                return $payment->refresh();
            }

            $expiresAt = $payment->billing_cycle === 'annual'
                ? now()->addYear()
                : now()->addMonth();

            $customer->update([
                'is_fleet_premium' => true,
                'vehicle_limit' => null,
                'fleet_expires_at' => $expiresAt,
            ]);

            $customer->payments()
                ->where('status', 'requested')
                ->where('id', '<>', $payment->id)
                ->update(['status' => 'superseded']);

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'confirmed_by' => $confirmer->id,
            ]);

            return $payment->refresh();
        }, attempts: 3);
    }

    public function reverseFleetPayment(CustomerPayment $customerPayment, User $reverser): CustomerPayment
    {
        return DB::transaction(function () use ($customerPayment, $reverser): CustomerPayment {
            $payment = CustomerPayment::query()->lockForUpdate()->findOrFail($customerPayment->id);

            if ($payment->status !== 'paid') {
                throw ValidationException::withMessages([
                    'payment' => 'Solo se pueden revertir pagos confirmados que no hayan sido revertidos previamente.',
                ]);
            }

            $customer = Customer::query()->lockForUpdate()->findOrFail($payment->customer_id);
            $paymentExpiry = $payment->paid_at?->copy()->add(
                $payment->billing_cycle === 'annual' ? '1 year' : '1 month'
            );
            $paymentControlsCurrentSubscription = $paymentExpiry
                && $customer->is_fleet_premium
                && $customer->fleet_expires_at?->format('Y-m-d H:i:s') === $paymentExpiry->format('Y-m-d H:i:s');

            if ($paymentControlsCurrentSubscription) {
                $customer->update([
                    'is_fleet_premium' => false,
                    'vehicle_limit' => 3,
                    'fleet_expires_at' => null,
                ]);
            }

            $payment->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => $reverser->id,
            ]);

            return $payment->refresh();
        }, attempts: 3);
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @return array{amount: string, breakdown: array<string, mixed>}
     */
    private function calculatePricing(Customer $customer, Collection $vehicles, string $billingCycle): array
    {
        $rates = CustomerSubscriptionRate::query()
            ->where('customer_type', $customer->customer_type)
            ->pluck('monthly_price', 'vehicle_type')
            ->all();

        if ($rates === []) {
            throw ValidationException::withMessages([
                'vehicles' => 'No existe una tarifa configurada para este tipo de cliente.',
            ]);
        }

        $vehicleCounts = [];

        foreach ($vehicles as $vehicle) {
            $vehicleType = $vehicle->vehicle_type;

            if (! $vehicleType || ! VehicleType::tryFrom($vehicleType)) {
                throw ValidationException::withMessages([
                    'vehicles' => "El vehículo #{$vehicle->id} no tiene un tipo válido. Solicita al taller que actualice sus datos.",
                ]);
            }

            if (! array_key_exists($vehicleType, $rates)) {
                throw ValidationException::withMessages([
                    'vehicles' => "No existe una tarifa configurada para el tipo de vehículo {$vehicleType}.",
                ]);
            }

            $vehicleCounts[$vehicleType] = ($vehicleCounts[$vehicleType] ?? 0) + 1;
        }

        ksort($vehicleCounts);
        $monthlyTotalCents = 0;
        $vehicleBreakdown = [];

        foreach ($vehicleCounts as $vehicleType => $count) {
            $unitPriceCents = (int) round(((float) $rates[$vehicleType]) * 100);
            $subtotalCents = $unitPriceCents * $count;
            $monthlyTotalCents += $subtotalCents;
            $vehicleBreakdown[$vehicleType] = [
                'count' => $count,
                'monthly_unit_price' => $this->formatAmount($unitPriceCents),
                'monthly_subtotal' => $this->formatAmount($subtotalCents),
            ];
        }

        $billableMonths = $billingCycle === 'annual'
            ? (int) config('customer_subscriptions.annual_billing_months')
            : 1;
        $totalCents = $monthlyTotalCents * $billableMonths;

        return [
            'amount' => $this->formatAmount($totalCents),
            'breakdown' => [
                'customer_type' => $customer->customer_type,
                'billing_cycle' => $billingCycle,
                'billing_months_charged' => $billableMonths,
                'monthly_total' => $this->formatAmount($monthlyTotalCents),
                'vehicles' => $vehicleBreakdown,
            ],
        ];
    }

    private function formatAmount(int $amountInCents): string
    {
        return number_format($amountInCents / 100, 2, '.', '');
    }

    /**
     * MySQL normalizes key order in JSON objects, so compare snapshots independent of key order.
     *
     * @param  array<string, mixed>  $breakdown
     * @return array<string, mixed>
     */
    private function normalizePricingBreakdown(array $breakdown): array
    {
        foreach ($breakdown as $key => $value) {
            if (is_array($value)) {
                $breakdown[$key] = $this->normalizePricingBreakdown($value);
            }
        }

        if (! array_is_list($breakdown)) {
            ksort($breakdown);
        }

        return $breakdown;
    }
}
