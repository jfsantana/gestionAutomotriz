<?php

namespace App\Domain\Companies\Models;

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

    /**
     * Usuario de acceso al portal vinculado al cliente.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'customer_id');
    }
}
