<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerReturn extends Model
{
    protected $fillable = [
        'return_no', 'sale_id', 'store_id', 'return_reason_id',
        'status', 'notes', 'processed_at', 'processed_by', 'created_by',
        'cash_session_id', 'refund_amount',
        'type', 'refund_method', 'refund_bank_name', 'refund_bank_account',
        'refund_account_holder', 'refund_proof_path', 'exchange_sale_id', 'exchange_diff',
    ];

    protected $casts = ['processed_at' => 'datetime', 'exchange_diff' => 'decimal:2'];

    public function isExchange(): bool { return $this->type === 'exchange'; }

    public function sale(): BelongsTo         { return $this->belongsTo(Sale::class); }
    public function exchangeSale(): BelongsTo { return $this->belongsTo(Sale::class, 'exchange_sale_id'); }
    public function store(): BelongsTo        { return $this->belongsTo(Store::class); }
    public function cashSession(): BelongsTo  { return $this->belongsTo(CashSession::class); }
    public function reason(): BelongsTo       { return $this->belongsTo(ReturnReason::class, 'return_reason_id'); }
    public function processor(): BelongsTo    { return $this->belongsTo(User::class, 'processed_by'); }
    public function creator(): BelongsTo      { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany          { return $this->hasMany(CustomerReturnItem::class); }

    public function isPending():   bool { return $this->status === 'pending'; }
    public function isProcessed(): bool { return $this->status === 'processed'; }
    public function totalQty():    int  { return $this->items->sum('qty'); }
    public function totalValue():  float { return (float) $this->items->sum('subtotal'); }
}
