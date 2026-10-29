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
            if ($order->exists && ! $order->isDirty([
                'company_id',
                'customer_id',
                'vehicle_id',
                'service_id',
                'mechanic_id',
                'registered_by_user_id',
                'origin',
            ])) {
                return;
            }

            $customer = Customer::find($order->customer_id);
            $vehicle = Vehicle::find($order->vehicle_id);
            if (! $customer || ! $vehicle || (int) $vehicle->customer_id !== (int) $customer->id) {
                throw ValidationException::withMessages(['vehicle_id' => 'El vehículo debe pertenecer al cliente.']);
            }

            if ($order->origin === 'customer') {
                if ($order->service_id || $order->mechanic_id) {
                    throw ValidationException::withMessages(['origin' => 'Los registros personales no pueden atribuirse a servicios ni mecánicos de un taller.']);
                }

                if ($order->registered_by_user_id && ! User::whereKey($order->registered_by_user_id)
                    ->where('role', 'customer')
                    ->where('customer_id', $customer->id)
                    ->exists()) {
                    throw ValidationException::withMessages(['registered_by_user_id' => 'El registro personal debe pertenecer al cliente del vehículo.']);
                }

                $order->company_id = null;

                return;
            }

            // La orden de taller pertenece al taller asignado al vehículo, que puede
            // diferir del taller actual del cliente (cada vehículo puede tener su taller).
            if (! $vehicle->company_id) {
                throw ValidationException::withMessages(['vehicle_id' => 'El vehículo debe tener un taller asignado antes de registrar una orden de taller.']);
            }

            $order->company_id = $vehicle->company_id;

            if ($order->service_id && ! Service::whereKey($order->service_id)->where('company_id', $vehicle->company_id)->exists()) {
                throw ValidationException::withMessages(['service_id' => 'El servicio debe pertenecer al taller asignado al vehículo.']);
            }
            if ($order->mechanic_id && ! User::whereKey($order->mechanic_id)->where('company_id', $vehicle->company_id)->where('role', 'mechanic')->exists()) {
                throw ValidationException::withMessages(['mechanic_id' => 'El mecánico debe pertenecer al taller asignado al vehículo.']);
            }
            if ($order->registered_by_user_id && ! User::whereKey($order->registered_by_user_id)
                ->where(fn (Builder $query) => $query
                    ->where('role', 'super_admin')
                    ->orWhere(fn (Builder $company) => $company
                        ->where('company_id', $vehicle->company_id)
                        ->whereIn('role', ['company_admin', 'mechanic'])))->exists()) {
                throw ValidationException::withMessages(['registered_by_user_id' => 'El usuario que registra la orden no pertenece al taller asignado al vehículo.']);
            }
        });
    }

    #[Scope]
    protected function withConsistentOwnership(Builder $query): void
    {
        $query->whereHas('vehicle', fn (Builder $vehicle) => $vehicle
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
