<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnedItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'return_type',
        'returned_at'
    ];

    protected $casts = [
        'returned_at' => 'datetime'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}