<?php

namespace App\Services;

use App\Domain\Companies\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerOwnershipService
{
    /**
     * Actualiza datos del cliente sin trasladar la autoría de su historial.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Customer $customer, array $attributes): void
    {
        DB::transaction(function () use ($customer, $attributes): void {
            $locked = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $requestedCompanyId = $attributes['company_id'] ?? $locked->company_id;

            if ($requestedCompanyId !== $locked->company_id
                && (string) $requestedCompanyId !== (string) $locked->company_id) {
                throw ValidationException::withMessages([
                    'company_id' => 'No se puede trasladar un cliente directamente. El cliente debe aceptar una invitación del nuevo taller.',
                ]);
            }

            unset($attributes['company_id']);
            $locked->fill($attributes);
            $locked->save();
        });

        $customer->refresh();
    }

    public function reconcile(Customer $customer): void
    {
        $this->update($customer, []);
    }
}
