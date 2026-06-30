<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'sale_no', 'cash_session_id', 'store_id', 'customer_id', 'payment_method_id',
        'subtotal', 'discount_amount', 'total_amount',
        'amount_paid', 'change_amount', 'notes', 'created_by',
        'customer_name', 'customer_phone',
        'price_method', 'payment_status', 'dp_amount', 'due_date',
        'approval_status', 'approved_by', 'approved_at', 'settled_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'change_amount'   => 'decimal:2',
        'dp_amount'       => 'decimal:2',
        'due_date'        => 'date',
        'approved_at'     => 'datetime',
        'settled_at'      => 'datetime',
    ];

    public function cashSession(): BelongsTo   { return $this->belongsTo(CashSession::class); }
    public function store(): BelongsTo         { return $this->belongsTo(Store::class); }
    public function customer(): BelongsTo      { return $this->belongsTo(Customer::class); }
    public function paymentMethod(): BelongsTo { return $this->belongsTo(PaymentMethod::class); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo      { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany           { return $this->hasMany(SaleItem::class); }
    public function payments(): HasMany        { return $this->hasMany(SalePayment::class); }

    /** Sisa utang nota ini (total − sudah dibayar), tidak negatif. */
    public function remainingDue(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->amount_paid);
    }

    /** True bila nota ini dibayar dengan >1 metode (split payment). */
    public function isSplitPayment(): bool
    {
        return $this->payments->count() > 1;
    }

    /**
     * Label metode pembayaran untuk laporan/struk.
     * - Split  → "Tunai + Transfer Bank"
     * - Single → nama metode (dari sale_payments bila ada, fallback paymentMethod).
     */
    public function paymentMethodLabel(): string
    {
        if ($this->payments->isNotEmpty()) {
            return $this->payments
                ->map(fn ($p) => $p->paymentMethod?->name ?? '—')
                ->unique()
                ->implode(' + ');
        }

        return $this->paymentMethod?->name ?? '—';
    }

    /**
     * Rincian pembayaran per metode (digabung jika metode sama).
     * Mengembalikan koleksi ['name' => ..., 'amount' => ...].
     * Fallback ke metode tunggal bila sale_payments belum ada (nota lama).
     */
    public function paymentBreakdown()
    {
        if ($this->payments->isNotEmpty()) {
            return $this->payments
                ->groupBy('payment_method_id')
                ->map(fn ($group) => [
                    'name'   => $group->first()->paymentMethod?->name ?? '—',
                    'amount' => (float) $group->sum('amount'),
                ])
                ->values();
        }

        return collect([[
            'name'   => $this->paymentMethod?->name ?? '—',
            'amount' => (float) $this->amount_paid,
        ]]);
    }
}
