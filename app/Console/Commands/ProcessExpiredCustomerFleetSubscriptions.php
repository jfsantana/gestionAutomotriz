<?php

namespace App\Console\Commands;

use App\Domain\Companies\Models\Customer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fleet-subscriptions:process-expired')]
#[Description('Restablece el límite de vehículos de los planes Propietario de Flota vencidos.')]
class ProcessExpiredCustomerFleetSubscriptions extends Command
{
    public function handle(): int
    {
        $expiredCount = Customer::query()
            ->where('is_fleet_premium', true)
            ->whereNotNull('fleet_expires_at')
            ->where('fleet_expires_at', '<=', now())
            ->update([
                'is_fleet_premium' => false,
                'vehicle_limit' => 3,
            ]);

        $this->info("Se restableció el límite de 3 vehículos para {$expiredCount} clientes.");

        return Command::SUCCESS;
    }
}
