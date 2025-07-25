<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketItem extends Model
{
    const TYPE_PART = 'part';
    const TYPE_REPAIR = 'repair';

    protected $fillable = [
        'ticket_id',
        'part_id',
        'repair_id',
        'serial',
        'quantity',
        'type',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
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
