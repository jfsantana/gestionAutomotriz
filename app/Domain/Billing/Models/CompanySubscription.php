<?php

namespace App\Domain\Billing\Models;

use App\Domain\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class CompanySubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'company_subscriptions';

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'suspended_at',
        'cancelled_at',
        'renewed_at',
        'auto_renew',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'suspended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'renewed_at' => 'datetime',
            'auto_renew' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CompanySubscriptionLog::class, 'company_subscription_id')->latest('id');
    }

    /**
     * Determina si la suscripción permite acceso operativo al sistema.
     */
    public function isOperable(): bool
    {
        if ($this->status === 'expired') {
            return $this->isInGracePeriod();
        }

        if (! in_array($this->status, ['trial', 'active'], true)) {
            return false;
        }

        if (! $this->ends_at) {
            return true;
        }

        return $this->ends_at->isToday()
            || $this->ends_at->isFuture()
            || $this->isInGracePeriod();
    }

    public function isInGracePeriod(): bool
    {
        if (! in_array($this->status, ['trial', 'active', 'expired'], true) || ! $this->ends_at) {
            return false;
        }

        $graceEndsAt = $this->ends_at->copy()->endOfDay()->addDays(3);

        return now()->greaterThan($this->ends_at->copy()->endOfDay())
            && now()->lessThanOrEqualTo($graceEndsAt);
    }

    public function graceEndsAt(): ?Carbon
    {
        return $this->ends_at?->copy()->endOfDay()->addDays(3);
    }

    /**
     * Días restantes hasta el vencimiento.
     */
    public function daysRemaining(): ?int
    {
        if (! $this->ends_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->ends_at->startOfDay(), false);
    }
}
