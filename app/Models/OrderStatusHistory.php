<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * desactivar la marca de tiempo updated_at
     */
    public const UPDATED_AT = null;

    /**
     * Relationship: historial pertenece a una orden
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
