<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'contact_number',
        'payment_method',
        'total_amount'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
