<?php

namespace App\Domain\Companies\Models;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'tax_id',
        'email',
        'phone',
        'whatsapp',
        'website',
        'instagram_url',
        'facebook_url',
        'logo_path',
        'theme_palette',
        'address',
        'city',
        'state',
        'country',
        'status',
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
        ];
    }

    /**
     * Clientes asociados a la empresa.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'company_id');
    }

    /**
     * Vehículos asociados a la empresa.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'company_id');
    }

    /**
     * Suscripciones de la empresa.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class, 'company_id')->latest('id');
    }

    /**
     * Suscripción más reciente de la empresa.
     */
    public function currentSubscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class, 'company_id')->latestOfMany();
    }

    /**
     * Usuarios asociados a la empresa.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    /**
     * Administradores de la empresa.
     */
    public function admins(): HasMany
    {
        return $this->hasMany(User::class, 'company_id')->where('role', 'company_admin');
    }

    /**
     * Personal operativo (mecánicos) de la empresa.
     */
    public function mechanics(): HasMany
    {
        return $this->hasMany(User::class, 'company_id')->where('role', 'mechanic');
    }

    /**
     * Determina si la empresa tiene una suscripción operable.
     */
    public function hasActiveSubscription(): bool
    {
        $sub = $this->currentSubscription;

        return $sub && $sub->isOperable();
    }

    /**
     * Determina si algún cliente de la empresa tiene una suscripción de flota vigente.
     */
    public function hasActiveFleetCustomers(): bool
    {
        return $this->customers()
            ->where('is_fleet_premium', true)
            ->where(function ($query): void {
                $query->whereNull('fleet_expires_at')
                    ->orWhere('fleet_expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Genera un slug único para la empresa a partir de su nombre o valor base.
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'empresa';
        $counter = 1;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
