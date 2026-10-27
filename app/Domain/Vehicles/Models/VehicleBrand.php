<?php

namespace App\Domain\Vehicles\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleBrand extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];
}
