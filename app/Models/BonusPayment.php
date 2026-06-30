<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusPayment extends Model
{
    protected $fillable = [
        'store_id', 'period_month', 'period_year', 'amount', 'paid_at', 'method', 'proof_path', 'note', 'recorded_by',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function store(): BelongsTo    { return $this->belongsTo(Store::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
