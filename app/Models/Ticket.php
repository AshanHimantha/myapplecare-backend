<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Ticket",
 *     type="object",
 *     title="Ticket",
 *     description="Ticket model",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="repaired_by", type="integer", nullable=true, example=2),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="contact_number", type="string", example="+1234567890"),
 *     @OA\Property(property="priority", type="string", enum={"low","medium","high"}, example="medium"),
 *     @OA\Property(property="device_category", type="string", enum={"iphone","android","other"}, example="iphone"),
 *     @OA\Property(property="device_model", type="string", example="iPhone 14 Pro"),
 *     @OA\Property(property="imei", type="string", nullable=true, example="123456789012345"),
 *     @OA\Property(property="issue", type="string", example="Screen not working"),
 *     @OA\Property(property="status", type="string", enum={"open","in_progress","completed"}, example="open"),
 *     @OA\Property(property="service_charge", type="number", format="float", example=150.00),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-15T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-15T10:00:00Z"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="repairedBy", ref="#/components/schemas/User")
 * )
 */
class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'repaired_by',
        'first_name',
        'last_name',
        'contact_number',
        'priority',
        'device_category',
        'device_model',
        'imei',
        'issue',
        'status',
        'service_charge'
        , 'payment_type'
    ];

    protected $casts = [
        'priority' => 'string',
        'device_category' => 'string',
        'status' => 'string'
        , 'payment_type' => 'string'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repairedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repaired_by');
    }

    public function items()
    {
        return $this->hasMany(TicketItem::class);
    }
}
