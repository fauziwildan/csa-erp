<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineOrder extends Model
{
    const STATUS_LABELS = [
        'pending'   => 'Menunggu Pembayaran',
        'paid'      => 'Lunas',
        'cancelled' => 'Dibatalkan',
    ];

    const STATUS_COLORS = [
        'pending'   => 'bg-yellow-100 text-yellow-700',
        'paid'      => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    protected $fillable = [
        'order_no', 'customer_name', 'customer_phone', 'address', 'notes',
        'warehouse_id', 'total_amount', 'total_qty', 'status', 'payment_note',
        'paid_at', 'paid_by', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'source',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'paid_at'       => 'datetime',
        'cancelled_at'  => 'datetime',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function items(): HasMany       { return $this->hasMany(OnlineOrderItem::class); }
    public function payer(): BelongsTo     { return $this->belongsTo(User::class, 'paid_by'); }
    public function canceller(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function isPending():   bool { return $this->status === 'pending'; }
    public function isPaid():      bool { return $this->status === 'paid'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

    public function statusLabel(): string { return self::STATUS_LABELS[$this->status] ?? $this->status; }
    public function statusColor(): string { return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-600'; }
}
