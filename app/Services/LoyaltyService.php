<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyLedger;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Beri poin atas sebuah nota yang sudah lunas. Idempotent per nota
     * (tidak menambah ulang jika sudah pernah diberi poin earn).
     */
    public static function award(Customer $customer, Sale $sale): void
    {
        $divisor = (int) Setting::get('loyalty_earn_divisor', 10000);
        if ($divisor <= 0) {
            return;
        }

        $points = (int) floor((float) $sale->total_amount / $divisor);
        if ($points <= 0) {
            return;
        }

        DB::transaction(function () use ($customer, $sale, $points) {
            // Cegah dobel: skip bila nota ini sudah punya entri earn.
            $exists = LoyaltyLedger::where('sale_id', $sale->id)->where('type', 'earn')->exists();
            if ($exists) {
                return;
            }

            LoyaltyLedger::create([
                'customer_id' => $customer->id,
                'sale_id'     => $sale->id,
                'points'      => $points,
                'type'        => 'earn',
                'note'        => "Poin dari nota {$sale->sale_no}",
                'created_by'  => Auth::id(),
            ]);

            $customer->increment('loyalty_points', $points);
        });
    }

    /** Tukar (kurangi) poin. Mengembalikan false bila poin tidak cukup. */
    public static function redeem(Customer $customer, int $points, ?string $note = null): bool
    {
        if ($points <= 0 || $points > (int) $customer->loyalty_points) {
            return false;
        }

        DB::transaction(function () use ($customer, $points, $note) {
            LoyaltyLedger::create([
                'customer_id' => $customer->id,
                'points'      => -$points,
                'type'        => 'redeem',
                'note'        => $note ?: 'Tukar poin',
                'created_by'  => Auth::id(),
            ]);
            $customer->decrement('loyalty_points', $points);
        });

        return true;
    }

    /** Koreksi manual poin (boleh + / −). */
    public static function adjust(Customer $customer, int $points, ?string $note = null): void
    {
        if ($points === 0) {
            return;
        }

        DB::transaction(function () use ($customer, $points, $note) {
            LoyaltyLedger::create([
                'customer_id' => $customer->id,
                'points'      => $points,
                'type'        => 'adjust',
                'note'        => $note ?: 'Penyesuaian poin',
                'created_by'  => Auth::id(),
            ]);
            $customer->increment('loyalty_points', $points); // increment menerima nilai negatif
        });
    }
}
