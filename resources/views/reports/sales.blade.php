@extends('layouts.app')
@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')
@section('breadcrumb', 'Laporan / Penjualan')

@section('content')
    <div class="space-y-4" x-data="salesReportApp()">

        <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
            @if($stores->count())
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Toko</label>
                    <select name="store_id"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Toko</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ request('store_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg self-end">Filter</button>
            <a href="{{ route('reports.sales') }}"
                class="bg-gray-100 text-gray-600 text-sm px-4 py-2 rounded-lg self-end">Reset</a>
        </form>

        {{-- Export buttons --}}
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-gray-500 font-medium">Export:</span>
            <a href="{{ route('exports.sales.pdf', array_merge(request()->query(), ['filename' => 'laporan-penjualan-' . now()->format('Y-m-d_H-i-s') . '.pdf'])) }}" download="laporan-penjualan.pdf"
                class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-2 rounded-lg">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                PDF
            </a>
            <a href="{{ route('exports.sales.excel', array_merge(request()->query(), ['filename' => 'laporan-penjualan-' . now()->format('Y-m-d_H-i-s') . '.xlsx'])) }}"
                class="inline-flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium px-3 py-2 rounded-lg"
                title="Excel (.xlsx) — jika gagal di iOS/Bluefy, gunakan CSV">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Excel
            </a>
            <a href="{{ route('exports.sales.csv', array_merge(request()->query(), ['filename' => 'laporan-penjualan-' . now()->format('Y-m-d_H-i-s') . '.csv'])) }}"
                class="inline-flex items-center gap-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium px-3 py-2 rounded-lg"
                title="CSV — format paling kompatibel untuk Bluefy &amp; iOS">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                CSV
            </a>
            {{-- Info Bluefy --}}
            <span
                class="ml-1 text-xs text-amber-600 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Bluefy iOS: gunakan CSV
            </span>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs text-gray-400">Total Pendapatan</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($totalSales, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs text-gray-400">Total Transaksi</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totalOrders) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No. Penjualan</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Toko</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Metode</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Items</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Total</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-indigo-600">{{ $sale->sale_no }}</td>
                                <td class="px-4 py-3 text-xs text-gray-700">{{ $sale?->store?->name }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $sale->paymentMethodLabel() }}
                                    @if($sale->isSplitPayment())
                                        <span class="ml-1 inline-block text-[10px] font-semibold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">Split</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-gray-700">{{ $sale->items->sum('qty') }}</td>
                                <td class="px-4 py-3 text-right text-xs font-semibold text-gray-800">Rp
                                    {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-xs text-gray-400">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button @click="showDetail({{ $sale->id }})"
                                        class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400">Tidak ada data penjualan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sales->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">{{ $sales->links() }}</div>
            @endif
        </div>

        <template x-teleport="body">
            <div x-show="showModal" style="display: none; z-index: 9999;"
                class="fixed inset-0 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 transition-opacity">
                <div @click.outside="showModal = false" x-show="showModal" x-transition.scale.origin.center
                    class="bg-white w-full max-w-2xl rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <div>
                            <h3 class="font-bold text-gray-800">Detail Transaksi</h3>
                            <p class="text-[10px] text-gray-500 font-mono" x-text="saleNo"></p>
                        </div>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto bg-gray-50/30 custom-scrollbar">
                        <div x-show="loading" class="flex flex-col items-center justify-center py-12">
                            <svg class="animate-spin h-8 w-8 text-indigo-600 mb-3" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <p class="text-xs text-gray-500 font-medium tracking-wide">Memuat detail...</p>
                        </div>

                        <div x-show="!loading && sale" class="p-6 space-y-6">
                            <!-- Info Utama -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-sm">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Toko</p>
                                    <p class="text-sm font-bold text-gray-800" x-text="sale?.store?.name"></p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-sm">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Kasir</p>
                                    <p class="text-sm font-bold text-gray-800" x-text="sale?.creator?.name"></p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-sm">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Metode</p>
                                    <p class="text-sm font-bold text-gray-800" x-text="paymentLabel()"></p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-gray-100 shadow-sm">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Tanggal
                                    </p>
                                    <p class="text-sm font-bold text-gray-800" x-text="formatDate(sale?.created_at)"></p>
                                </div>
                            </div>

                            <!-- Tabel Barang -->
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <table class="w-full text-sm text-left border-collapse">
                                    <thead
                                        class="bg-gray-50/90 backdrop-blur-md sticky top-0 z-10 border-b border-gray-100">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                Produk</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">
                                                Qty</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">
                                                Harga</th>
                                            <th
                                                class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">
                                                Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <template x-for="item in sale?.items" :key="item.id">
                                            <tr class="hover:bg-gray-50/50 transition-colors">
                                                <td class="px-4 py-3">
                                                    <p class="font-bold text-gray-800" x-text="item.variant.product.name">
                                                    </p>
                                                    <p class="text-[10px] text-gray-400 font-mono"
                                                        x-text="item.variant.sku + ' · ' + item.variant.color.name + ' / ' + item.variant.size.name">
                                                    </p>
                                                </td>
                                                <td class="px-4 py-3 text-center font-bold text-gray-600" x-text="item.qty">
                                                </td>
                                                <td class="px-4 py-3 text-right text-gray-600"
                                                    x-text="formatCurrency(item.unit_price)"></td>
                                                <td class="px-4 py-3 text-right font-bold text-indigo-600"
                                                    x-text="formatCurrency(item.subtotal)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ringkasan Biaya -->
                            <div class="flex flex-col md:flex-row gap-6">
                                <div class="flex-1">
                                    <!-- Notes if any -->
                                    <div x-show="sale?.notes"
                                        class="p-4 bg-orange-50/50 border border-orange-100 rounded-2xl">
                                        <p class="text-[10px] font-bold text-orange-600 uppercase tracking-widest mb-1">
                                            Catatan</p>
                                        <p class="text-xs text-orange-800 italic" x-text="sale?.notes"></p>
                                    </div>
                                </div>
                                <div class="w-full md:w-72 space-y-2">
                                    <div class="flex justify-between text-xs text-gray-500 font-medium">
                                        <span>Subtotal</span>
                                        <span class="text-gray-800 font-bold"
                                            x-text="formatCurrency(sale?.subtotal)"></span>
                                    </div>
                                    <div x-show="sale?.discount_amount > 0"
                                        class="flex justify-between text-xs text-red-500 font-medium">
                                        <span>Diskon</span>
                                        <span class="font-bold"
                                            x-text="'- ' + formatCurrency(sale?.discount_amount)"></span>
                                    </div>
                                    <div class="pt-2 border-t border-gray-100 flex justify-between items-center">
                                        <span class="text-sm font-black text-gray-800 uppercase tracking-wider">Total</span>
                                        <span class="text-lg font-black text-indigo-600"
                                            x-text="formatCurrency(sale?.total_amount)"></span>
                                    </div>
                                    <!-- Rincian Pembayaran (split / per metode) -->
                                    <div x-show="paymentRows().length" class="pt-3 mt-1 border-t border-dashed border-gray-200 space-y-1">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Rincian Pembayaran</p>
                                        <template x-for="(row, idx) in paymentRows()" :key="idx">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-500" x-text="row.name"></span>
                                                <span class="font-semibold text-gray-700" x-text="formatCurrency(row.amount)"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="pt-4 space-y-1">
                                        <div
                                            class="flex justify-between text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            <span>Dibayar</span>
                                            <span class="text-gray-600" x-text="formatCurrency(sale?.amount_paid)"></span>
                                        </div>
                                        <div
                                            class="flex justify-between text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            <span>Kembalian</span>
                                            <span class="text-green-600"
                                                x-text="formatCurrency(sale?.change_amount)"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-white border-t border-gray-100 flex justify-end">
                        <button @click="showModal = false"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-xl text-sm font-black transition-all active:scale-95">Tutup</button>
                    </div>
                </div>
            </div>
        </template>

        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 5px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background-color: #e2e8f0;
                border-radius: 20px;
            }
        </style>

        <script>
            function salesReportApp() {
                return {
                    showModal: false,
                    loading: false,
                    sale: null,
                    saleNo: '',
                    formatCurrency(val) {
                        if (!val) return 'Rp 0';
                        return 'Rp ' + Number(val).toLocaleString('id-ID');
                    },
                    paymentRows() {
                        // Gabung pembayaran per metode (split → beberapa baris).
                        const pays = this.sale?.payments || [];
                        if (!pays.length) return [];
                        const map = {};
                        pays.forEach(p => {
                            const name = p.payment_method?.name || '—';
                            map[name] = (map[name] || 0) + Number(p.amount || 0);
                        });
                        return Object.keys(map).map(name => ({ name, amount: map[name] }));
                    },
                    paymentLabel() {
                        const rows = this.paymentRows();
                        if (rows.length) return rows.map(r => r.name).join(' + ');
                        return this.sale?.payment_method?.name || '—';
                    },
                    formatDate(dateStr) {
                        if (!dateStr) return '-';
                        const date = new Date(dateStr);
                        return date.toLocaleString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    },
                    async showDetail(saleId) {
                        this.showModal = true;
                        this.loading = true;
                        this.sale = null;
                        try {
                            let res = await fetch(`/reports/sales/${saleId}/detail`, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                            });
                            if (!res.ok) {
                                alert('Gagal mengambil detail (status: ' + res.status + ').');
                                this.showModal = false;
                                return;
                            }
                            let data = await res.json();
                            if (data.success) {
                                this.sale = data.sale;
                                this.saleNo = data.sale.sale_no;
                            } else {
                                alert('Gagal mengambil detail.');
                                this.showModal = false;
                            }
                        } catch (e) {
                            alert('Terjadi kesalahan koneksi: ' + e.message);
                            this.showModal = false;
                        }
                        this.loading = false;
                    }
                }
            }
        </script>
    </div>
@endsection