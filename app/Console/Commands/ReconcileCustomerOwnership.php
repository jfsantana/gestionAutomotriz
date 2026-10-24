<?php

namespace App\Console\Commands;

use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Vehicles\Models\Vehicle;
use App\Services\CustomerOwnershipService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ReconcileCustomerOwnership extends Command
{
    protected $signature = 'ownership:reconcile-customer {customer : ID del cliente} {--apply : Aplicar los cambios; sin esta opción solo muestra el alcance}';

    protected $description = 'Alinea vehículos e historial con la empresa del cliente sin enviar correos.';

    public function handle(CustomerOwnershipService $ownership): int
    {
        $id = (string) $this->argument('customer');
        if (! ctype_digit($id) || (int) $id < 1) {
            $this->error('El ID del cliente debe ser un entero positivo.');

            return self::FAILURE;
        }
        $customer = Customer::find($id);
        if (! $customer) {
            $this->error('Cliente no encontrado.');

            return self::FAILURE;
        }

        $this->info("Cliente #{$customer->id} | Empresa de referencia #{$customer->company_id}");
        $this->line('Vehículos: '.Vehicle::withTrashed()->where('customer_id', $customer->id)->count());
        $this->line('Órdenes: '.MaintenanceOrder::withTrashed()->where('customer_id', $customer->id)->count());

        if (! $this->option('apply')) {
            $this->comment('Sin cambios. Usa --apply para reconciliar y desvincular las referencias de otras empresas.');

            return self::SUCCESS;
        }

        try {
            $ownership->reconcile($customer);
        } catch (ValidationException $exception) {
            $this->error(implode(' ', $exception->errors()['company_id']));

            return self::FAILURE;
        }

        $this->info('Pertenencia reconciliada. No se enviaron correos.');

        return self::SUCCESS;
    }
}
