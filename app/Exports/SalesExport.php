<?php

namespace App\Exports;

use App\Exports\Sheets\SalesDetailSheet;
use App\Exports\Sheets\SalesSummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export Laporan Penjualan: sheet 1 = ringkasan + grafik (periode filter,
 * default hari ini), sheet 2 = rincian per-nota/per-item.
 */
class SalesExport implements WithMultipleSheets
{
    public function __construct(
        protected ?string $storeId  = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo   = null,
        protected ?string $metode   = null,
    ) {}

    public function sheets(): array
    {
        return [
            new SalesSummarySheet($this->storeId, $this->dateFrom, $this->dateTo, $this->metode),
            new SalesDetailSheet($this->storeId, $this->dateFrom, $this->dateTo, $this->metode),
        ];
    }
}
