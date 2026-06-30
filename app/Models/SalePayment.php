<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id', 'amount', 'payment_method_id', 'paid_at', 'received_by', 'proof_path', 'note',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function sale(): BelongsTo          { return $this->belongsTo(Sale::class); }
    public function paymentMethod(): BelongsTo { return $this->belongsTo(PaymentMethod::class); }
    public function receiver(): BelongsTo      { return $this->belongsTo(User::class, 'received_by'); }
}
