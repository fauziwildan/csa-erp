<?php

namespace App\Services;

use App\Models\SaleItem;
use App\Models\Sale;
use App\Models\Settlement;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

/**
 * Hitung kewajiban setoran toko → owner.
 *
 * Kebijakan (2026-06-30): setoran = hasil jual − komisi toko (Rp500/item),
 * basis CASH (nota lunas/settled), net of return. Bonus toko TIDAK mengurangi setoran
 * (dibayar terpisah oleh owner).
 *
 *   obligation = (Σ total_amount nota lunas − Σ nilai retur)
 *              − (Σ reward_store nota lunas − Σ reward_store retur)
 */
class SettlementService
{
    /** Total kewajiban kumulatif toko ke owner sampai saat ini. */
    public static function obligation(Store $store): float
    {
        $grossSales = (float) Sale::where('store_id', $store->id)
            ->where('payment_status', 'lunas')->whereNotNull('settled_at')
            ->sum('total_amount');

        $grossTokoComm = (float) SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.store_id', $store->id)
            ->where('sales.payment_status', 'lunas')->whereNotNull('sales.settled_at')
            ->sum('sale_items.reward_store');

        // Retur atas nota yang sudah lunas (mengurangi hasil jual & komisi toko terkait).
        $ret = DB::table('customer_return_items as cri')
            ->join('customer_returns as cr', 'cr.id', '=', 'cri.customer_return_id')
            ->join('sales as os', 'os.id', '=', 'cr.sale_id')
            ->leftJoin('sale_items as si', function ($j) {
                $j->on('si.sale_id', '=', 'cr.sale_id')
                  ->on('si.product_variant_id', '=', 'cri.product_variant_id');
            })
            ->where('cr.store_id', $store->id)
            ->where('os.payment_status', 'lunas')
            ->selectRaw('COALESCE(SUM(cri.subtotal),0) AS val,
                         COALESCE(SUM(cri.qty * (si.reward_store / si.qty)),0) AS toko')
            ->first();

        return ($grossSales - (float) $ret->val) - ($grossTokoComm - (float) $ret->toko);
    }

    /** Total yang sudah disetor toko. */
    public static function settled(Store $store): float
    {
        return (float) Settlement::where('store_id', $store->id)->sum('amount');
    }

    /** Ringkasan: kewajiban, sudah disetor, sisa. */
    public static function summary(Store $store): array
    {
        $obligation = self::obligation($store);
        $settled    = self::settled($store);

        return [
            'obligation'  => $obligation,
            'settled'     => $settled,
            'outstanding' => $obligation - $settled,
        ];
    }
}
