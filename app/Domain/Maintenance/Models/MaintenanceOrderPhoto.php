<?php

namespace App\Domain\Maintenance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceOrderPhoto extends Model
{
    use HasFactory;

    protected $table = 'maintenance_order_photos';

    protected $fillable = [
        'maintenance_order_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function maintenanceOrder(): BelongsTo
    {
        return $this->belongsTo(MaintenanceOrder::class, 'maintenance_order_id');
    }
}
