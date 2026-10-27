<?php

namespace App\Domain\Billing\Models;

use App\Domain\Companies\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPayment extends Model
{
    use SoftDeletes;

    protected $table = 'pagos_clientes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'customer_type',
        'vehicle_count',
        'billing_cycle',
        'amount',
        'currency',
        'status',
        'pricing_breakdown',
        'paid_at',
        'reversed_at',
        'confirmed_by',
        'reversed_by',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_count' => 'integer',
            'amount' => 'decimal:2',
            'pricing_breakdown' => 'array',
            'paid_at' => 'datetime',
            'reversed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
