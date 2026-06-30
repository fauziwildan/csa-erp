@extends('layouts.app')
@section('title', 'Kasir — ' . $store->name)
@section('page-title', 'Terminal Kasir')

@section('content')

    <!-- Menyuntikkan data katalog dengan enkripsi khusus (JSON_HEX) agar tanda kutip di nama produk tidak merusak sistem -->
    <script>
        window.POS_CATALOG_DATA = {!! json_encode($catalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
    </script>

    <!-- WRAPPER UTAMA: Diubah paksa menjadi flex-row (Selalu berdampingan) -->
    <div class="flex flex-row gap-4 xl:gap-6 h-[calc(100vh-6.5rem)] w-full overflow-hidden -mt-2"
        x-data="posApp({{ $session->id }}, {{ $store->id }})" @keydown.window="handleGlobalScan($event)"
        @click.window="handleGlobalClick($event)">


        {{-- ==========================================
        KIRI: KATALOG PRODUK & PENCARIAN
        ========================================== --}}
        <!-- flex-1 min-w-0 memastikan panel ini fleksibel tapi tidak akan pernah hilang -->
        <div class="flex-1 min-w-0 flex flex-col bg-transparent overflow-hidden h-full">
            {{-- 1. Search Bar & Scanner --}}
            <div class="shrink-0 mb-4">
                <div class="relative group">
                    <!-- <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <svg class="w-6 h-6 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div> -->
                    <input type="text" id="searchInput" x-model="search" @keydown.enter.prevent="handleEnterScan()"
                        placeholder="Scan Barcode di sini, atau ketik nama/SKU produk..."
                        class="w-full bg-white border border-gray-200 rounded-2xl pl-14 pr-6 py-4 text-base shadow-sm focus:ring-4 focus:ring-indigo-500/20 text-gray-800 placeholder-gray-400 font-medium transition-all">

                    <div x-show="search.length > 0" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                        <button @click="search = ''; document.getElementById('searchInput').focus()"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-500 rounded-full p-2 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 2. Grid Produk (Scrollable Area) --}}
            <div class="flex-1 overflow-y-auto pb-6 pr-2 custom-scrollbar">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                    <template x-for="p in filteredCatalog" :key="p.id">
                        <!-- KARTU PRODUK -->
                        <div @click="addToCart(p)"
                            class="relative bg-white rounded-2xl transition-all duration-200 flex flex-col h-full overflow-hidden cursor-pointer select-none group shadow-sm hover:shadow-md"
                            :class="[
                                p.stock <= 0 ? 'opacity-50 grayscale' : 'hover:-translate-y-1',
                                getCartQty(p.id) > 0 ? 'ring-4 ring-indigo-500 ring-offset-2' : 'border border-gray-100'
                             ]">

                            <!-- Gambar Produk -->
                            <div class="h-32 xl:h-40 w-full bg-gray-50 relative shrink-0 border-b border-gray-100">
                                <img :src="p.image" :alt="p.name" class="w-full h-full object-cover">

                                <!-- Overlay Jika Stok Habis -->
                                <div x-show="p.stock <= 0"
                                    class="absolute inset-0 bg-white/60 flex items-center justify-center backdrop-blur-[1px]">
                                    <span
                                        class="bg-red-500 text-white text-[10px] font-bold px-3 py-1.5 rounded-full uppercase tracking-wider">Habis</span>
                                </div>

                                <!-- HIGHLIGHT: Badge Jumlah di Keranjang (Menyala jika dibeli) -->
                                <div x-show="getCartQty(p.id) > 0" x-transition.scale
                                    class="absolute top-2 right-2 bg-indigo-600 text-white w-9 h-9 flex items-center justify-center rounded-full font-black text-sm shadow-lg border-2 border-white">
                                    <span x-text="getCartQty(p.id)"></span>
                                </div>

                                <!-- Badge Sisa Stok (Kiri Bawah Gambar) -->
                                <div x-show="p.stock > 0"
                                    class="absolute bottom-2 left-2 bg-black/60 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded-md">
                                    Sisa: <span x-text="p.stock"></span>
                                </div>
                            </div>

                            <!-- Info Produk -->
                            <div class="p-3 flex flex-col flex-1">
                                <h3 class="text-xs xl:text-sm font-bold text-gray-800 leading-snug mb-1 line-clamp-2"
                                    x-text="p.name"></h3>
                                <p class="text-[10px] text-gray-500 font-mono mb-2" x-text="p.sku"></p>

                                <div class="mt-auto pt-2 border-t border-gray-50 flex items-center justify-between">
                                    <p class="text-indigo-600 font-black text-sm xl:text-base" x-text="p.price_formatted">
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Jika tidak ada produk -->
                    <div x-show="filteredCatalog.length === 0"
                        class="col-span-full flex flex-col items-center justify-center py-20 text-gray-400 bg-white rounded-2xl border-2 border-dashed border-gray-200">
                        <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="text-base font-medium text-gray-500">Produk atau SKU tidak ditemukan.</p>
                    </div>

                </div>
            </div>
        </div>

        {{-- ==========================================
        KANAN: TAGIHAN & PEMBAYARAN
        ========================================== --}}
        <!-- Lebar dikunci mutlak (shrink-0) agar tidak bisa merusak katalog di sebelahnya -->
        <div
            class="w-[360px] xl:w-[420px] shrink-0 flex flex-col bg-white rounded-3xl overflow-hidden shadow-xl h-full border border-gray-200">

            {{-- Header Tagihan --}}
            <div class="px-5 py-4 border-b border-gray-200 shrink-0 flex justify-between items-center bg-gray-50">
                <div>
                    <h2 class="text-lg font-black text-gray-800">Detail Pesanan</h2>
                    <p class="text-[10px] font-bold text-gray-500 mt-1 uppercase tracking-wider">KASIR:
                        {{ Auth::user()->name }}</p>
                </div>

                {{-- Kumpulan Tombol Aksi (Sebelah Kanan) --}}
                <div class="flex items-center gap-2">

                    <button x-show="isFocusMode" @click="exitFocusMode()" style="display: none;"
                        class="p-2 text-orange-600 bg-orange-50 hover:bg-orange-500 hover:text-white border border-orange-200 rounded-xl transition-colors shadow-sm"
                        title="Keluar Mode Fokus">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>

                    <a href="{{ route('pos.history') }}" target="_blank"
                        class="p-2 text-teal-600 bg-teal-50 hover:bg-teal-500 hover:text-white border border-teal-200 rounded-xl transition-colors shadow-sm"
                        title="Riwayat Transaksi (Print Ulang Struk)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </a>

                    <div class="relative" x-data="{ openExport: false }">
                        <button @click="openExport = !openExport"
                            class="p-2 text-indigo-600 bg-indigo-50 hover:bg-indigo-500 hover:text-white border border-indigo-200 rounded-xl transition-colors shadow-sm"
                            title="Export Laporan Penjualan">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </button>
                        <div x-show="openExport" @click.outside="openExport = false" style="display: none;"
                            class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden">

                            <div
                                class="px-4 py-2 bg-gray-50 border-b border-gray-100 font-bold text-xs text-gray-500 uppercase">
                                Format PDF</div>
                            <a href="{{ route('pos.report.export', ['period' => 'today', 'format' => 'pdf']) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 border-b border-gray-50 font-medium">Hari
                                Ini</a>
                            <a href="{{ route('pos.report.export', ['period' => 'weekly', 'format' => 'pdf']) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 border-b border-gray-50 font-medium">Minggu
                                Ini</a>
                            <a href="{{ route('pos.report.export', ['period' => 'monthly', 'format' => 'pdf']) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 border-b border-gray-100 font-medium">Bulan
                                Ini</a>

                            <div
                                class="px-4 py-2 bg-gray-50 border-b border-gray-100 font-bold text-xs text-gray-500 uppercase mt-1">
                                Format Excel</div>
                            <a href="{{ route('pos.report.export', ['period' => 'today', 'format' => 'excel']) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 border-b border-gray-50 font-medium">Hari
                                Ini</a>
                            <a href="{{ route('pos.report.export', ['period' => 'weekly', 'format' => 'excel']) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 border-b border-gray-50 font-medium">Minggu
                                Ini</a>
                            <a href="{{ route('pos.report.export', ['period' => 'monthly', 'format' => 'excel']) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 border-b border-gray-100 font-medium">Bulan
                                Ini</a>

                            <div
                                class="px-4 py-2 bg-gray-50 border-b border-gray-100 font-bold text-xs text-gray-500 uppercase mt-1">
                                Format CSV</div>
                            <a href="{{ route('pos.report.export', ['period' => 'today', 'format' => 'csv']) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 border-b border-gray-50 font-medium">Hari
                                Ini</a>
                            <a href="{{ route('pos.report.export', ['period' => 'weekly', 'format' => 'csv']) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 border-b border-gray-50 font-medium">Minggu
                                Ini</a>
                            <a href="{{ route('pos.report.export', ['period' => 'monthly', 'format' => 'csv']) }}"
                                target="_blank"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 font-medium">Bulan Ini</a>
                        </div>
                    </div>

                    <button @click="if(cart.length>0 && confirm('Yakin ingin mereset pesanan?')) cart=[]"
                        class="p-2 text-red-500 bg-red-50 hover:bg-red-500 hover:text-white border border-red-200 rounded-xl transition-colors shadow-sm"
                        title="Kosongkan Keranjang">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>

                </div>
            </div>

            {{-- List Barang (Minimalis) --}}
            <div id="cart-list" class="overflow-y-auto p-2 bg-white custom-scrollbar shrink-0" style="max-height: 180px; min-height: 70px;">

                <div x-show="cart.length === 0" class="h-full flex flex-col items-center justify-center text-center px-6">
                    <div
                        class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-3 border-2 border-dashed border-gray-200">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">Belum ada pesanan</p>
                    <p class="text-[10px] text-gray-400 mt-1">Pilih produk di katalog atau scan barcode.</p>
                </div>

                <template x-for="(item, idx) in cart" :key="item.variant_id">
                    <div
                        class="flex items-center gap-3 p-2 border-b border-gray-100 hover:bg-gray-50 transition-colors group rounded-xl">
                        <!-- Foto Thumbnail di Cart -->
                        <img :src="item.image"
                            class="w-12 h-12 object-cover rounded-lg bg-gray-100 shrink-0 border border-gray-200">

                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-gray-800 truncate" x-text="item.name"></p>
                            <p class="text-[10px] text-indigo-600 font-semibold"
                                x-text="'Rp ' + item.price.toLocaleString('id-ID')"></p>
                        </div>

                        <!-- Ecer Toggle & Qty Control -->
                        <div class="flex flex-col gap-2 items-end">
                            <button @click="toggleEcer(item)" 
                                :class="item.is_ecer ? 'bg-amber-100 text-amber-700 border-amber-300' : 'bg-gray-100 text-gray-500 border-gray-200'"
                                class="text-[9px] font-bold px-2 py-0.5 rounded border transition-colors flex items-center gap-1 shadow-sm">
                                <span x-show="item.is_ecer">🏷️ Ecer</span>
                                <span x-show="!item.is_ecer">Ecer</span>
                            </button>
                            <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-0.5 shrink-0 shadow-sm">
                                <button @click="qtyDown(idx)"
                                    class="w-6 h-6 flex items-center justify-center text-gray-600 hover:text-red-500 hover:bg-red-50 rounded font-bold transition-colors">−</button>
                                <input type="number" x-model.number="item.qty"
                                    @change="if(item.qty < 1) item.qty = 1; if(item.qty > item.maxQty) item.qty = item.maxQty;"
                                    class="w-12 text-center text-xs font-bold text-gray-800 border-none p-0 focus:ring-0 hide-arrows"
                                    min="1" :max="item.maxQty">
                                <button @click="qtyUp(idx)" :disabled="item.qty >= item.maxQty"
                                    class="w-6 h-6 flex items-center justify-center text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded font-bold disabled:opacity-30 transition-colors">+</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Area Pembayaran (Scrollable jika layar pendek) --}}
            <div
                class="bg-gray-50 flex-1 min-h-0 overflow-y-auto rounded-t-3xl shadow-[0_-4px_20px_rgba(0,0,0,0.05)] border-t border-gray-200 p-4 xl:p-5 custom-scrollbar">

                <!-- Rincian -->
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-gray-500 text-xs font-medium">
                        <span>Subtotal (<span class="font-bold text-gray-800" x-text="cart.reduce((s,i)=>s+i.qty,0)"></span>
                            barang)</span>
                        <span class="font-bold text-gray-800" x-text="'Rp ' + subtotal.toLocaleString('id-ID')"></span>
                    </div>
                    <div class="flex justify-between items-center text-gray-500 text-xs font-medium">
                        <span>Diskon (Rp)</span>
                        <input type="text" inputmode="numeric" x-model="discountAmount"
                            class="input-currency w-28 bg-white border border-gray-300 rounded-lg px-2 py-1.5 text-right font-bold text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                    </div>

                    <div class="flex justify-between items-end border-t border-gray-200 pt-3 mt-3">
                        <span class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mb-1">Total
                            Tagihan</span>
                        <span class="text-2xl xl:text-3xl font-black text-indigo-600"
                            x-text="'Rp ' + total.toLocaleString('id-ID')"></span>
                    </div>
                </div>

                <form id="posForm" method="POST" action="{{ route('pos.sale') }}" @submit.prevent="submitSale($event)">
                    @csrf
                    <input type="hidden" name="payment_method_id" :value="primaryMethodId">
                    <input type="hidden" name="amount_paid" :value="effectiveAmountPaid">
                    <input type="hidden" name="discount_amount" :value="rawDiscountAmount">
                    <input type="hidden" name="customer_name" :value="customerName">
                    <input type="hidden" name="customer_phone" :value="customerPhone">
                    <input type="hidden" name="payment_status" :value="paymentStatus">
                    <input type="hidden" name="dp_amount" :value="effectiveAmountPaid">
                    <input type="hidden" name="due_date" :value="dueDate">
                    <template x-if="splitMode">
                        <span>
                            <template x-for="(p, pi) in splitPayments" :key="pi">
                                <span>
                                    <input type="hidden" :name="`payments[${pi}][payment_method_id]`" :value="p.payment_method_id">
                                    <input type="hidden" :name="`payments[${pi}][amount]`" :value="Number((p.amount || '').toString().replace(/\D/g, '')) || 0">
                                </span>
                            </template>
                        </span>
                    </template>
                    <template x-for="(item, i) in cart" :key="i">
                        <span>
                            <input type="hidden" :name="`items[${i}][variant_id]`" :value="item.variant_id">
                            <input type="hidden" :name="`items[${i}][qty]`" :value="item.qty">
                            <input type="hidden" :name="`items[${i}][unit_price]`" :value="item.price">
                            <input type="hidden" :name="`items[${i}][is_ecer]`" :value="item.is_ecer ? 1 : 0">
                        </span>
                    </template>

                    <!-- Info Pelanggan -->
                    <div class="bg-white rounded-2xl p-3 mb-2 border border-gray-200 shadow-sm" x-data="{ cmSuggestions: [], cmShowSug: false }">
                        <div class="relative mb-2">
                            <input type="text" x-model="customerName" id="cmCustomerName"
                                @input.debounce.300ms="
                                    if (customerName.length >= 2) {
                                        fetch('{{ route('pos.customers.autocomplete') }}?q=' + encodeURIComponent(customerName))
                                            .then(r => r.json()).then(d => { cmSuggestions = d; cmShowSug = d.length > 0; });
                                    } else { cmSuggestions = []; cmShowSug = false; }
                                "
                                @focus="cmShowSug = cmSuggestions.length > 0"
                                placeholder="Nama Pelanggan (Opsional)"
                                class="w-full bg-transparent border-b border-gray-200 px-1 py-1.5 text-xs text-gray-800 placeholder-gray-400 focus:border-indigo-400 outline-none">
                            <div x-show="cmShowSug" @click.outside="cmShowSug = false"
                                class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-[99] overflow-hidden" style="display:none;">
                                <template x-for="s in cmSuggestions" :key="s.name">
                                    <button type="button"
                                        @click="customerName = s.name; customerPhone = s.phone; cmShowSug = false;"
                                        class="w-full text-left px-3 py-2 hover:bg-indigo-50 border-b border-gray-50 last:border-0">
                                        <p class="text-[11px] font-bold text-gray-800" x-text="s.name"></p>
                                        <p class="text-[10px] text-gray-500" x-text="s.phone"></p>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <input type="text" x-model="customerPhone" placeholder="No. Telepon" class="w-full bg-transparent border-b border-gray-200 px-1 py-1.5 text-xs text-gray-800 placeholder-gray-400 focus:border-indigo-400 outline-none">
                    </div>

                    <!-- Status Pembayaran -->
                    <div class="mb-2">
                        <label class="text-[9px] text-gray-500 font-bold uppercase tracking-wider block mb-1">Status Pembayaran</label>
                        <select x-model="paymentStatus"
                            class="w-full bg-white border border-gray-200 text-gray-800 text-xs font-bold rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none cursor-pointer appearance-none shadow-sm">
                            <option value="lunas">✅ LUNAS</option>
                            <option value="tempo">⏳ TEMPO</option>
                            <option value="dp">💰 UANG MUKA (DP)</option>
                            <option value="po">📦 PURCHASE ORDER (PO)</option>
                        </select>
                    </div>

                    <!-- Jatuh Tempo (Hanya untuk Tempo/DP/PO) -->
                    <div x-show="['tempo', 'dp', 'po'].includes(paymentStatus)" class="bg-white rounded-2xl p-3 mb-2 border border-gray-200 shadow-sm transition-all duration-300">
                        <label class="text-[9px] text-gray-500 font-bold uppercase tracking-wider block mb-1">
                            <span x-show="paymentStatus === 'tempo'">Tanggal Jatuh Tempo</span>
                            <span x-show="paymentStatus === 'dp'">Jatuh Tempo Pelunasan DP</span>
                            <span x-show="paymentStatus === 'po'">Jatuh Tempo Pembayaran PO</span>
                        </label>
                        <input type="date" x-model="dueDate" :required="['tempo', 'dp', 'po'].includes(paymentStatus)"
                            class="w-full bg-transparent border-0 text-sm font-bold text-gray-900 p-0 focus:ring-0">
                    </div>

                    <!-- Input Uang Diterima -->
                    <div class="bg-white rounded-2xl p-3 mb-4 border border-gray-200 shadow-sm">
                        <div class="flex gap-3 items-center">
                            <div class="flex-1">
                                <label class="text-[9px] text-gray-500 font-bold uppercase tracking-wider block mb-1" x-text="['tempo', 'dp', 'po'].includes(paymentStatus) ? 'UANG MUKA (DP)' : 'UANG DITERIMA'"></label>
                                <input type="text" inputmode="numeric" x-model="amountPaid"
                                    class="input-currency w-full bg-transparent border-0 text-xl font-black text-gray-900 p-0 focus:ring-0 placeholder-gray-300"
                                    placeholder="0">
                            </div>
                            <div class="w-px h-10 bg-gray-200"></div>
                            <div class="flex-1 text-right">
                                <label class="text-[9px] text-gray-500 font-bold uppercase tracking-wider block mb-1" x-text="['tempo', 'dp', 'po'].includes(paymentStatus) ? 'SISA HUTANG' : 'KEMBALIAN'"></label>
                                <div class="text-lg font-black" :class="['tempo', 'dp', 'po'].includes(paymentStatus) ? 'text-red-500' : (change >= 0 ? 'text-green-600' : 'text-red-500')"
                                    x-text="['tempo', 'dp', 'po'].includes(paymentStatus) ? 'Rp ' + Math.max(0, total - effectiveAmountPaid).toLocaleString('id-ID') : 'Rp ' + Math.max(0, change).toLocaleString('id-ID')"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Split Metode -->
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">Metode Pembayaran</label>
                        <button type="button" @click="splitMode = !splitMode"
                            class="text-[10px] font-bold px-2.5 py-1 rounded-lg transition-colors"
                            :class="splitMode ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            <span x-text="splitMode ? '✓ Split metode AKTIF' : '+ Split metode'"></span>
                        </button>
                    </div>

                    <!-- Repeater Split (multi-metode) -->
                    <div x-show="splitMode" class="bg-white rounded-2xl p-3 mb-2 border border-gray-200 shadow-sm space-y-2" style="display:none;">
                        <template x-for="(p, pi) in splitPayments" :key="pi">
                            <div class="flex gap-2 items-center">
                                <select x-model="p.payment_method_id"
                                    class="w-2/5 bg-white border border-gray-300 text-gray-800 text-[11px] font-bold rounded-lg px-2 py-2 outline-none">
                                    <option value="" disabled>Metode</option>
                                    @foreach($paymentMethods as $pm)
                                        <option value="{{ $pm->id }}">{{ strtoupper($pm->name) }}</option>
                                    @endforeach
                                </select>
                                <input type="text" inputmode="numeric" x-model="p.amount" placeholder="Jumlah"
                                    class="input-currency flex-1 bg-gray-50 border border-gray-200 rounded-lg px-2 py-2 text-sm font-bold text-gray-900 outline-none">
                                <button type="button" @click="removeSplitRow(pi)" class="text-red-400 hover:text-red-600 text-sm px-1">✕</button>
                            </div>
                        </template>
                        <div class="flex items-center justify-between pt-1">
                            <button type="button" @click="addSplitRow()" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">+ Tambah metode</button>
                            <span class="text-[11px] font-bold" :class="splitTotalPaid === total ? 'text-green-600' : 'text-gray-500'">
                                Dialokasikan: Rp <span x-text="splitTotalPaid.toLocaleString('id-ID')"></span> / Rp <span x-text="total.toLocaleString('id-ID')"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Metode Bayar & Tombol BAYAR -->
                    <div class="flex gap-2">
                        <select x-show="!splitMode" x-model="paymentMethodId"
                            class="w-1/3 bg-white border border-gray-300 text-gray-800 text-[11px] font-bold rounded-xl px-2 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none cursor-pointer appearance-none text-center shadow-sm">
                            <option value="" disabled>Pilih Metode</option>
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" data-type="{{ $pm->type }}">{{ strtoupper($pm->name) }}</option>
                            @endforeach
                        </select>

                        <button type="submit"
                            :disabled="cart.length === 0 || (!splitMode && !paymentMethodId) || (splitMode && splitTotalPaid <= 0) || (paymentStatus === 'lunas' && effectiveAmountPaid < total) || (['tempo', 'dp', 'po'].includes(paymentStatus) && !dueDate) || processing"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed text-white font-black py-3 rounded-xl text-base shadow-md hover:shadow-indigo-500/30 transition-all active:scale-95 flex items-center justify-center gap-2">
                            <svg x-show="!processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg x-show="processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            <span x-show="!processing">BAYAR</span>
                            <span x-show="processing">PROSES...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <template x-teleport="body">
            <div x-show="showReceiptModal" style="display: none; z-index: 999999;"
                class="fixed inset-0 flex items-center justify-center bg-gray-900/70 backdrop-blur-md p-4 transition-opacity">
                <div @click.outside="showReceiptModal = false" x-show="showReceiptModal" x-transition.scale.origin.bottom
                    class="bg-white w-full max-w-sm rounded-[2rem] overflow-hidden shadow-2xl flex flex-col max-h-[80vh] border border-white/20">
                    <!-- Header Modal -->
                    <div class="bg-indigo-600 px-5 py-4 flex flex-col gap-3 shrink-0 relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                        <div class="absolute -left-4 -bottom-4 w-24 h-24 bg-black/10 rounded-full blur-xl"></div>

                        <div class="flex justify-between items-center relative z-10">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-white font-black text-xl tracking-wide">Transaksi Berhasil!</h3>
                                    <p class="text-indigo-100 text-xs font-medium">Pembayaran telah diterima</p>
                                </div>
                            </div>
                            <button @click="showReceiptModal = false"
                                class="text-white/70 hover:text-white bg-black/10 hover:bg-black/20 p-2 rounded-full transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        @if(auth()->user()->hasRole('superadmin'))
                            <!-- Superadmin Printer Selector -->
                            <div
                                class="relative z-10 bg-black/10 border border-white/10 rounded-xl p-2 flex items-center justify-between">
                                <span class="text-[9px] font-bold text-white/80 uppercase tracking-widest ml-1">Printer:</span>
                                <select x-model="printMethod"
                                    @change="localStorage.setItem('pos_print_method', $event.target.value)"
                                    class="bg-white border-none rounded-lg py-1 px-3 text-[9px] font-black text-indigo-600 focus:ring-0 cursor-pointer">
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
                    <div
                        class="flex-1 bg-gray-50 overflow-y-auto py-4 px-2 flex justify-center custom-scrollbar relative shadow-inner">
                        <!-- Decorative background elements -->
                        <div
                            class="absolute inset-0 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:16px_16px] opacity-50">
                        </div>

                        <div id="print-area" x-html="['pc_bluetooth', 'ios_bluefy', 'android_bluetooth'].includes(printMethod) ? buildSimpleReceiptHtml(currentSaleData) : receiptHtmlHtml"
                            class="bg-white shadow-xl p-0 relative z-10 transition-transform duration-300 min-w-[72mm] flex-shrink-0"
                            style="zoom: 0.7; min-height: 100px;"></div>
                    </div>

                    <!-- Area Aksi / Tombol -->
                    <div
                        class="p-3 bg-white border-t border-gray-100 flex flex-col gap-2 shrink-0 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.05)]">
                        <button @click="executePrint()"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-2.5 rounded-2xl text-base shadow-[0_8px_20px_-6px_rgba(79,70,229,0.5)] hover:shadow-[0_12px_25px_-8px_rgba(79,70,229,0.7)] transition-all flex items-center justify-center gap-2 group">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            CETAK STRUK SEKARANG
                        </button>
                        <button @click="showReceiptModal = false"
                            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 rounded-2xl text-sm transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                            Selesai & Transaksi Baru
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>



    <style>
        /* Mempercantik Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 20px;
        }

        /* Sembunyikan arrow pada input number qty */
        .hide-arrows::-webkit-outer-spin-button,
        .hide-arrows::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .hide-arrows {
            -moz-appearance: textfield;
        }

        /* =======================================================
       CSS MODE CETAK (PRINT) - ANTI KERTAS KOSONG
       ======================================================= */
        @media print {

            /* 1. Sembunyikan Sidebar, Header, Navigasi (bawaan layout Laravel Anda) */
            header,
            nav,
            aside {
                display: none !important;
            }

            /* 2. Sembunyikan Area Katalog (Kiri) & Area Tagihan (Kanan) secara permanen dari kertas */
            .flex-1.min-w-0,
            .w-\\[360px\\],
            .xl\\:w-\\[420px\\] {
                display: none !important;
            }

            /* 3. Matikan paksaan tinggi layar (100vh) agar kertas tidak ikut panjang */
            .h-\\[calc\\(100vh-6\\.5rem\\)\\] {
                height: auto !important;
                overflow: visible !important;
            }

            /* 4. Lepaskan Pop-up Modal dari posisinya agar menempel langsung ke kertas putih */
            .fixed.inset-0 {
                position: static !important;
                background: transparent !important;
                padding: 0 !important;
            }

            .bg-white.max-w-sm {
                max-width: 100% !important;
                box-shadow: none !important;
                height: auto !important;
            }

            /* 5. Sembunyikan Header Biru pada Modal & Tombol "Cetak" di bawahnya */
            .bg-indigo-600.px-5,
            .border-t.border-gray-100 {
                display: none !important;
            }

            /* 6. Hilangkan margin bawaan browser */
            @page {
                margin: 0;
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        const posCatalogData = window.POS_CATALOG_DATA || [];

        function posApp(sessionId, storeId) {
            return {
                catalog: posCatalogData,
                cart: [],
                search: '',
                paymentMethodId: '',
                amountPaid: '',
                discountAmount: 0,
                processing: false,
                barcodeBuffer: '',
                barcodeTimer: null,
                isFocusMode: false,
                clickCount: 0,
                clickTimer: null,
                showReceiptModal: false,
                receiptHtmlHtml: '',
                currentSaleData: null,
                printMethod: localStorage.getItem('pos_print_method') || 'pc_usb',
                cachedBluetoothDevice: null, // CACHE KONEKSI UNTUK PC BLUETOOTH
                cachedCharacteristic: null,
                customerName: '',
                customerPhone: '',
                paymentStatus: 'lunas', // 'lunas' | 'tempo' | 'dp' | 'po'
                dueDate: '',
                splitMode: false,
                splitPayments: [{ payment_method_id: '', amount: '' }],

                init() {
                    // Watch total: Jika total berubah dan metode bukan Cash, update amountPaid
                    this.$watch('total', (value) => {
                        if (this.paymentMethodId && !this.isCashSelected()) {
                            this.amountPaid = this.formatCurrency(value);
                        }
                    });
                    // Watch paymentMethodId: Jika berubah ke non-cash, isi amountPaid sesuai total
                    this.$watch('paymentMethodId', (value) => {
                        if (value && !this.isCashSelected()) {
                            this.amountPaid = this.formatCurrency(this.total);
                        }
                    });
                },

                formatCurrency(value) {
                    let numberString = value.toString().replace(/\D/g, '');
                    if (numberString === '') return '';
                    return parseInt(numberString, 10).toLocaleString('id-ID');
                },

                handleGlobalClick(e) {
                    // Jangan hitung ketukan jika kasir sedang mengklik Tombol, Link, atau Input/Kolom Teks
                    if (['INPUT', 'BUTTON', 'A', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) return;

                    this.clickCount++;
                    clearTimeout(this.clickTimer);

                    // Waktu maksimal antar ketukan adalah 400 milidetik
                    this.clickTimer = setTimeout(() => {
                        if (this.clickCount >= 3) {
                            this.toggleFocusMode();
                        }
                        this.clickCount = 0;
                    }, 400);
                },

                enterFocusMode() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(err => {
                            alert(`Gagal masuk mode fokus: ${err.message}`);
                        });
                        this.isFocusMode = true;
                    }
                },

                // Fungsi KHUSUS KELUAR Fullscreen
                exitFocusMode() {
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    }
                    this.isFocusMode = false;
                },

                toggleEcer(item) {
                    item.is_ecer = !item.is_ecer;
                    if (item.is_ecer) {
                        item.price = item.retailPrice;
                    } else {
                        item.price = item.systemPrice;
                    }
                },

                get filteredCatalog() {
                    let list;
                    if (this.search.trim() === '') {
                        list = this.catalog;
                    } else {
                        let q = this.search.toLowerCase();
                        list = this.catalog.filter(p =>
                            p.sku.toLowerCase().includes(q) ||
                            p.name.toLowerCase().includes(q)
                        );
                    }
                    return [...list].sort((a, b) => b.stock - a.stock);
                },

                getCartQty(variantId) {
                    const item = this.cart.find(i => i.variant_id === variantId);
                    return item ? item.qty : 0;
                },

                get rawAmountPaid() { return Number(this.amountPaid.toString().replace(/\D/g, '')) || 0; },
                get rawDiscountAmount() { return Number(this.discountAmount.toString().replace(/\D/g, '')) || 0; },
                get subtotal() { return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0); },
                get total() { return Math.max(0, this.subtotal - this.rawDiscountAmount); },
                // Multi-payment (split metode)
                get splitTotalPaid() { return this.splitPayments.reduce((s, p) => s + (Number((p.amount || '').toString().replace(/\D/g, '')) || 0), 0); },
                get effectiveAmountPaid() { return this.splitMode ? this.splitTotalPaid : this.rawAmountPaid; },
                get primaryMethodId() { return this.splitMode ? (this.splitPayments[0]?.payment_method_id || '') : this.paymentMethodId; },
                addSplitRow() { this.splitPayments.push({ payment_method_id: '', amount: '' }); },
                removeSplitRow(i) { this.splitPayments.splice(i, 1); if (this.splitPayments.length === 0) this.splitPayments.push({ payment_method_id: '', amount: '' }); },
                get change() { return this.effectiveAmountPaid - this.total; },

                isCashSelected() {
                    let select = document.querySelector('select[x-model="paymentMethodId"]');
                    let option = select.options[select.selectedIndex];
                    return option ? option.getAttribute('data-type') === 'cash' : true;
                },

                addToCart(product) {
                    if (product.stock <= 0) {
                        alert(`Stok produk ${product.sku} sudah habis!`);
                        return;
                    }

                    const existing = this.cart.find(i => i.variant_id === product.id);
                    if (existing) {
                        if (existing.qty < existing.maxQty) existing.qty++;
                        else alert('Maksimal stok tercapai!');
                    } else {
                        this.cart.unshift({
                            variant_id:    product.id,
                            sku:           product.sku,
                            name:          product.name,
                            price:         product.price,
                            systemPrice:   product.price,
                            retailPrice:   product.retail_price || product.price,
                            is_ecer:       false,
                            qty:           1,
                            maxQty:        product.stock,
                            image:         product.image
                        });
                    }

                    this.search = '';
                    document.getElementById('searchInput').focus();

                    // Auto-scroll cart ke bawah ketika item sudah 5 atau lebih
                    this.$nextTick(() => {
                        if (this.cart.length >= 5) {
                            const cartList = document.getElementById('cart-list');
                            if (cartList) cartList.scrollTop = cartList.scrollHeight;
                        }
                    });
                },

                qtyUp(idx) {
                    const item = this.cart[idx];
                    if (item.qty < item.maxQty) item.qty++;
                },

                qtyDown(idx) {
                    const item = this.cart[idx];
                    if (item.qty > 1) item.qty--;
                    else this.cart.splice(idx, 1);
                },

                handleEnterScan() {
                    if (this.search.trim() !== '') {
                        this.processScan(this.search.trim());
                    }
                },

                handleGlobalScan(e) {
                    // PERBAIKAN: Hapus pengecualian 'searchInput' agar fungsi global 
                    // ini sepenuhnya mati jika kursor Anda sedang berada di dalam kolom input apapun
                    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

                    if (e.key === 'Enter') {
                        if (this.barcodeBuffer.length > 0) {
                            this.processScan(this.barcodeBuffer);
                            this.barcodeBuffer = '';
                        }
                    } else {
                        if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                            this.barcodeBuffer += e.key;
                            clearTimeout(this.barcodeTimer);
                            this.barcodeTimer = setTimeout(() => { this.barcodeBuffer = ''; }, 50);
                        }
                    }
                },

                toggleFocusMode() {
                    if (!document.fullscreenElement) {
                        // Masuk Fullscreen
                        document.documentElement.requestFullscreen().catch(err => {
                            alert(`Gagal masuk mode fokus: ${err.message}`);
                        });
                        this.isFocusMode = true;
                    } else {
                        // Keluar Fullscreen
                        document.exitFullscreen();
                        this.isFocusMode = false;
                    }
                },

                processScan(scannedSku) {
                    let product = this.catalog.find(p => p.sku.toLowerCase() === scannedSku.toLowerCase());
                    if (product) {
                        this.addToCart(product);
                    } else {
                        alert('SKU "' + scannedSku + '" tidak ditemukan atau stok habis di toko ini!');
                        this.search = '';
                    }
                },

                async submitSale(e) {
                    if (this.cart.length === 0) return;
                    if (!this.splitMode && !this.paymentMethodId) return;
                    if (this.splitMode && this.splitTotalPaid <= 0) return;
                    if (this.paymentStatus === 'lunas' && this.effectiveAmountPaid < this.total) return;
                    if (['tempo', 'dp', 'po'].includes(this.paymentStatus) && !this.dueDate) return;
                    this.processing = true;

                    try {
                        let formData = new FormData(e.target);
                        let res = await fetch("{{ route('pos.sale') }}", {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' } // Penanda AJAX
                        });

                        let data = await res.json();

                        if (data.success) {
                            // UPDATE STOK DI KATALOG LOKAL (Agar tidak perlu reload & koneksi bluetooth aman)
                            this.cart.forEach(item => {
                                let product = this.catalog.find(p => p.id === item.variant_id);
                                if (product) {
                                    product.stock -= item.qty;
                                }
                            });

                            this.receiptHtmlHtml = data.html;      // Tampilkan desain di Modal
                            this.currentSaleData = data.sale;      // Simpan data mentah untuk Flutter
                            this.showReceiptModal = true;          // Munculkan Modal

                            // Kosongkan form untuk transaksi berikutnya
                            this.cart = [];
                            this.amountPaid = '';
                            this.paymentMethodId = '';
                            this.discountAmount = 0;
                            this.customerName = '';
                            this.customerPhone = '';
                            this.paymentStatus = 'lunas';
                            this.dueDate = '';
                            this.splitMode = false;
                            this.splitPayments = [{ payment_method_id: '', amount: '' }];
                        } else {
                            alert('Gagal: ' + data.error);
                        }
                    } catch (err) {
                        alert('Terjadi kesalahan jaringan.');
                    }
                    this.processing = false;
                },
                buildSimpleReceiptHtml(sale) {
                    if (!sale) return '';
                    const fmt = (n) => parseInt(n || 0).toLocaleString('id-ID');
                    let rows = sale.items.map(item => {
                        const name = item.variant?.product?.name || '-';
                        const sku  = item.variant?.sku || '';
                        const color = item.variant?.color?.name || '';
                        const size = item.variant?.size?.name || '';
                        const itemTypeLabel = item.is_ecer ? '(Ecer)' : '(Grosir)';
                        return `<div style="margin-bottom:8px">
                            <div style="font-weight:bold;font-size:13px">${name} <span style="font-size: 11px; color: #444;">${itemTypeLabel}</span></div>
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
                        <div style="display:flex;justify-content:space-between;margin-top:8px"><span>Bayar (${sale.payment_status === 'lunas' ? 'Tunai' : 'Uang Muka/DP'})</span><span>Rp ${fmt(sale.amount_paid)}</span></div>
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
                        // METODE IFRAME: Cetak secara tersembunyi tanpa merusak layar kasir
                        let printFrame = document.createElement('iframe');
                        printFrame.style.display = 'none';
                        document.body.appendChild(printFrame);

                        // Masukkan desain struk ke dalam iframe
                        printFrame.contentDocument.write('<html><head><style>@page { margin: 0; } body { margin: 0; font-family: monospace; }</style></head><body>' + this.receiptHtmlHtml + '</body></html>');
                        printFrame.contentDocument.close();

                        // Fokus dan Cetak Iframe
                        printFrame.contentWindow.focus();
                        printFrame.contentWindow.print();

                        // Hapus iframe setelah selesai (Jeda 2 detik)
                        setTimeout(() => document.body.removeChild(printFrame), 2000);
                    }
                    else if (this.printMethod === 'android_flutter') {
                        // FORMAT ULANG JSON UNTUK FLUTTER AGAR TIDAK ERROR "NULL"
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
                        // KELUARGA WEB BLUETOOTH (iOS, PC, Android Chrome)
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
                            
                            // Header dengan perbesaran teks dan tebal
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
                            
                            let statusLabel = (sale.payment_status === 'tempo') ? 'TEMPO / DP / PO' : 'LUNAS';
                            text += alignLR("Status:", statusLabel);

                            if (sale.customer_name) {
                                text += "------------------------------------------------\n";
                                text += alignLR("Nama Pelanggan:", sale.customer_name);
                                if (sale.customer_phone) {
                                    text += alignLR("No telp Pelanggan:", sale.customer_phone);
                                }
                            }

                            text += "------------------------------------------------\n";
                            text += alignLR("ITEM", "TOTAL");
                            text += "------------------------------------------------\n";

                            sale.items.forEach(item => {
                                let itemTypeLabel = item.is_ecer ? '(Ecer)' : '(Grosir)';
                                text += String(item.variant.product.name + " " + itemTypeLabel).substring(0, 48) + "\n";
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
                            
                            let bayarLabel = (sale.payment_status === 'tempo') ? 'Bayar (DP)' : 'Bayar (Tunai)';
                            text += alignLR(bayarLabel, "Rp " + parseInt(sale.amount_paid).toLocaleString('id-ID'));
                            
                            if (sale.payment_status === 'tempo') {
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

                            // ESC/POS QR Code
                            let qrData = sale.sale_no;
                            let qrBytes = encoder.encode(qrData);
                            let pL = (qrBytes.length + 3) % 256;
                            let pH = Math.floor((qrBytes.length + 3) / 256);
                            let qrCmds = new Uint8Array([
                                0x1B, 0x61, 0x01, // Align Center
                                0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00,
                                0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, 0x06,
                                0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, 0x31,
                                0x1D, 0x28, 0x6B, pL, pH, 0x31, 0x50, 0x30, ...qrBytes,
                                0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x51, 0x30,
                                0x0A, 0x0A
                            ]);

                            // ESC/POS Barcode Code128
                            let barcodeBytes = encoder.encode("{B" + sale.sale_no);
                            let textBelowBarcodeBytes = encoder.encode(sale.sale_no + "\n");
                            let barcodeCmds = new Uint8Array([
                                0x1B, 0x61, 0x01, // Align Center
                                0x1D, 0x68, 60,   // Height
                                0x1D, 0x77, 2,    // Width
                                0x1D, 0x48, 0,    // HRI text disabled
                                0x1D, 0x6B, 73, barcodeBytes.length, ...barcodeBytes,
                                0x0A,
                                ...textBelowBarcodeBytes,
                                0x0A, 0x0A
                            ]);

                            const thanksBytes = encoder.encode(alignC("TERIMA KASIH TELAH BERBELANJA") + alignC("Silahkan bawa struk ini untuk retur barang", 48));
                            const feed = new Uint8Array([0x1B, 0x64, 0x05]);

                            // Combine all payloads
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