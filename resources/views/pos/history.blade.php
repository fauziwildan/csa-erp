@extends('layouts.app')
@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi POS')
@section('breadcrumb', 'POS / Riwayat')

@section('content')
    <div class="space-y-4" x-data="posHistoryApp()">

        <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
            @if($stores->count() > 0)
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Toko</label>
                    <select name="store_id" onchange="this.form.submit()"
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
                <label class="block text-xs font-medium text-gray-500 mb-1">Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            {{-- Searchable & Scannable SKU Filter --}}
            <div x-data="{ 
                open: false, 
                search: '{{ request('sku') }}', 
                variants: @js($variants),
                selectedIndex: -1,
                get filteredVariants() {
                    if (this.search === '') return this.variants.slice(0, 50);
                    return this.variants.filter(v => 
                        v.sku.toLowerCase().includes(this.search.toLowerCase()) || 
                        v.product.name.toLowerCase().includes(this.search.toLowerCase())
                    ).slice(0, 50);
                },
                select(v) {
                    this.search = v.sku;
                    this.open = false;
                    $nextTick(() => {
                        $el.closest('form').submit();
                    });
                },
                onEnter() {
                    const filtered = this.filteredVariants;
                    if (this.selectedIndex >= 0 && this.selectedIndex < filtered.length) {
                        this.select(filtered[this.selectedIndex]);
                    } else if (filtered.length === 1) {
                        this.select(filtered[0]);
                    } else {
                        // Fallback: submit form with current search text
                        this.open = false;
                        $nextTick(() => {
                            $el.closest('form').submit();
                        });
                    }
                }
            }" class="relative w-full md:w-64" @click.outside="open = false">
                <label class="block text-xs font-medium text-gray-500 mb-1">Cari / Scan SKU</label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="sku" 
                        x-model="search" 
                        @focus="open = true"
                        @input="open = true; selectedIndex = -1"
                        @keydown.arrow-down.prevent="open = true; selectedIndex = (selectedIndex + 1) % filteredVariants.length"
                        @keydown.arrow-up.prevent="open = true; selectedIndex = (selectedIndex - 1 + filteredVariants.length) % filteredVariants.length"
                        @keydown.enter.prevent="onEnter()"
                        placeholder="Ketik atau Scan Barcode..."
                        autocomplete="off"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                {{-- Dropdown Result --}}
                <div 
                    x-show="open && filteredVariants.length > 0" 
                    class="absolute z-[60] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-xl max-h-60 overflow-y-auto custom-scrollbar"
                    style="display: none;"
                >
                    <template x-for="(v, index) in filteredVariants" :key="v.id">
                        <div 
                            @click="select(v)"
                            :class="{ 'bg-indigo-50 text-indigo-700': selectedIndex === index, 'hover:bg-gray-50': selectedIndex !== index }"
                            class="px-4 py-2.5 cursor-pointer border-b border-gray-50 last:border-0 transition-colors"
                        >
                            <div class="font-bold text-sm" x-text="v.sku"></div>
                            <div class="text-[11px] text-gray-500 truncate" x-text="v.product.name"></div>
                        </div>
                    </template>
                </div>
            </div>
            <button type="submit" class="bg-gray-800 text-white text-sm px-4 py-2 rounded-lg self-end">Filter</button>
            <a href="{{ route('pos.history') }}"
                class="bg-gray-100 text-gray-600 text-sm px-4 py-2 rounded-lg self-end">Reset</a>

            @php
                $totalAmount = $sales->sum('total_amount');
                $totalItems = $sales->sum(fn($s) => $s->items->sum('qty'));
            @endphp
            <div class="ml-auto self-end text-sm text-gray-600">
                <span class="font-semibold text-gray-900">{{ $sales->total() }}</span> transaksi ·
                Total: <span class="font-semibold text-indigo-700">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
            </div>
        </form>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No. Transaksi</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Waktu</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Toko</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Kasir</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Pembayaran</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Item</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Total</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-indigo-600">{{ $sale->sale_no }}</td>
                                <td class="px-4 py-3 text-xs text-gray-400">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-xs text-gray-700">{{ $sale?->store?->name }}</td>
                                <td class="px-4 py-3 text-xs text-gray-700">{{ $sale->creator?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    {{ $sale->paymentMethodLabel() }}
                                    @if($sale->isSplitPayment())
                                        <span class="ml-1 inline-block text-[10px] font-semibold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">Split</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-gray-700">{{ $sale->items->sum('qty') }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">
                                    Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                    @if($sale->discount_amount > 0)
                                        <span class="block text-xs font-normal text-red-500">Diskon: Rp
                                            {{ number_format($sale->discount_amount, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button @click="openReceipt({{ $sale->id }})"
                                        class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        Cetak
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-gray-400">Tidak ada transaksi</td>
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
        <div x-show="showReceiptModal" style="display: none; z-index: 999999;" class="fixed inset-0 flex items-center justify-center bg-gray-900/70 backdrop-blur-md p-4 transition-opacity">
            <div @click.outside="showReceiptModal = false" x-show="showReceiptModal" x-transition.scale.origin.bottom class="bg-white w-full max-w-md rounded-[2rem] overflow-hidden shadow-2xl flex flex-col max-h-[90vh] border border-white/20">
                <!-- Header Modal -->
                <div class="bg-indigo-600 px-6 py-4 flex flex-col gap-3 shrink-0 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                    <div class="absolute -left-4 -bottom-4 w-24 h-24 bg-black/10 rounded-full blur-xl"></div>
                    
                    <div class="flex justify-between items-center relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <h3 class="text-white font-black text-xl tracking-wide">Cetak Struk</h3>
                                <p class="text-indigo-100 text-xs font-medium">Preview transaksi terpilih</p>
                            </div>
                        </div>
                        <button @click="showReceiptModal = false" class="text-white/70 hover:text-white bg-black/10 hover:bg-black/20 p-2 rounded-full transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    @if(auth()->user()->hasRole('superadmin'))
                    <!-- Superadmin Printer Selector -->
                    <div class="relative z-10 bg-black/10 border border-white/10 rounded-xl p-2 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-white/80 uppercase tracking-widest ml-1">Setting Printer:</span>
                        <select x-model="printMethod" @change="localStorage.setItem('pos_print_method', $event.target.value)" 
                                class="bg-white border-none rounded-lg py-1 px-3 text-[10px] font-black text-indigo-600 focus:ring-0 cursor-pointer">
                            <option value="pc_usb">USB (BROWSER)</option>
                            <option value="pc_bluetooth">BLUETOOTH (PC)</option>
                            <option value="ios_bluefy">BLUETOOTH (IOS)</option>
                            <option value="android_bluetooth">BLUETOOTH (ANDROID)</option>
                            <option value="android_flutter">ANDROID APP</option>
                        </select>
                    </div>
                    @endif
                </div>
                
                <!-- Area Preview Struk -->
                <div class="flex-1 bg-gray-50 overflow-y-auto p-6 flex justify-center custom-scrollbar relative shadow-inner">
                    <!-- Decorative background elements -->
                    <div class="absolute inset-0 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:16px_16px] opacity-50"></div>
                    
                    <div id="print-area" x-html="receiptHtmlHtml" class="bg-white shadow-xl p-0 relative z-10 transition-transform duration-300 min-w-[72mm] flex-shrink-0" style="zoom: 0.85; min-height: 100px;"></div>
                </div>

                <!-- Area Aksi / Tombol -->
                <div class="p-5 bg-white border-t border-gray-100 flex flex-col gap-3 shrink-0 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.05)]">
                    <button @click="executePrint()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl text-lg shadow-[0_8px_20px_-6px_rgba(79,70,229,0.5)] hover:shadow-[0_12px_25px_-8px_rgba(79,70,229,0.7)] transition-all flex items-center justify-center gap-3 group">
                        <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        CETAK STRUK SEKARANG
                    </button>
                    <button @click="showReceiptModal = false" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3.5 rounded-2xl transition-colors flex items-center justify-center gap-2">
                        Tutup Preview
                    </button>
                </div>
            </div>
        </div>
    </template>

    <style>
        /* Mempercantik Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }

        @media print {
            header, nav, aside { display: none !important; }
            .space-y-4 { display: none !important; }
            .fixed.inset-0 { position: static !important; background: transparent !important; padding: 0 !important; }
            .bg-white.max-w-md { max-width: 100% !important; box-shadow: none !important; height: auto !important; }
            .bg-indigo-600, .p-5.bg-white.border-t { display: none !important; }
            @page { margin: 0; }
        }
    </style>

@push('scripts')
<script>
function posHistoryApp() {
    return {
        showReceiptModal: false,
        receiptHtmlHtml: '',
        currentSaleData: null,
        printMethod: localStorage.getItem('pos_print_method') || 'pc_usb',
        cachedBluetoothDevice: null,
        cachedCharacteristic: null,

        async openReceipt(saleId) {
            try {
                let res = await fetch(`/reports/sales/${saleId}/detail`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!res.ok) {
                    alert('Gagal mengambil data struk (status: ' + res.status + ').');
                    return;
                }
                let data = await res.json();
                if (data.success) {
                    this.currentSaleData = data.sale;
                    // Jika server berhasil render HTML (ada library QR/barcode), tampilkan
                    // Jika tidak (hosting tanpa library), buat preview sederhana dari data
                    if (data.html) {
                        this.receiptHtmlHtml = data.html;
                    } else {
                        this.receiptHtmlHtml = this.buildSimpleReceiptHtml(data.sale);
                    }
                    this.showReceiptModal = true;
                } else {
                    alert('Gagal mengambil data struk.');
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan: ' + err.message);
            }
        },

        buildSimpleReceiptHtml(sale) {
            const fmt = (n) => parseInt(n || 0).toLocaleString('id-ID');
            let rows = sale.items.map(item => {
                const name = item.variant?.product?.name || '-';
                const sku  = item.variant?.sku || '';
                const color = item.variant?.color?.name || '';
                const size = item.variant?.size?.name || '';
                return `<div style="margin-bottom:8px">
                    <div style="font-weight:bold;font-size:13px">${name}</div>
                    <div style="font-size:11px;color:#444;margin-bottom:2px;">${sku} · ${color} / ${size}</div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="font-size:12px">@ Rp ${fmt(item.unit_price)}</span>
                        <span style="width:40px;text-align:center;">x${item.qty}</span>
                        <span style="font-weight:bold;width:85px;text-align:right;">Rp ${fmt(item.subtotal)}</span>
                    </div>
                </div>`;
            }).join('');
            
            let pMethod = sale.payment_method || sale.paymentMethod;
            let pMethodName = pMethod ? pMethod.name.toUpperCase() : '-';
            let priceLabel = sale.price_method === 'custom' ? 'Ecer (Custom)' : (sale.price_method === 'grosir' ? 'Grosir' : 'Ecer');
            let statusLabel = sale.payment_status.toUpperCase();
            let statusColor = (sale.payment_status === 'lunas') ? '#16a34a' : '#dc2626';

            let customerHtml = '';
            if (sale.customer_name) {
                customerHtml += `<div style="border-top:1px dashed #000;margin:6px 0"></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Nama Pelanggan:</span><span style="font-weight:bold">${sale.customer_name}</span></div>`;
                if (sale.customer_phone) {
                    customerHtml += `<div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>No telp Pelanggan:</span><span>${sale.customer_phone}</span></div>`;
                }
            }

            let dueDateHtml = '';
            if (sale.due_date) {
                let dDate = new Date(sale.due_date);
                let formattedDueDate = String(dDate.getDate()).padStart(2, '0') + "/" + 
                    String(dDate.getMonth() + 1).padStart(2, '0') + "/" + 
                    dDate.getFullYear();
                dueDateHtml = `<div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Jatuh Tempo:</span><span>${formattedDueDate}</span></div>`;
            }

            let d = new Date(sale.created_at);
            let formattedDate = d.getFullYear() + "-" + 
                String(d.getMonth() + 1).padStart(2, '0') + "-" + 
                String(d.getDate()).padStart(2, '0') + " " + 
                String(d.getHours()).padStart(2, '0') + ":" + 
                String(d.getMinutes()).padStart(2, '0');

            let bankHtml = '';
            if (sale.store?.bank_name || sale.store?.bank_account) {
                bankHtml += `<div style="text-align:center;margin-bottom:4px">PEMBAYARAN TRANSFER:</div>`;
                if (sale.store?.bank_name) bankHtml += `<div style="text-align:center;margin-bottom:2px">Bank: ${sale.store.bank_name}</div>`;
                if (sale.store?.bank_account) bankHtml += `<div style="text-align:center;font-weight:bold;margin-bottom:2px">${sale.store.bank_account}</div>`;
                if (sale.store?.bank_account_name) bankHtml += `<div style="text-align:center;margin-bottom:2px">A.N. ${sale.store.bank_account_name}</div>`;
                bankHtml += `<div style="border-top:1px dashed #000;margin:10px 0"></div>`;
            }

            let phoneHtml = '';
            if (sale.store?.phone) {
                phoneHtml += `<div style="text-align:center;margin-top:10px">No Telp</div>`;
                if (Array.isArray(sale.store.phone)) {
                    sale.store.phone.forEach(p => { if (p) phoneHtml += `<div style="text-align:center">${p}</div>`; });
                } else if (typeof sale.store.phone === 'string') {
                    try {
                        let phones = JSON.parse(sale.store.phone);
                        if (Array.isArray(phones)) {
                            phones.forEach(p => { if (p) phoneHtml += `<div style="text-align:center">${p}</div>`; });
                        } else {
                            phoneHtml += `<div style="text-align:center">${sale.store.phone}</div>`;
                        }
                    } catch(e) {
                        phoneHtml += `<div style="text-align:center">${sale.store.phone}</div>`;
                    }
                }
                phoneHtml += `<div style="margin-bottom:10px"></div>`;
            }

            let qrBarcodeHtml = `
                <div style="text-align:center;margin:10px 0;padding:10px;border:1px dashed #ccc;color:#666;font-size:10px;">
                    [ QR CODE ]<br><br>
                    [ BARCODE: ${sale.sale_no} ]<br>
                    ${sale.sale_no}
                </div>
            `;

            return `<div style="font-family:'Courier New', monospace;font-size:13px;width:72mm;margin:0 auto;padding:12px;color:#000;background:#fff;">
                <div style="text-align:center;font-weight:bold;font-size:16px;margin-bottom:2px;text-transform:uppercase;">${sale.store?.name || ''}</div>
                <div style="text-align:center;font-size:9px;color:#666;margin-bottom:4px">SevenKey erp</div>
                ${sale.store?.address ? `<div style="text-align:center;font-size:11px;color:#444">${sale.store.address}</div>` : ''}
                <div style="border-top:1px dashed #000;margin:10px 0"></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>No.</span><span style="font-weight:bold">${sale.sale_no}</span></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Tgl</span><span>${formattedDate}</span></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Kasir</span><span>${sale.creator?.name||'-'}</span></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Metode</span><span>${pMethodName}</span></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Harga</span><span>${priceLabel}</span></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;color:${statusColor};font-weight:bold"><span>Status</span><span>${statusLabel}</span></div>
                ${customerHtml}
                ${dueDateHtml}
                <div style="border-top:1px dashed #000;margin:10px 0"></div>
                <div style="margin-bottom:6px">
                    <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:12px;text-transform:uppercase;">
                        <span style="flex:1;padding-right:8px;">ITEM</span>
                        <span style="width:85px;text-align:right;">TOTAL</span>
                    </div>
                </div>
                <div style="border-top:1px solid #000;margin:10px 0"></div>
                ${rows}
                <div style="border-top:1px solid #000;margin:10px 0"></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Subtotal</span><span>Rp ${fmt(sale.subtotal)}</span></div>
                ${sale.discount_amount > 0 ? `<div style="display:flex;justify-content:space-between;margin-bottom:4px"><span>Diskon</span><span>-Rp ${fmt(sale.discount_amount)}</span></div>` : ''}
                <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:15px;margin-top:8px"><span>TOTAL</span><span>Rp ${fmt(sale.total_amount)}</span></div>
                ${(() => {
                    const pays = sale.payments || [];
                    const map = {};
                    pays.forEach(p => { const n = (p.payment_method && p.payment_method.name) || '—'; map[n] = (map[n]||0) + Number(p.amount||0); });
                    const keys = Object.keys(map);
                    if (keys.length > 1) {
                        return `<div style="display:flex;justify-content:space-between;margin-top:8px;font-weight:bold"><span>PEMBAYARAN</span><span></span></div>` +
                            keys.map(n => `<div style="display:flex;justify-content:space-between"><span>&middot; ${n}</span><span>Rp ${fmt(map[n])}</span></div>`).join('');
                    }
                    const label = sale.payment_status === 'lunas' ? (keys[0] || 'Tunai') : 'Uang Muka/DP';
                    return `<div style="display:flex;justify-content:space-between;margin-top:8px"><span>Bayar (${label})</span><span>Rp ${fmt(sale.amount_paid)}</span></div>`;
                })()}
                ${sale.payment_status !== 'lunas' ? `<div style="display:flex;justify-content:space-between;font-weight:bold;color:#dc2626;margin-bottom:4px"><span>Sisa Hutang</span><span>Rp ${fmt(Math.max(0, sale.total_amount - sale.amount_paid))}</span></div>` : ''}
                ${sale.change_amount > 0 ? `<div style="display:flex;justify-content:space-between;font-weight:bold;margin-bottom:4px"><span>Kembalian</span><span>Rp ${fmt(sale.change_amount)}</span></div>` : ''}
                <div style="border-top:1px dashed #000;margin:10px 0"></div>
                ${bankHtml}
                ${qrBarcodeHtml}
                ${phoneHtml}
                <div style="text-align:center;font-size:12px;font-weight:bold;margin-top:16px">TERIMA KASIH TELAH BERBELANJA</div>
                <div style="text-align:center;font-size:10px;margin-top:4px">Silahkan bawa struk ini untuk retur barang</div>
                <div style="height:2.5cm"></div>
            </div>`;
        },

        async executePrint() {
            if (this.printMethod === 'pc_usb') {
                let printFrame = document.createElement('iframe');
                printFrame.style.display = 'none';
                document.body.appendChild(printFrame);
                printFrame.contentDocument.write('<html><head><style>@page { margin: 0; } body { margin: 0; font-family: monospace; }</style></head><body>' + this.receiptHtmlHtml + '</body></html>');
                printFrame.contentDocument.close();
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
                setTimeout(() => document.body.removeChild(printFrame), 2000);
            } 
            else if (this.printMethod === 'android_flutter') {
                let sale = this.currentSaleData;
                
                let d = new Date(sale.created_at);
                let formattedDate = d.getFullYear() + "-" + 
                    String(d.getMonth() + 1).padStart(2, '0') + "-" + 
                    String(d.getDate()).padStart(2, '0') + " " + 
                    String(d.getHours()).padStart(2, '0') + ":" + 
                    String(d.getMinutes()).padStart(2, '0');

                let dataStruk = {
                    store_name: sale.store.name,
                    store_address: sale.store.address || '',
                    receipt_no: sale.sale_no,
                    date: formattedDate,
                    cashier: sale.creator ? sale.creator.name.substring(0, 15) : '-',
                    items: sale.items.map(item => ({
                        name: String(item.variant.product.name).substring(0, 30),
                        qty: item.qty,
                        price: parseInt(item.unit_price).toLocaleString('id-ID'),
                        total: parseInt(item.subtotal).toLocaleString('id-ID')
                    })),
                    subtotal: parseInt(sale.subtotal).toLocaleString('id-ID'),
                    grand_total: parseInt(sale.total_amount).toLocaleString('id-ID'),
                    paid: parseInt(sale.amount_paid).toLocaleString('id-ID'),
                    change: parseInt(sale.change_amount).toLocaleString('id-ID'),
                    bank_name: sale.store.bank_name || '',
                    bank_account: sale.store.bank_account || '',
                    bank_account_name: sale.store.bank_account_name || '',
                    qr_code: sale.sale_no,
                    barcode: sale.sale_no
                };

                if (window.PrintChannel) {
                    window.PrintChannel.postMessage(JSON.stringify(dataStruk));
                } else {
                    alert("Aplikasi Native Flutter tidak terdeteksi!");
                }
            } 
            else {
                try {
                    if (!this.cachedCharacteristic) {
                        this.cachedBluetoothDevice = await navigator.bluetooth.requestDevice({
                            acceptAllDevices: true,
                            optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb']
                        });
                        
                        this.cachedBluetoothDevice.addEventListener('gattserverdisconnected', () => {
                            this.cachedCharacteristic = null;
                        });

                        const server = await this.cachedBluetoothDevice.gatt.connect();
                        const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
                        this.cachedCharacteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');
                    }

                    const alignLR = (left, right, max = 48) => {
                        let spaces = max - left.toString().length - right.toString().length;
                        return left + " ".repeat(spaces > 0 ? spaces : 1) + right + "\n";
                    };
                    const alignC = (text, max = 48) => {
                        let spaces = Math.floor((max - text.length) / 2);
                        return " ".repeat(spaces > 0 ? spaces : 0) + text + "\n";
                    };

                    let sale = this.currentSaleData;
                    
                    let d = new Date(sale.created_at);
                    let formattedDate = d.getFullYear() + "-" + 
                        String(d.getMonth() + 1).padStart(2, '0') + "-" + 
                        String(d.getDate()).padStart(2, '0') + " " + 
                        String(d.getHours()).padStart(2, '0') + ":" + 
                        String(d.getMinutes()).padStart(2, '0');

                    let text = "\n"; 
                    
                    text += "\x1B\x61\x01"; // Align Center
                    text += "\x1D\x21\x11\x1B\x45\x01"; // Double Height/Width + Bold
                    text += sale.store.name.toUpperCase() + "\n";
                    text += "\x1D\x21\x00\x1B\x45\x00"; // Reset Size + Normal
                    text += "SevenKey erp\n";
                    if (sale.store.address) {
                        text += sale.store.address + "\n";
                    }
                    text += "\x1B\x61\x00"; // Align Left
                    
                    text += "------------------------------------------------\n";
                    text += alignLR("No:", sale.sale_no);
                    text += alignLR("Tgl:", formattedDate);
                    text += alignLR("Kasir:", sale.creator ? sale.creator.name.substring(0, 15) : '-');

                    let pMethod = sale.payment_method || sale.paymentMethod;
                    text += alignLR("Metode:", pMethod ? pMethod.name.toUpperCase() : '-');
                    
                    let priceLabel = sale.price_method === 'custom' ? 'Ecer (Custom)' : (sale.price_method === 'grosir' ? 'Grosir' : 'Ecer');
                    text += alignLR("Harga:", priceLabel);
                    
                    let statusLabel = sale.payment_status.toUpperCase();
                    text += alignLR("Status:", statusLabel);

                    if (sale.customer_name) {
                        text += "------------------------------------------------\n";
                        text += alignLR("Nama Pelanggan:", sale.customer_name);
                        if (sale.customer_phone) {
                            text += alignLR("No telp Pelanggan:", sale.customer_phone);
                        }
                    }

                    if (sale.due_date) {
                        let dDate = new Date(sale.due_date);
                        let formattedDueDate = String(dDate.getDate()).padStart(2, '0') + "/" + 
                            String(dDate.getMonth() + 1).padStart(2, '0') + "/" + 
                            dDate.getFullYear();
                        text += alignLR("Jatuh Tempo:", formattedDueDate);
                    }

                    text += "------------------------------------------------\n";
                    text += alignLR("ITEM", "TOTAL");
                    text += "------------------------------------------------\n";
                    
                    sale.items.forEach(item => {
                        text += String(item.variant.product.name).substring(0, 48) + "\n";
                        let skuText = item.variant.sku + " · " + (item.variant.color ? item.variant.color.name : '') + " / " + (item.variant.size ? item.variant.size.name : '');
                        text += String(skuText).substring(0, 48) + "\n";
                        
                        let c1 = "@ Rp " + parseInt(item.unit_price).toLocaleString('id-ID');
                        let c2 = "x" + item.qty;
                        let leftSide = c1.padEnd(20, ' ') + c2;
                        let rightSide = "Rp " + parseInt(item.subtotal).toLocaleString('id-ID');
                        text += alignLR(leftSide, rightSide);
                    });
                    
                    text += "------------------------------------------------\n";
                    text += alignLR("Subtotal", "Rp " + parseInt(sale.subtotal).toLocaleString('id-ID'));
                    if (sale.discount_amount > 0) {
                        text += alignLR("Diskon", "-Rp " + parseInt(sale.discount_amount).toLocaleString('id-ID'));
                    }
                    text += alignLR("TOTAL", "Rp " + parseInt(sale.total_amount).toLocaleString('id-ID'));
                    
                    const _pays = sale.payments || [];
                    const _map = {};
                    _pays.forEach(p => { const n = (p.payment_method && p.payment_method.name) || '-'; _map[n] = (_map[n]||0) + Number(p.amount||0); });
                    const _keys = Object.keys(_map);
                    if (_keys.length > 1) {
                        text += "PEMBAYARAN:\n";
                        _keys.forEach(n => { text += alignLR("  " + n, "Rp " + parseInt(_map[n]).toLocaleString('id-ID')); });
                    } else {
                        let bayarLabel = (sale.payment_status === 'lunas') ? ('Bayar (' + (_keys[0] || 'Tunai') + ')') : 'Bayar (DP)';
                        text += alignLR(bayarLabel, "Rp " + parseInt(sale.amount_paid).toLocaleString('id-ID'));
                    }
                    
                    if (sale.payment_status !== 'lunas') {
                        let sisa = Math.max(0, sale.total_amount - sale.amount_paid);
                        text += alignLR("Sisa Hutang", "Rp " + parseInt(sisa).toLocaleString('id-ID'));
                    }

                    if (sale.change_amount > 0) {
                        text += alignLR("Kembalian", "Rp " + parseInt(sale.change_amount).toLocaleString('id-ID'));
                    }
                    text += "------------------------------------------------\n";
                    
                    if (sale.store.bank_name || sale.store.bank_account) {
                        text += alignC("PEMBAYARAN TRANSFER:");
                        if (sale.store.bank_name) text += alignC("Bank: " + sale.store.bank_name);
                        if (sale.store.bank_account) text += alignC(sale.store.bank_account);
                        if (sale.store.bank_account_name) text += alignC("A.N. " + sale.store.bank_account_name);
                        text += "------------------------------------------------\n";
                    }

                    if (sale.store.phone) {
                        text += alignC("No Telp");
                        if (Array.isArray(sale.store.phone)) {
                            sale.store.phone.forEach(p => { if (p) text += alignC(p); });
                        } else if (typeof sale.store.phone === 'string') {
                            try {
                                let phones = JSON.parse(sale.store.phone);
                                if (Array.isArray(phones)) {
                                    phones.forEach(p => { if (p) text += alignC(p); });
                                } else {
                                    text += alignC(sale.store.phone);
                                }
                            } catch(e) {
                                text += alignC(sale.store.phone);
                            }
                        }
                        text += "\n";
                    }

                    const encoder = new TextEncoder();
                    const init = new Uint8Array([0x1B, 0x40]);
                    const contentBytes = encoder.encode(text);
                    
                    let qrData = sale.sale_no;
                    let qrBytes = encoder.encode(qrData);
                    let pL = (qrBytes.length + 3) % 256;
                    let pH = Math.floor((qrBytes.length + 3) / 256);
                    let qrCmds = new Uint8Array([
                        0x1B, 0x61, 0x01,
                        0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00,
                        0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, 0x06,
                        0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, 0x31,
                        0x1D, 0x28, 0x6B, pL, pH, 0x31, 0x50, 0x30, ...qrBytes,
                        0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x51, 0x30,
                        0x0A, 0x0A
                    ]);

                    let barcodeBytes = encoder.encode("{B" + sale.sale_no);
                    let textBelowBarcodeBytes = encoder.encode(sale.sale_no + "\n");
                    let barcodeCmds = new Uint8Array([
                        0x1B, 0x61, 0x01,
                        0x1D, 0x68, 60,
                        0x1D, 0x77, 2,
                        0x1D, 0x48, 0,
                        0x1D, 0x6B, 73, barcodeBytes.length, ...barcodeBytes,
                        0x0A,
                        ...textBelowBarcodeBytes,
                        0x0A, 0x0A
                    ]);

                    const thanksBytes = encoder.encode(alignC("TERIMA KASIH TELAH BERBELANJA") + alignC("Silahkan bawa struk ini untuk retur barang", 48));
                    const feed = new Uint8Array([0x1B, 0x64, 0x05]); 

                    const payload = new Uint8Array(init.length + contentBytes.length + qrCmds.length + barcodeCmds.length + thanksBytes.length + feed.length);
                    let offset = 0;
                    payload.set(init, offset); offset += init.length;
                    payload.set(contentBytes, offset); offset += contentBytes.length;
                    payload.set(qrCmds, offset); offset += qrCmds.length;
                    payload.set(barcodeCmds, offset); offset += barcodeCmds.length;
                    payload.set(thanksBytes, offset); offset += thanksBytes.length;
                    payload.set(feed, offset);

                    for (let i = 0; i < payload.length; i += 40) {
                        await this.cachedCharacteristic.writeValue(payload.slice(i, i + 40));
                    }
                } catch (e) { 
                    alert("Koneksi Bluetooth Terputus. Mohon pilih perangkat kembali. " + e.message); 
                    this.cachedCharacteristic = null; 
                }
            }
        },
    };
}
</script>
@endpush
@endsection