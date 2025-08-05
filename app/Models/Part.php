<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $fillable = [
        'part_name',
        'part_image',
        'quantity',
        'unit_price',
        'selling_price',
        'device_category',
        'grade',
        'description'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity' => 'integer',
        'device_category' => 'string',
        'grade' => 'string'
    ];
}
