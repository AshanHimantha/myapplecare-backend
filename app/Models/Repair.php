<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    protected $fillable = [
        'repair_name',
        'device_category',
        'cost',
        'selling_price',
        'description'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'device_category' => 'string'
    ];
}
