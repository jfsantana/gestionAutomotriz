<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class MaintenanceOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'maintenance_orders';

    protected $fillable = [
        'company_id',
        'customer_id',
        'vehicle_id',
        'service_id',
        'mechanic_id',
        'registered_by_user_id',
        'order_number',
        'maintenance_type',
        'service_name_snapshot',
        'service_description_snapshot',
        'service_date',
        'current_mileage',
        'cost',
        'technical_notes',
        'customer_notes',
        'status',
        'origin',
        'is_verified',
        'next_due_date',
        'next_due_mileage',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'next_due_date' => 'date',
            'reminder_sent_at' => 'datetime',
            'cost' => 'decimal:2',
            'is_verified' => 'boolean',
            'current_mileage' => 'integer',
            'next_due_mileage' => 'integer',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (MaintenanceOrder $order): void {
            if ($order->exists && ! $order->isDirty(['company_id', 'customer_id', 'vehicle_id', 'service_id', 'mechanic_id', 'registered_by_user_id'])) {
                return;
            }

            $customer = Customer::find($order->customer_id);
            $vehicle = Vehicle::find($order->vehicle_id);
            if (! $customer || ! $vehicle || (int) $vehicle->customer_id !== (int) $customer->id
                || (int) $vehicle->company_id !== (int) $customer->company_id) {
                throw ValidationException::withMessages(['vehicle_id' => 'El vehículo debe pertenecer al cliente y a su empresa.']);
            }

            $order->company_id = $customer->company_id;

            if ($order->service_id && ! Service::whereKey($order->service_id)->where('company_id', $customer->company_id)->exists()) {
                throw ValidationException::withMessages(['service_id' => 'El servicio debe pertenecer a la empresa del cliente.']);
            }
            if ($order->mechanic_id && ! User::whereKey($order->mechanic_id)->where('company_id', $customer->company_id)->where('role', 'mechanic')->exists()) {
                throw ValidationException::withMessages(['mechanic_id' => 'El mecánico debe pertenecer a la empresa del cliente.']);
            }
            if ($order->registered_by_user_id && ! User::whereKey($order->registered_by_user_id)
                ->where(fn (Builder $query) => $query->where('company_id', $customer->company_id)->orWhere('role', 'super_admin'))->exists()) {
                throw ValidationException::withMessages(['registered_by_user_id' => 'El usuario que registra la orden no pertenece a la empresa del cliente.']);
            }
        });
    }

    #[Scope]
    protected function withConsistentOwnership(Builder $query): void
    {
        $query->whereHas('company')
            ->whereHas('customer', fn (Builder $customer) => $customer
                ->whereColumn('customers.company_id', 'maintenance_orders.company_id'))
            ->whereHas('vehicle', fn (Builder $vehicle) => $vehicle
                ->whereColumn('vehicles.company_id', 'maintenance_orders.company_id')
                ->whereColumn('vehicles.customer_id', 'maintenance_orders.customer_id'));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MaintenanceOrderPhoto::class, 'maintenance_order_id');
    }

    /**
     * Genera un número de orden legible por empresa: ORD-YYYYMM-0001
     */
    public static function generateOrderNumber(int $companyId): string
    {
        $prefix = 'ORD-'.date('Ym').'-';
        $count = static::where('company_id', $companyId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count() + 1;

        return $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
