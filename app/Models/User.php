<?php

namespace App\Models;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Users\Models\PlatformPolicy;
use App\Domain\Users\Models\UserPolicyAcceptance;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'company_id', 'customer_id', 'is_active', 'account_status', 'last_login_at', 'activated_at', 'email_verification_required'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    public function preferredLocale(): string
    {
        return 'es';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_required' => 'boolean',
            'last_login_at' => 'datetime',
            'activated_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Empresa asociada al usuario.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Cliente propietario asociado (para rol customer).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Determina si el usuario es cliente.
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Determina si el usuario es superadministrador.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Determina si el usuario es administrador de empresa.
     */
    public function isCompanyAdmin(): bool
    {
        return $this->role === 'company_admin';
    }

    /**
     * Historial de aceptaciones de políticas del usuario.
     */
    public function policyAcceptances(): HasMany
    {
        return $this->hasMany(UserPolicyAcceptance::class, 'user_id');
    }

    /**
     * Determina si el usuario ya aceptó la versión vigente de las políticas.
     */
    public function hasAcceptedCurrentPolicy(): bool
    {
        $activePolicy = PlatformPolicy::activePolicy();

        if (! $activePolicy) {
            return true; // Si no hay política publicada, no se bloquea
        }

        return $this->policyAcceptances()
            ->where('platform_policy_id', $activePolicy->id)
            ->exists();
    }
}
