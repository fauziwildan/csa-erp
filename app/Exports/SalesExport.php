<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles, WithEvents
{
    /** Kolom level-nota (A..J) yang di-merge untuk nota dengan >1 item. */
    private array $merges = [];

    public function __construct(
        protected ?string $storeId  = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo   = null,
    ) {}

    public function collection(): Collection
    {
        $user = auth()->user();
        $isGlobal = $user && ($user->hasRole('superadmin') || $user->hasRole('owner') || $user->hasRole('finance'));

        $query = Sale::with([
            'store',
            'paymentMethod',
            'payments.paymentMethod',
            'items.variant' => fn($q) => $q->withTrashed(),
            'items.variant.product' => fn($q) => $q->withTrashed(),
            'items.variant.color',
            'items.variant.size',
            'creator',
        ])
            ->when($this->storeId,  fn($q) => $q->where('store_id', $this->storeId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn($q) => $q->whereDate('created_at', '<=', $this->dateTo));

        if (!$isGlobal && $user) {
            $storeIds = $user->stores()->pluck('stores.id')->toArray();
            if (empty($storeIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('store_id', $storeIds);
            }
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        $rows = [];
        $excelRow = 2; // baris 1 = heading

        foreach ($sales as $sale) {
            [$cash, $transfer] = $this->splitCashTransfer($sale);
            $itemCount = max(1, $sale->items->count());
            $start = $excelRow;

            foreach ($sale->items as $idx => $item) {
                $rows[] = [
                    'sale_no'         => $idx === 0 ? $sale->sale_no : '',
                    'store'           => $idx === 0 ? $sale->store->name : '',
                    'payment'         => $idx === 0 ? $sale->paymentMethodLabel() : '',
                    'tunai'           => $idx === 0 ? $cash : '',
                    'transfer'        => $idx === 0 ? $transfer : '',
                    'cashier'         => $idx === 0 ? ($sale->creator?->name ?? '-') : '',
                    'subtotal_before' => $idx === 0 ? $sale->subtotal : '',
                    'discount'        => $idx === 0 ? $sale->discount_amount : '',
                    'total'           => $idx === 0 ? $sale->total_amount : '',
                    'date'            => $idx === 0 ? $sale->created_at->format('d/m/Y H:i') : '',
                    'product'         => $item->variant?->product?->name ?? 'Produk Terhapus',
                    'sku'             => ($item->variant?->sku ?? '-') . " (" . ($item->variant?->color?->name ?? '-') . " / " . ($item->variant?->size?->name ?? '-') . ")",
                    'qty'             => $item->qty,
                    'price'           => $item->unit_price,
                    'subtotal'        => $item->subtotal,
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
            'No. Penjualan', 'Toko', 'Metode Bayar', 'Tunai', 'Transfer/Non-Tunai', 'Kasir',
            'Subtotal (Sblm Diskon)', 'Diskon', 'Total (Stlh Diskon)', 'Tanggal',
            'Item', 'SKU / Variant', 'Qty', 'Harga Satuan', 'Subtotal Item',
        ];
    }

    public function map($row): array
    {
        return array_values($row);
    }

    public function title(): string { return 'Laporan Penjualan'; }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();

                // Merge kolom level-nota untuk nota dengan >1 item (keterangan tidak kosong).
                foreach ($this->merges as $range) {
                    $ws->mergeCells($range);
                }

                // Rata tengah vertikal untuk kolom level-nota (A..J) agar sel merge rapi.
                $ws->getStyle('A:J')->getAlignment()->setVertical('center');
            },
        ];
    }
}
