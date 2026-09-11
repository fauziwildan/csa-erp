<?php

namespace App\Exports\Concerns;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter + RBAC scoping bersama untuk sheet-sheet export Laporan Penjualan
 * (dipakai oleh SalesDetailSheet & SalesSummarySheet agar hasilnya konsisten).
 */
trait ScopesSalesQuery
{
    protected function scopedSalesQuery(?string $storeId, ?string $dateFrom, ?string $dateTo, ?string $metode): Builder
    {
        $user = auth()->user();
        $isGlobal = $user && ($user->hasRole('superadmin') || $user->hasRole('owner') || $user->hasRole('finance'));

        $query = Sale::query()
            ->when($storeId,  fn($q) => $q->where('store_id', $storeId))
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->filterPaymentType($metode);

        if (!$isGlobal && $user) {
            $storeIds = $user->stores()->pluck('stores.id')->toArray();
            if (empty($storeIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('store_id', $storeIds);
            }
        }

        return $query;
    }
}
