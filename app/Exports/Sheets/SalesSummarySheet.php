<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\ScopesSalesQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title as ChartTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Sheet ringkasan + grafik untuk Laporan Penjualan: total hari ini (atau
 * periode filter tanggal yang dipakai saat download), tren penjualan per
 * hari, produk terlaris, dan brand terlaris pada periode tsb.
 */
class SalesSummarySheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents, WithCharts
{
    use ScopesSalesQuery;

    private const RP_FORMAT = '"Rp" #,##0';
    private const SHEET_TITLE = 'Ringkasan & Grafik';

    private string $periodLabel = '';

    private int $kpiHeaderRow = 0;
    private int $kpiValueRow  = 0;

    private int $dailyHeaderRow = 0;
    private int $dailyDataStart = 0;
    private int $dailyDataEnd   = 0;

    private int $productHeaderRow = 0;
    private int $productDataStart = 0;
    private int $productDataEnd   = 0;

    private int $brandHeaderRow = 0;
    private int $brandDataStart = 0;
    private int $brandDataEnd   = 0;

    public function __construct(
        protected ?string $storeId  = null,
        protected ?string $dateFrom = null,
        protected ?string $dateTo   = null,
        protected ?string $metode   = null,
    ) {}

    public function title(): string { return self::SHEET_TITLE; }

