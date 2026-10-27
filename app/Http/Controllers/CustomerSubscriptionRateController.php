<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Models\CustomerSubscriptionRate;
use App\Domain\Vehicles\Enums\VehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerSubscriptionRateController extends Controller
{
    private const CUSTOMER_TYPES = [
        'individual' => 'Cliente individual',
        'fleet_owner' => 'Propietario de flota',
    ];

    public function edit(): View
    {
        $vehicleTypes = VehicleType::options();
        $rates = CustomerSubscriptionRate::query()
            ->get()
            ->keyBy(fn (CustomerSubscriptionRate $rate): string => $rate->customer_type.'|'.$rate->vehicle_type);

        return view('customer_subscription_rates.edit', [
            'customerTypes' => self::CUSTOMER_TYPES,
            'vehicleTypes' => $vehicleTypes,
            'rates' => $rates,
            'annualBillingMonths' => (int) config('customer_subscriptions.annual_billing_months'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $vehicleTypeKeys = array_keys(VehicleType::options());
        $allowedVehicleTypes = implode(',', $vehicleTypeKeys);
        $rules = [
            'rates' => ['required', 'array:individual,fleet_owner'],
        ];

        foreach (array_keys(self::CUSTOMER_TYPES) as $customerType) {
            $rules["rates.{$customerType}"] = ['required', "array:{$allowedVehicleTypes}"];

            foreach ($vehicleTypeKeys as $vehicleType) {
                $rules["rates.{$customerType}.{$vehicleType}"] = [
                    'required',
                    'numeric',
                    'decimal:0,2',
                    'min:0',
                    'max:99999999.99',
                ];
            }
        }

        $validated = $request->validate($rules);
        $now = now();
        $rates = [];

        foreach ($validated['rates'] as $customerType => $vehicleRates) {
            foreach ($vehicleRates as $vehicleType => $monthlyPrice) {
                $rates[] = [
                    'customer_type' => $customerType,
                    'vehicle_type' => $vehicleType,
                    'monthly_price' => $monthlyPrice,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($rates): void {
            CustomerSubscriptionRate::query()->upsert(
                $rates,
                ['customer_type', 'vehicle_type'],
                ['monthly_price', 'updated_at'],
            );
        });

        return to_route('customer-subscription-rates.edit')
            ->with('success', 'Las tarifas de suscripción de clientes se actualizaron correctamente.');
    }
}
