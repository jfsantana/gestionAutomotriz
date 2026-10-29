<?php

namespace App\Domain\Vehicles\Models;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehicles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'customer_id',
        'plate',
        'vin',
        'vehicle_type',
        'make',
        'model',
        'year',
        'color',
        'photo_path',
        'current_mileage',
        'fuel_type',
        'customer_status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'current_mileage' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot del modelo para forzar que el vehículo siempre pertenezca
     * a la misma empresa que su cliente asociado sin excepción.
     */
    protected static function booted(): void
    {
        static::creating(function (Vehicle $vehicle) {
            if (! $vehicle->customer_id) {
                throw new \InvalidArgumentException('Todo vehículo debe tener obligatoriamente un cliente asociado.');
            }

            $customer = $vehicle->customer ?: Customer::find($vehicle->customer_id);
            if (! $customer) {
                throw new \InvalidArgumentException('El vehículo debe tener un cliente asociado válido.');
            }

            // Si no se indicó un taller explícito, el vehículo hereda el taller actual del
            // cliente. Un company_id explícito (incluso null) se respeta: cada vehículo
            // puede quedar asignado a un taller distinto o como particular del cliente.
            if (! array_key_exists('company_id', $vehicle->getAttributes())) {
                $vehicle->company_id = $customer->company_id;
            }
        });

        static::updating(function (Vehicle $vehicle) {
            if ($vehicle->isDirty('customer_id')) {
                throw ValidationException::withMessages(['customer_id' => 'No se puede reasignar un vehículo a otro cliente. Debe darse de baja antes de un nuevo registro.']);
            }
            $customerId = $vehicle->customer_id ?: $vehicle->getOriginal('customer_id');
            if (! $customerId || ! Customer::find($customerId)) {
                throw new \InvalidArgumentException('El vehículo debe tener un cliente asociado válido.');
            }
            // El taller del vehículo (company_id) es independiente del taller actual del cliente:
            // un cliente puede tener vehículos asignados a talleres distintos.
        });
    }

    /**
     * Empresa del vehículo.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Propietario / Cliente del vehículo.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Historial de órdenes de mantenimiento del vehículo.
     */
    public function maintenanceOrders(): HasMany
    {
        return $this->hasMany(MaintenanceOrder::class, 'vehicle_id');
    }

    /**
     * Vehículos cuyo taller asignado coincide con el taller actual del cliente.
     * Los vehículos particulares (company_id null) siempre son consistentes.
     */
    #[Scope]
    protected function withConsistentOwnership(Builder $query): void
    {
        $query->whereHas('customer', fn (Builder $customer) => $customer
            ->where(function (Builder $company) {
                $company->whereColumn('customers.company_id', 'vehicles.company_id')
                    ->orWhereNull('vehicles.company_id')
                    ->orWhere(function (Builder $bothNull): void {
                        $bothNull->whereNull('customers.company_id')
                            ->whereNull('vehicles.company_id');
                    });
            }));
    }
}