    public function array(): array
    {
        // Tanpa filter tanggal → default ke hari ini. Dengan filter → ikut periode itu.
        $from = $this->dateFrom ?: now()->toDateString();
        $to   = $this->dateTo   ?: now()->toDateString();

        $this->periodLabel = $from === $to
            ? 'Hari Ini (' . Carbon::parse($from)->translatedFormat('d F Y') . ')'
            : Carbon::parse($from)->format('d/m/Y') . ' s/d ' . Carbon::parse($to)->format('d/m/Y');

        $salesQuery = $this->scopedSalesQuery($this->storeId, $from, $to, $this->metode);
        $saleIds    = (clone $salesQuery)->pluck('id');

        $totalOrders  = $saleIds->count();
        $totalRevenue = (float) (clone $salesQuery)->sum('total_amount');
        $totalItems   = (int) DB::table('sale_items')->whereIn('sale_id', $saleIds)->sum('qty');
        $avgPerOrder  = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.0;

        $daily = DB::table('sales')
            ->whereIn('id', $saleIds)
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as transaksi, SUM(total_amount) as pendapatan')
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->whereIn('sales.id', $saleIds)
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name as nama, SUM(sale_items.qty) as qty, SUM(sale_items.subtotal) as pendapatan')
            ->orderByDesc('qty')->limit(10)->get();

        $topBrands = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->whereIn('sales.id', $saleIds)
            ->groupBy('brands.id', 'brands.name')
            ->selectRaw("COALESCE(brands.name, 'Tanpa Brand') as nama, SUM(sale_items.qty) as qty, SUM(sale_items.subtotal) as pendapatan")
            ->orderByDesc('qty')->limit(10)->get();

        $rows = [];
        $rows[] = ['RINGKASAN PENJUALAN'];
        $rows[] = ['Periode: ' . $this->periodLabel];
        $rows[] = [null];

        $this->kpiHeaderRow = count($rows) + 1;
        $rows[] = ['Total Pendapatan (Rp)', 'Total Transaksi', 'Total Item Terjual', 'Rata-rata / Transaksi (Rp)'];
        $this->kpiValueRow = count($rows) + 1;
        $rows[] = [$totalRevenue, $totalOrders, $totalItems, $avgPerOrder];
        $rows[] = [null];
        $rows[] = [null];

        $this->dailyHeaderRow = count($rows) + 1;
        $rows[] = ['Tanggal', 'Total Transaksi', 'Total Pendapatan (Rp)'];
        $this->dailyDataStart = count($rows) + 1;
        if ($daily->isEmpty()) {
            $rows[] = ['(Tidak ada penjualan)', 0, 0];
        } else {
            foreach ($daily as $d) {
                $rows[] = [Carbon::parse($d->tanggal)->format('d/m/Y'), (int) $d->transaksi, (float) $d->pendapatan];
            }
        }
        $this->dailyDataEnd = count($rows);
        $rows[] = [null];
        $rows[] = [null];

        $this->productHeaderRow = count($rows) + 1;
        $rows[] = ['Produk', 'Qty Terjual', 'Pendapatan (Rp)'];
        $this->productDataStart = count($rows) + 1;
        if ($topProducts->isEmpty()) {
            $rows[] = ['(Tidak ada penjualan)', 0, 0];
        } else {
            foreach ($topProducts as $p) {
                $rows[] = [$p->nama, (int) $p->qty, (float) $p->pendapatan];
            }
        }
        $this->productDataEnd = count($rows);
        $rows[] = [null];
        $rows[] = [null];

        $this->brandHeaderRow = count($rows) + 1;
        $rows[] = ['Brand', 'Qty Terjual', 'Pendapatan (Rp)'];
        $this->brandDataStart = count($rows) + 1;
        if ($topBrands->isEmpty()) {
            $rows[] = ['(Tidak ada penjualan)', 0, 0];
        } else {
            foreach ($topBrands as $b) {
                $rows[] = [$b->nama, (int) $b->qty, (float) $b->pendapatan];
            }
        }
        $this->brandDataEnd = count($rows);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();

                // ── Judul & periode.
                $ws->mergeCells('A1:D1');
                $ws->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '3730A3']],
                ]);
                $ws->mergeCells('A2:D2');
                $ws->getStyle('A2')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF666666'));

                // ── Blok KPI (baris header + nilai).
                $this->styleTableHeader($ws, "A{$this->kpiHeaderRow}:D{$this->kpiHeaderRow}");
                $ws->getStyle("A{$this->kpiValueRow}:D{$this->kpiValueRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                ]);
                $ws->getStyle("A{$this->kpiValueRow}")->getNumberFormat()->setFormatCode(self::RP_FORMAT);
                $ws->getStyle("B{$this->kpiValueRow}")->getNumberFormat()->setFormatCode('#,##0');
                $ws->getStyle("C{$this->kpiValueRow}")->getNumberFormat()->setFormatCode('#,##0');
                $ws->getStyle("D{$this->kpiValueRow}")->getNumberFormat()->setFormatCode(self::RP_FORMAT);
                $ws->getRowDimension($this->kpiValueRow)->setRowHeight(24);

                // ── 3 tabel data (Tanggal / Produk / Brand), sama gayanya.
                $this->styleDataTable($ws, $this->dailyHeaderRow, $this->dailyDataStart, $this->dailyDataEnd, 'C');
                $this->styleDataTable($ws, $this->productHeaderRow, $this->productDataStart, $this->productDataEnd, 'C');
                $this->styleDataTable($ws, $this->brandHeaderRow, $this->brandDataStart, $this->brandDataEnd, 'C');

                $ws->getColumnDimension('A')->setWidth(28);
                $ws->getColumnDimension('B')->setWidth(16);
                $ws->getColumnDimension('C')->setWidth(20);
                $ws->getColumnDimension('D')->setWidth(20);
            },
        ];
    }

    private function styleTableHeader($ws, string $range): void
    {
        $ws->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3730A3']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ]);
    }

    private function styleDataTable($ws, int $headerRow, int $dataStart, int $dataEnd, string $rpCol): void
    {
        $this->styleTableHeader($ws, "A{$headerRow}:C{$headerRow}");

        $range = "A{$dataStart}:C{$dataEnd}";
        $ws->getStyle($range)->applyFromArray([
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getStyle("A{$dataStart}:A{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $ws->getStyle("{$rpCol}{$dataStart}:{$rpCol}{$dataEnd}")->getNumberFormat()->setFormatCode(self::RP_FORMAT);
    }

    public function charts()
    {
        return [
            $this->buildChart(
                'chart_daily', 'Pendapatan per Hari', DataSeries::DIRECTION_COL,
                $this->dailyDataStart, $this->dailyDataEnd, 'C',
                'F2', 'N19'
            ),
            $this->buildChart(
                'chart_products', 'Produk Terlaris (Qty Terjual)', DataSeries::DIRECTION_BAR,
                $this->productDataStart, $this->productDataEnd, 'B',
                'F21', 'N38'
            ),
            $this->buildChart(
                'chart_brands', 'Brand Terlaris (Qty Terjual)', DataSeries::DIRECTION_BAR,
                $this->brandDataStart, $this->brandDataEnd, 'B',
                'F40', 'N57'
            ),
        ];
    }

    /** Bar chart: kategori dari kolom A, nilai dari kolom $valCol, pada rentang baris data yang sudah dicatat di array(). */
    private function buildChart(
        string $id,
        string $titleText,
        string $direction,
        int $dataStart,
        int $dataEnd,
        string $valCol,
        string $topLeft,
        string $bottomRight
    ): Chart {
        $count = max(1, $dataEnd - $dataStart + 1);
        $sheet = self::SHEET_TITLE;

        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheet}'!\$A\${$dataStart}:\$A\${$dataEnd}", null, $count)];
        $values     = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheet}'!\${$valCol}\${$dataStart}:\${$valCol}\${$dataEnd}", null, $count)];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            [],
            $categories,
            $values,
            $direction
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend   = new Legend(Legend::POSITION_RIGHT, null, false);
        $title    = new ChartTitle($titleText);

        $chart = new Chart($id, $title, $legend, $plotArea);
        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);

        return $chart;
    }
}
