<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketItem extends Model
{
    public const TYPE_PART = 'part';
    public const TYPE_REPAIR = 'repair';

    protected $fillable = [
        'ticket_id',
        'part_id',
        'repair_id',
        'serial',
        'quantity',
        'type',
        'sold_price',
        'cost',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'sold_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'quantity' => 'integer'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }
}
