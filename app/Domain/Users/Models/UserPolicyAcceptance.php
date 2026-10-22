<?php

namespace App\Domain\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPolicyAcceptance extends Model
{
    use HasFactory;

    protected $table = 'user_policy_acceptances';

    protected $fillable = [
        'user_id',
        'platform_policy_id',
        'policy_version',
        'accepted_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(PlatformPolicy::class, 'platform_policy_id');
    }
}
