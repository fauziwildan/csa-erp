<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\ScopesSalesQuery;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/** Sheet rinci per-nota/per-item untuk Laporan Penjualan (lihat SalesSummarySheet untuk ringkasan+grafik). */
class SalesDetailSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithEvents
{
    use ScopesSalesQuery;

    /** Kode format Rupiah: "Rp 1.234.567" (grouping ikut regional Excel). */
    private const RP_FORMAT = '"Rp" #,##0';

    /** Kolom level-nota (A..J) yang di-merge untuk nota dengan >1 item. */
    private array $merges = [];

    /** Baris pertama tiap nota (untuk ditebalkan), single maupun multi-item. */
    private array $noteStartRows = [];

    public function __construct(
        protected ?string $storeId  = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo   = null,
        protected ?string $metode   = null,
    ) {}

    public function collection(): Collection
    {
        $sales = $this->scopedSalesQuery($this->storeId, $this->dateFrom, $this->dateTo, $this->metode)
            ->with([
                'store',
                'paymentMethod',
                'payments.paymentMethod',
                'items.variant' => fn($q) => $q->withTrashed(),
                'items.variant.product' => fn($q) => $q->withTrashed(),
                'items.variant.color',
                'items.variant.size',
                'creator',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = [];
        $excelRow = 2; // baris 1 = heading

        foreach ($sales as $sale) {
            [$cash, $transfer] = $this->splitCashTransfer($sale);
            $itemCount = max(1, $sale->items->count());
            $start = $excelRow;
            $this->noteStartRows[] = $start;

            foreach ($sale->items as $idx => $item) {
                $rows[] = [
                    'sale_no'         => $idx === 0 ? $sale->sale_no : '',
                    'store'           => $idx === 0 ? $sale->store->name : '',
                    'payment'         => $idx === 0 ? $sale->paymentMethodLabel() : '',
                    'tunai'           => $idx === 0 ? $cash : null,
                    'transfer'        => $idx === 0 ? $transfer : null,
                    'cashier'         => $idx === 0 ? ($sale->creator?->name ?? '-') : '',
                    'subtotal_before' => $idx === 0 ? (float) $sale->subtotal : null,
                    'discount'        => $idx === 0 ? (float) $sale->discount_amount : null,
                    'total'           => $idx === 0 ? (float) $sale->total_amount : null,
                    'date'            => $idx === 0 ? $sale->created_at->format('d/m/Y H:i') : '',
                    'product'         => $item->variant?->product?->name ?? 'Produk Terhapus',
                    'sku'             => ($item->variant?->sku ?? '-') . " (" . ($item->variant?->color?->name ?? '-') . " / " . ($item->variant?->size?->name ?? '-') . ")",
                    'qty'             => (int) $item->qty,
                    'price'           => (float) $item->unit_price,
                    'subtotal'        => (float) $item->subtotal,
                ];
            }

            $excelRow += $itemCount;

            // Merge kolom level-nota (A..J) bila nota punya >1 item → keterangan tidak kosong.
            if ($itemCount > 1) {
                $end = $start + $itemCount - 1;
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
                    $this->merges[] = "{$col}{$start}:{$col}{$end}";
                }
            }
        }

        return collect($rows);
    }

    /** Pisahkan total bayar menjadi Tunai (type cash) dan Transfer (selain cash). */
    private function splitCashTransfer(Sale $sale): array
    {
        $cash = 0.0;
        $transfer = 0.0;

        if ($sale->payments->isNotEmpty()) {
            foreach ($sale->payments as $p) {
                if (($p->paymentMethod?->type) === 'cash') {
                    $cash += (float) $p->amount;
                } else {
                    $transfer += (float) $p->amount;
                }
            }
        } else {
            // Nota lama tanpa rincian sale_payments → pakai metode utama.
            if (($sale->paymentMethod?->type) === 'cash') {
                $cash = (float) $sale->amount_paid;
            } else {
                $transfer = (float) $sale->amount_paid;
            }
        }

        return [$cash, $transfer];
    }

    public function headings(): array
    {
        return [
            'No. Penjualan', 'Toko', 'Metode Bayar', 'Tunai (Rp)', 'Transfer/Non-Tunai (Rp)', 'Kasir',
            'Subtotal Sblm Diskon (Rp)', 'Diskon (Rp)', 'Total Stlh Diskon (Rp)', 'Tanggal',
            'Item', 'SKU / Variant', 'Qty', 'Harga Satuan (Rp)', 'Subtotal Item (Rp)',
        ];
    }

    public function map($row): array
    {
        return array_values($row);
    }

    public function title(): string { return 'Laporan Penjualan'; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();
                $lastRow = max(2, $ws->getHighestRow());
                $lastCol = 'O';

                // Merge kolom level-nota untuk nota dengan >1 item (keterangan tidak kosong).
                foreach ($this->merges as $range) {
                    $ws->mergeCells($range);
                }

                // ── Header (baris 1): bold putih di atas indigo, rata tengah, sedikit lebih tinggi.
                $ws->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3730A3']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $ws->getRowDimension(1)->setRowHeight(30);
                $ws->freezePane('A2');
                $ws->setAutoFilter("A1:{$lastCol}1");

                // ── Border tipis di seluruh tabel + rata tengah vertikal semua baris.
                $ws->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                ]);
                $ws->getStyle("A1:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // ── Rata kanan untuk semua kolom angka/Rupiah, rata tengah untuk Qty & Tanggal.
                $ws->getStyle("D2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $ws->getStyle("G2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $ws->getStyle("N2:O{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $ws->getStyle("M2:M{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $ws->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ── Format Rupiah "Rp 1.234.567" (bukan angka mentah tanpa pemisah ribuan).
                foreach (['D', 'E', 'G', 'H', 'I', 'N', 'O'] as $col) {
                    $ws->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode(self::RP_FORMAT);
                }
                $ws->getStyle("M2:M{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

                // ── Baris nota (kolom A berisi No. Penjualan) ditebalkan agar tabel mudah dipindai.
                foreach ($this->noteStartRows as $row) {
                    $ws->getStyle("A{$row}:J{$row}")->getFont()->setBold(true);
                }
            },
        ];
    }
}
