<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;

class CreditService
{
    /**
     * Evaluasi apakah utang baru ($newDebt) untuk customer melanggar batas kredit GLOBAL.
     * credit_limit 0 = tidak boleh kredit sama sekali.
     *
     * @return object{over_limit:bool, mode:string, allowed:bool, requires_approval:bool,
     *                message:?string, limit:float, current_debt:float, projected_debt:float}
     */
    public static function evaluate(?Customer $customer, float $newDebt): object
    {
        $mode = (string) Setting::get('credit_mode', 'warning');

        $result = (object) [
            'over_limit'        => false,
            'mode'              => $mode,
            'allowed'           => true,
            'requires_approval' => false,
            'message'           => null,
            'limit'             => 0.0,
            'current_debt'      => 0.0,
            'projected_debt'    => 0.0,
        ];

        // Tidak ada utang baru (lunas) atau customer tidak teridentifikasi => tidak ada yang dibatasi.
        if ($newDebt <= 0 || ! $customer) {
            return $result;
        }

        $limit     = $customer->effectiveCreditLimit();
        $current   = $customer->outstanding_debt;
        $projected = $current + $newDebt;

        $result->limit          = $limit;
        $result->current_debt   = $current;
        $result->projected_debt = $projected;

        if ($projected <= $limit) {
            return $result; // masih dalam batas
        }

        $result->over_limit = true;
        $fmt  = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $base = "Utang {$customer->name} akan menjadi {$fmt($projected)}, melebihi batas {$fmt($limit)}.";

        switch ($mode) {
            case 'block':
                $result->allowed = false;
                $result->message = "{$base} Transaksi kredit ditolak.";
                break;

            case 'approval':
                $result->allowed           = false;
                $result->requires_approval = true;
                $result->message           = "{$base} Perlu persetujuan owner.";
                break;

            case 'warning':
            default:
                $result->allowed = true;
                $result->message = "Peringatan: {$base}";
                break;
        }

        return $result;
    }
}
