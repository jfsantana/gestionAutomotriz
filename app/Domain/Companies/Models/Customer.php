<?php

namespace App\Domain\Companies\Models;

use App\Domain\Billing\Models\CustomerPayment;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * Default values available before the model is persisted.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_fleet_premium' => false,
        'vehicle_limit' => 3,
        'fleet_expires_at' => null,
        'fleet_trial_started_at' => null,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'full_name',
        'email',
        'phone',
        'document_type',
        'document_number',
        'customer_type',
        'is_fleet_premium',
        'vehicle_limit',
        'fleet_expires_at',
        'fleet_trial_started_at',
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'is_fleet_premium' => 'boolean',
            'vehicle_limit' => 'integer',
            'fleet_expires_at' => 'datetime',
            'fleet_trial_started_at' => 'datetime',
        ];
    }

    /**
     * Empresa a la que pertenece el cliente.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Vehículos activos del cliente (excluye dados de baja por el cliente).
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'customer_id')->withConsistentOwnership()->where('customer_status', 'ACTIVE');
    }

    /**
     * Todos los vehículos históricos vinculados.
     */
    public function allVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'customer_id')->withConsistentOwnership();
    }

    public function hasReachedVehicleLimit(): bool
    {
        if ($this->isFleetPremiumActive() && $this->vehicle_limit === null) {
            return false;
        }

        return $this->vehicles()->count() > ($this->vehicle_limit ?? 3);
    }

    public function hasFleetTrialActive(): bool
    {
        return $this->fleet_trial_started_at !== null
            && now()->isBefore($this->fleet_trial_started_at->copy()->addMonths(2));
    }

    public function isFleetPremiumActive(): bool
    {
        return $this->is_fleet_premium
            && (! $this->fleet_expires_at || $this->fleet_expires_at->isFuture());
    }

    public function hasFleetPortalAccess(): bool
    {
        return $this->isFleetPremiumActive() || $this->hasFleetTrialActive();
    }

    public function startFleetTrialIfNeeded(): void
    {
        if ($this->fleet_trial_started_at === null) {
            static::query()
                ->whereKey($this->getKey())
                ->whereNull('fleet_trial_started_at')
                ->update(['fleet_trial_started_at' => now()]);

            $this->refresh();
        }
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'customer_id')->latest('id');
    }

    /**
     * Usuario de acceso al portal vinculado al cliente.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'customer_id');
    }
}
