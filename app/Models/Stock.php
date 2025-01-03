<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'condition',
        'serial_number',
        'quantity',
        'selling_price',
        'color',
        'cost_price'
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'quantity' => 'integer'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
