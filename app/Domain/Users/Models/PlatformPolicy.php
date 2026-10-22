<?php

namespace App\Domain\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformPolicy extends Model
{
    use HasFactory;

    protected $table = 'platform_policies';

    protected $fillable = [
        'version',
        'title',
        'content',
        'effective_date',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(UserPolicyAcceptance::class, 'platform_policy_id');
    }

    /**
     * Obtiene la política actualmente activa / vigente.
     */
    public static function activePolicy(): ?self
    {
        return static::where('is_active', true)->latest('id')->first();
    }
}
