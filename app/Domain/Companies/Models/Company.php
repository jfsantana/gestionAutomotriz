<?php

namespace App\Domain\Companies\Models;

use App\Domain\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

