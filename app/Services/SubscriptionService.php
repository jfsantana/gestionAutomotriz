<?php

namespace App\Services;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\CompanySubscriptionLog;
use App\Domain\Companies\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /**
     * Asigna o crea una nueva suscripción para una empresa, registrando auditoría.
     */
    public function assignSubscription(array $data, ?User $performer = null): CompanySubscription
    {
        return DB::transaction(function () use ($data, $performer) {
            $company = Company::findOrFail($data['company_id']);
            $newSubscription = new CompanySubscription($data);
            if (
                $company->hasActiveFleetCustomers()
                && $company->hasActiveSubscription()
                && ! $newSubscription->isOperable()
            ) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede bloquear esta empresa porque tiene clientes con una suscripción de flota vigente.',
                ]);
            }

            $data['created_by'] = $performer?->id;
            $data['updated_by'] = $performer?->id;

            $subscription = CompanySubscription::create($data);

            // Sincronizar estado en la tabla companies
            Company::where('id', $subscription->company_id)->update(['status' => $subscription->status]);

            CompanySubscriptionLog::create([
                'company_subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'previous_status' => null,
                'new_status' => $subscription->status,
                'action' => 'created',
                'notes' => $subscription->notes,
                'performed_by' => $performer?->id,
            ]);

            return $subscription;
        });
    }

    /**
     * Actualiza y gestiona la transición de estado de una suscripción.
     */
    public function updateSubscription(CompanySubscription $subscription, array $data, ?User $performer = null): CompanySubscription
    {
        return DB::transaction(function () use ($subscription, $data, $performer) {
            $previousStatus = $subscription->status;
            $newStatus = $data['status'];
            $company = $subscription->company;
            $updatedSubscription = clone $subscription;
            $updatedSubscription->fill($data);

            if (
                $company?->hasActiveFleetCustomers()
                && $subscription->isOperable()
                && ! $updatedSubscription->isOperable()
            ) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede bloquear esta empresa porque tiene clientes con una suscripción de flota vigente.',
                ]);
            }

            $action = 'updated';
            if ($previousStatus !== $newStatus) {
                $action = match ($newStatus) {
                    'active' => ($previousStatus === 'expired' ? 'renewed' : ($previousStatus === 'suspended' ? 'reactivated' : 'activated')),
                    'suspended' => 'suspended',
                    'expired' => 'expired',
                    'cancelled' => 'cancelled',
                    default => 'status_changed',
                };
            }

            if ($newStatus === 'suspended' && ! $subscription->suspended_at) {
                $data['suspended_at'] = now();
            } elseif ($newStatus === 'cancelled' && ! $subscription->cancelled_at) {
                $data['cancelled_at'] = now();
            } elseif ($newStatus === 'active') {
                $data['suspended_at'] = null;
                $data['renewed_at'] = now();
            }

            $data['updated_by'] = $performer?->id;
            $subscription->update($data);

            // Sincronizar estado de la empresa
            Company::where('id', $subscription->company_id)->update(['status' => $newStatus]);

            CompanySubscriptionLog::create([
                'company_subscription_id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'action' => $action,
                'notes' => $data['notes'] ?? null,
                'performed_by' => $performer?->id,
            ]);

            return $subscription;
        });
    }

    /**
     * Resumen de suscripciones para el dashboard de superadmin.
     */
    public function getSuperAdminSummary(): array
    {
        $companies = Company::with('currentSubscription')->get();

        $active = 0;
        $trial = 0;
        $expired = 0;
        $suspended = 0;
        $cancelled = 0;

        foreach ($companies as $comp) {
            $sub = $comp->currentSubscription;
            if (! $sub) {
                $expired++;

                continue;
            }

            match ($sub->status) {
                'active' => ($sub->ends_at && $sub->ends_at->isPast() ? $expired++ : $active++),
                'trial' => ($sub->ends_at && $sub->ends_at->isPast() ? $expired++ : $trial++),
                'expired' => $expired++,
                'suspended' => $suspended++,
                'cancelled' => $cancelled++,
                default => $expired++,
            };
        }

        return [
            'active' => $active,
            'trial' => $trial,
            'expired' => $expired,
            'suspended' => $suspended,
            'cancelled' => $cancelled,
            'total_companies' => $companies->count(),
        ];
    }
}
