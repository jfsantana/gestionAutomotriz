<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSubscriptionRate extends Model
{
    protected $fillable = [
        'customer_type',
        'vehicle_type',
        'monthly_price',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
        ];
    }
}
