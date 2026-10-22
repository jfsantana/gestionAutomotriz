<?php

namespace App\Domain\Billing\Models;

use App\Domain\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySubscriptionLog extends Model
{
    use HasFactory;

    protected $table = 'company_subscription_logs';

    protected $fillable = [
        'company_subscription_id',
        'company_id',
        'previous_status',
        'new_status',
        'action',
        'notes',
        'performed_by',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CompanySubscription::class, 'company_subscription_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
