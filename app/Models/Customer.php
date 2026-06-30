<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'city',
        'credit_limit',
        'loyalty_points',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'credit_limit' => 'decimal:2',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function loyaltyLedgers(): HasMany
    {
        return $this->hasMany(LoyaltyLedger::class)->latest();
    }

    /**
     * Total utang berjalan customer ini, dihitung LINTAS TOKO
     * (kredit melekat ke customer, bukan toko).
     */
    public function getOutstandingDebtAttribute(): float
    {
        return (float) $this->sales()
            ->where('payment_status', '!=', 'lunas')
            ->selectRaw('COALESCE(SUM(GREATEST(total_amount - amount_paid, 0)), 0) as debt')
            ->value('debt');
    }

    /**
     * Limit efektif: pakai override per-customer bila diisi, selain itu setelan GLOBAL.
     */
    public function effectiveCreditLimit(): float
    {
        if (! is_null($this->credit_limit)) {
            return (float) $this->credit_limit;
        }

        return (float) Setting::get('credit_limit', 0);
    }
}
