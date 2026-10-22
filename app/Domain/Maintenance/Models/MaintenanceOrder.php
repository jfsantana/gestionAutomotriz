<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function photos(): HasMany
    {
        return $this->hasMany(MaintenanceOrderPhoto::class, 'maintenance_order_id');
    }

    /**
     * Genera un número de orden legible por empresa: ORD-YYYYMM-0001
     */
    public static function generateOrderNumber(int $companyId): string
    {
        $prefix = 'ORD-' . date('Ym') . '-';
        $count = static::where('company_id', $companyId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count() + 1;

        return $prefix . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }
}
