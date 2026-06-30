<?php

namespace App\Services;

use App\Models\SaleItem;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal perhitungan komisi/reward & bonus.
 *
 * Basis: CASH-BASIS — komisi diakui saat nota LUNAS (sales.settled_at), bukan saat nota dibuat.
 * Net of return: dikurangi retur konsumen yang terjadi pada periode yang sama (berdasarkan
 * customer_returns.created_at) untuk retur atas nota yang sudah lunas.
 */
class RewardService
{
    /**
     * Reward & bonus satu toko untuk satu bulan (cash-basis, net of return).
     *
     * @return array{store:Store,target:int,total_qty:int,excess:int,
     *               regular_reward:float,owner_reward:float,bonus:float,total_reward:float}
     */
    public static function storeMonthly(Store $store, int $month, int $year): array
    {
        // 1) Komisi yang DIAKUI: item dari nota lunas yang settled di bulan ini.
        $gross = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.store_id', $store->id)
            ->where('sales.payment_status', 'lunas')
            ->whereNotNull('sales.settled_at')
            ->whereMonth('sales.settled_at', $month)
            ->whereYear('sales.settled_at', $year)
            ->selectRaw('COALESCE(SUM(sale_items.qty),0) AS qty,
                         COALESCE(SUM(sale_items.reward_store),0) AS store_reward,
                         COALESCE(SUM(sale_items.reward_owner),0) AS owner_reward')
            ->first();

        // 2) Pengurang RETUR (net of return) pada bulan terjadinya retur,
        //    hanya retur atas nota yang sudah lunas (komisinya pernah diakui).
        $ret = self::returnDeductions($store->id, $month, $year);

        $totalQty    = max(0, (int) $gross->qty - (int) $ret->qty);
        $storeReward = (float) $gross->store_reward - (float) $ret->store_reward;
        $ownerReward = (float) $gross->owner_reward - (float) $ret->owner_reward;

        $target = $store->getTargetForMonth($month, $year);
        $excess = 0;
        $bonus  = 0.0;
        if ($target > 0 && $totalQty > $target) {
            $excess = $totalQty - $target;
            $bonus  = floor($excess / 1000) * 1000000;
        }

        return [
            'store'          => $store,
            'target'         => $target,
            'total_qty'      => $totalQty,
            'excess'         => $excess,
            'regular_reward' => $storeReward,
            'owner_reward'   => $ownerReward,
            'bonus'          => $bonus,
            'total_reward'   => $storeReward + $bonus,
        ];
    }

    /**
     * Total reward terakui (cash-basis, net of return) untuk SETAHUN — dipakai dashboard.
     *
     * @return array{qty:int,store_reward:float,owner_reward:float}
     */
    public static function yearTotals(?int $storeId, int $year): array
    {
        $grossQuery = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.payment_status', 'lunas')
            ->whereNotNull('sales.settled_at')
            ->whereYear('sales.settled_at', $year)
            ->when($storeId, fn ($q) => $q->where('sales.store_id', $storeId));

        $gross = $grossQuery
            ->selectRaw('COALESCE(SUM(sale_items.qty),0) AS qty,
                         COALESCE(SUM(sale_items.reward_store),0) AS store_reward,
                         COALESCE(SUM(sale_items.reward_owner),0) AS owner_reward')
            ->first();

        $ret = self::returnDeductions($storeId, null, $year);

        return [
            'qty'          => max(0, (int) $gross->qty - (int) $ret->qty),
            'store_reward' => (float) $gross->store_reward - (float) $ret->store_reward,
            'owner_reward' => (float) $gross->owner_reward - (float) $ret->owner_reward,
        ];
    }

    /**
     * Hitung pengurang dari retur konsumen pada periode tertentu.
     * Reward per unit diambil dari snapshot sale_items nota asal (akurat, termasuk markup ecer).
     * Hanya retur atas nota yang sudah LUNAS yang ikut diperhitungkan.
     */
    private static function returnDeductions(?int $storeId, ?int $month, int $year)
    {
        $q = DB::table('customer_return_items as cri')
            ->join('customer_returns as cr', 'cr.id', '=', 'cri.customer_return_id')
            ->join('sales as os', 'os.id', '=', 'cr.sale_id')
            ->join('sale_items as si', function ($j) {
                $j->on('si.sale_id', '=', 'cr.sale_id')
                  ->on('si.product_variant_id', '=', 'cri.product_variant_id');
            })
            ->where('os.payment_status', 'lunas')
            ->whereYear('cr.created_at', $year)
            ->when($month, fn ($qq) => $qq->whereMonth('cr.created_at', $month))
            ->when($storeId, fn ($qq) => $qq->where('cr.store_id', $storeId));

        return $q->selectRaw('COALESCE(SUM(cri.qty),0) AS qty,
                              COALESCE(SUM(cri.qty * (si.reward_store / si.qty)),0) AS store_reward,
                              COALESCE(SUM(cri.qty * (si.reward_owner / si.qty)),0) AS owner_reward')
            ->first();
    }
}
