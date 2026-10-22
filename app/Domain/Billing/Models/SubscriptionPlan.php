<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'max_internal_users',
        'max_mechanics',
        'max_maintenance_orders_per_month',
        'max_vehicles',
        'duration_days',
        'is_trial',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'max_internal_users' => 'integer',
            'max_mechanics' => 'integer',
            'max_maintenance_orders_per_month' => 'integer',
            'max_vehicles' => 'integer',
            'duration_days' => 'integer',
            'is_trial' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class, 'subscription_plan_id');
    }
}
