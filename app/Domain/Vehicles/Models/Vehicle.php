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
            if ($vehicle->customer_id) {
                $customer = $vehicle->customer ?: Customer::find($vehicle->customer_id);
                if (! $customer) {
                    throw new \InvalidArgumentException('El vehículo debe tener un cliente asociado válido.');
                }
                $vehicle->company_id = $customer->company_id;
            } else {
                throw new \InvalidArgumentException('Todo vehículo debe tener obligatoriamente un cliente asociado.');
            }
        });

        static::updating(function (Vehicle $vehicle) {
            if ($vehicle->isDirty('customer_id')) {
                throw ValidationException::withMessages(['customer_id' => 'No se puede reasignar un vehículo a otro cliente. Debe darse de baja antes de un nuevo registro.']);
            }
            $customerId = $vehicle->customer_id ?: $vehicle->getOriginal('customer_id');
            if ($customerId) {
                $customer = Customer::find($customerId);
                if (! $customer) {
                    throw new \InvalidArgumentException('El vehículo debe tener un cliente asociado válido.');
                }
                $vehicle->company_id = $customer->company_id;
            } else {
                throw new \InvalidArgumentException('Todo vehículo debe tener obligatoriamente un cliente asociado.');
            }
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

    #[Scope]
    protected function withConsistentOwnership(Builder $query): void
    {
        $query->whereHas('customer', fn (Builder $customer) => $customer
            ->where(function (Builder $company) {
                $company->whereColumn('customers.company_id', 'vehicles.company_id')
                    ->orWhere(function (Builder $bothNull): void {
                        $bothNull->whereNull('customers.company_id')
                            ->whereNull('vehicles.company_id');
                    });
            }));
    }
}
