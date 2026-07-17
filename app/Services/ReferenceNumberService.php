<?php

namespace App\Services;

use App\Models\CustomerReturn;
use App\Models\Inbound;
use App\Models\OnlineOrder;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\StockOpname;
use App\Models\StoreReturn;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class ReferenceNumberService
{
    /**
     * Generate: WKO-YYYYMM-NNNN (pesanan online dari katalog)
     */
    public static function onlineOrder(): string
    {
        return self::generate('WKO', fn($prefix) =>
            OnlineOrder::where('order_no', 'like', "{$prefix}%")->max('order_no')
        );
    }

    /**
     * Generate: INB-YYYYMM-NNNN
     */
    public static function inbound(): string
    {
        return self::generate('INB', fn($prefix) =>
            Inbound::where('reference_no', 'like', "{$prefix}%")->max('reference_no')
        );
    }

    /**
     * Generate: SHP-YYYYMM-NNNN
     */
    public static function shipment(): string
    {
        return self::generate('SHP', fn($prefix) =>
            Shipment::where('shipment_no', 'like', "{$prefix}%")->max('shipment_no')
        );
    }

    /** Generate: CRT-YYYYMM-NNNN */
    public static function customerReturn(): string
    {
        return self::generate('CRT', fn($prefix) =>
            CustomerReturn::where('return_no', 'like', "{$prefix}%")->max('return_no')
        );
    }

    /** Generate: SRT-YYYYMM-NNNN */
    public static function storeReturn(): string
    {
        return self::generate('SRT', fn($prefix) =>
            StoreReturn::where('return_no', 'like', "{$prefix}%")->max('return_no')
        );
    }

    /** Generate: OPN-YYYYMM-NNNN */
    public static function opname(): string
    {
        return self::generate('OPN', fn($prefix) =>
            StockOpname::where('opname_no', 'like', "{$prefix}%")->max('opname_no')
        );
    }

    /**
     * Generate: SAL-YYYYMM-NNNN
     */
    public static function sale(): string
    {
        return self::generate('SAL', fn($prefix) =>
            Sale::where('sale_no', 'like', "{$prefix}%")->max('sale_no')
        );
    }

    /**
     * Generate: TRF-YYYYMM-NNNN
     */
    public static function transfer(): string
    {
        return self::generate('TRF', fn($prefix) =>
            Transfer::where('transfer_no', 'like', "{$prefix}%")->max('transfer_no')
        );
    }

    private static function generate(string $type, callable $maxFn): string
    {
        $prefix = $type . '-' . now()->format('Ym') . '-';
        $last   = $maxFn($prefix);

        if ($last) {
            $seq = (int) substr($last, strrpos($last, '-') + 1);
        } else {
            $seq = 0;
        }

        return $prefix . str_pad($seq + 1, 4, '0', STR_PAD_LEFT);
    }
}
