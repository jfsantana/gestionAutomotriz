<?php

namespace App\Services;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Maintenance\Models\Service;
use App\Domain\Users\Models\ClientInvitation;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerOwnershipService
{
    public function update(Customer $customer, array $attributes): void
    {
        DB::transaction(function () use ($customer, $attributes): void {
            $locked = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $previousCompanyId = (int) $locked->company_id;
            $companyId = (int) ($attributes['company_id'] ?? $previousCompanyId);
            Company::findOrFail($companyId);

            $vehicles = Vehicle::withTrashed()->where('customer_id', $locked->id)->lockForUpdate()->get();
            $orders = MaintenanceOrder::withTrashed()->where('customer_id', $locked->id)->lockForUpdate()->get();

            if ($orders->pluck('vehicle_id')->diff($vehicles->modelKeys())->isNotEmpty()
                || MaintenanceOrder::withTrashed()->whereIn('vehicle_id', $vehicles->modelKeys())
                    ->where('customer_id', '!=', $locked->id)->exists()) {
                throw ValidationException::withMessages([
                    'company_id' => 'Hay órdenes cuyo vehículo y cliente no corresponden. Revisa esas asociaciones antes de trasladar o reconciliar al cliente.',
                ]);
            }

            if (Vehicle::withTrashed()->where('company_id', $companyId)
                ->where('customer_id', '!=', $locked->id)->whereIn('plate', $vehicles->pluck('plate'))->exists()) {
                throw ValidationException::withMessages([
                    'company_id' => 'No se puede completar el traslado: una placa ya está registrada en la empresa de destino.',
                ]);
            }

            $locked->fill($attributes);
            $locked->company_id = $companyId;
            $locked->save();

            Vehicle::withTrashed()->whereIn('id', $vehicles->modelKeys())->update(['company_id' => $companyId]);
            User::where('role', 'customer')->where('customer_id', $locked->id)->update(['company_id' => $companyId]);
            ClientInvitation::where('customer_id', $locked->id)->update(['company_id' => $companyId]);

            $orderQuery = MaintenanceOrder::withTrashed()->whereIn('id', $orders->modelKeys());
            (clone $orderQuery)->update(['company_id' => $companyId]);
            (clone $orderQuery)->whereNotNull('service_id')
                ->whereNotIn('service_id', Service::withTrashed()->where('company_id', $companyId)->select('id'))
                ->update(['service_id' => null]);
            (clone $orderQuery)->whereNotNull('mechanic_id')
                ->whereNotIn('mechanic_id', User::where('company_id', $companyId)->select('id'))
                ->update(['mechanic_id' => null]);
            (clone $orderQuery)->whereNotNull('registered_by_user_id')
                ->whereNotIn('registered_by_user_id', User::query()->where(fn (Builder $query) => $query
                    ->where('company_id', $companyId)->orWhere('role', 'super_admin'))->select('id'))
                ->update(['registered_by_user_id' => null]);

            Log::notice('Customer ownership synchronized.', [
                'customer_id' => $locked->id,
                'previous_company_id' => $previousCompanyId,
                'company_id' => $companyId,
                'vehicles' => $vehicles->count(),
                'orders' => $orders->count(),
                'order_ids' => $orders->modelKeys(),
                'previous_order_company_ids' => $orders->pluck('company_id')->unique()->values()->all(),
            ]);
        });

        $customer->refresh();
    }

    public function reconcile(Customer $customer): void
    {
        $this->update($customer, []);
    }
}
