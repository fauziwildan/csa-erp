@extends('layouts.app')
@section('title', 'Dashboard Toko')
@section('page-title', 'Performa Toko Hari Ini')

@section('content')
<div class="dash">
<div class="space-y-6">
    <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3 uppercase tracking-tight">Ringkasan Finansial Toko Hari Ini</h3>
    </div>

    @php
        $myStoreIds = auth()->user()->stores->pluck('id');
        $trfReceive = \App\Models\Transfer::where('status','shipped')->whereIn('to_store_id', $myStoreIds)->count();   // barang masuk → perlu DITERIMA
        $trfApprove = \App\Models\Transfer::where('status','pending')->whereIn('from_store_id', $myStoreIds)->count(); // permintaan keluar → perlu DISETUJUI
        $trfShip    = \App\Models\Transfer::where('status','approved')->whereIn('from_store_id', $myStoreIds)->count(); // disetujui → perlu DIKIRIM
        $trfTotal   = $trfReceive + $trfApprove + $trfShip;
    @endphp

    {{-- ============ PERLU TINDAKAN: TRANSFER ANTAR TOKO (biar tidak "gaib") ============ --}}
    @if($trfTotal > 0)
    <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm overflow-hidden">
        <div class="bg-indigo-50/60 border-b border-indigo-100 px-5 py-3.5 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600 animate-pulse shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            <h4 class="text-sm font-bold text-indigo-900">Perlu Tindakan — Transfer Antar Toko</h4>
            <span class="ml-auto text-xs bg-indigo-600 text-white px-2.5 py-0.5 rounded-full font-bold">{{ $trfTotal }}</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
            {{-- Barang masuk: TERIMA (penting — pakai from_store_id kosong agar transfer MASUK ikut tampil) --}}
            <a href="{{ route('transfers.index', ['status' => 'shipped', 'from_store_id' => '']) }}" class="p-4 flex items-center gap-3 hover:bg-indigo-50/40 transition-colors {{ $trfReceive==0 ? 'opacity-50' : '' }}">
                <span class="w-11 h-11 rounded-xl bg-green-100 text-green-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-3.5l-1 2h-7l-1-2H4"/></svg>
                </span>
                <div>
                    <p class="text-2xl font-black text-gray-900 leading-none">{{ $trfReceive }}</p>
                    <p class="text-xs text-gray-500 mt-1">Barang masuk → klik <strong class="text-green-700">“Terima”</strong></p>
                </div>
            </a>
            {{-- Permintaan keluar: SETUJUI --}}
            <a href="{{ route('transfers.index', ['status' => 'pending']) }}" class="p-4 flex items-center gap-3 hover:bg-indigo-50/40 transition-colors {{ $trfApprove==0 ? 'opacity-50' : '' }}">
                <span class="w-11 h-11 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <p class="text-2xl font-black text-gray-900 leading-none">{{ $trfApprove }}</p>
                    <p class="text-xs text-gray-500 mt-1">Permintaan → <strong class="text-amber-700">“Setujui”</strong></p>
                </div>
            </a>
            {{-- Disetujui: KIRIM --}}
            <a href="{{ route('transfers.index', ['status' => 'approved']) }}" class="p-4 flex items-center gap-3 hover:bg-indigo-50/40 transition-colors {{ $trfShip==0 ? 'opacity-50' : '' }}">
                <span class="w-11 h-11 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
                <div>
                    <p class="text-2xl font-black text-gray-900 leading-none">{{ $trfShip }}</p>
                    <p class="text-xs text-gray-500 mt-1">Disetujui → <strong class="text-blue-700">“Kirim”</strong></p>
                </div>
            </a>
        </div>
    </div>
    @endif

    {{-- Pelanggan Jatuh Tempo Card --}}
    @if(isset($approachingDueSales) && $approachingDueSales->count() > 0)
    <div class="bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden mb-6">
        <div class="bg-red-50/50 border-b border-red-100 px-6 py-4 flex items-center justify-between">
            <h4 class="text-sm font-bold text-red-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Peringatan Pembayaran Jatuh Tempo Pelanggan
            </h4>
            <span class="text-xs bg-red-100 text-red-800 px-2.5 py-0.5 rounded-full font-bold">Mendekati / Lewat Tempo</span>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($approachingDueSales as $sale)
            @php
                $due = \Carbon\Carbon::parse($sale->due_date);
                $isOverdue = $due->isPast();
                $daysDiff = now()->startOfDay()->diffInDays($due, false);
            @endphp
            <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-red-50/10 transition-colors">
                <div class="flex items-start gap-3">
                    <div class="p-2 {{ $isOverdue ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }} rounded-lg shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h5 class="text-sm font-bold text-gray-900">{{ $sale->customer_name }}</h5>
                            <span class="text-xs text-gray-500 font-mono">({{ $sale->customer_phone ?: '-' }})</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            No. Transaksi: <span class="font-mono text-indigo-600 font-semibold">{{ $sale->sale_no }}</span> 
                            @if($sale->store)
                                · Toko: <span class="font-medium text-gray-700">{{ $sale?->store?->name }}</span>
                            @endif
                        </p>
                        <p class="text-xs font-semibold mt-1">
                            Status: 
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] uppercase font-bold {{ $sale->payment_status === 'tempo' ? 'bg-red-100 text-red-800' : ($sale->payment_status === 'dp' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ strtoupper($sale->payment_status) }}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-6 justify-between sm:justify-end">
                    <div class="text-left sm:text-right">
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Sisa Hutang</p>
                        <p class="text-sm font-extrabold text-red-600">Rp {{ number_format(max(0, $sale->total_amount - $sale->amount_paid), 0, ',', '.') }}</p>
                    </div>
                    <div class="text-left sm:text-right min-w-[120px]">
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Jatuh Tempo</p>
                        <p class="text-xs font-bold {{ $isOverdue ? 'text-red-600' : 'text-amber-600' }}">
                            {{ $due->format('d/m/Y') }}
                        </p>
                        <p class="text-[10px] mt-0.5 {{ $isOverdue ? 'text-red-700 font-extrabold' : 'text-gray-500 font-semibold' }}">
                            @if($daysDiff < 0)
                                Lewat {{ abs($daysDiff) }} hari!
                            @elseif($daysDiff == 0)
                                Hari ini!
                            @else
                                {{ $daysDiff }} hari lagi
                            @endif
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <a href="{{ route('store.customers.show', ['name' => $sale->customer_name, 'phone' => $sale->customer_phone]) }}" class="inline-flex items-center gap-1 bg-indigo-600 text-white hover:bg-indigo-700 text-xs px-3 py-1.5 rounded-lg font-medium shadow-sm transition">
                            <span>Detail</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Daily Financial Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 cards-tight">
        {{-- Card Pemasukan Hari Ini --}}
        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm group relative overflow-hidden transition-all hover:shadow-md">
            <div class="absolute top-0 right-0 w-16 h-16 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <p class="text-xs font-bold text-blue-500 uppercase tracking-wider mb-1 relative z-10">Pemasukan Hari Ini</p>
            <div class="flex items-center justify-between relative z-10">
                <h4 class="text-xl font-black text-gray-900">Rp {{ number_format($todaySales, 0, ',', '.') }}</h4>
                <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>

        {{-- Card Pengeluaran Hari Ini --}}
        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm group relative overflow-hidden transition-all hover:shadow-md">
            <div class="absolute top-0 right-0 w-16 h-16 bg-red-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <p class="text-xs font-bold text-red-500 uppercase tracking-wider mb-1 relative z-10">Pengeluaran Hari Ini</p>
            <div class="flex items-center justify-between relative z-10">
                <h4 class="text-xl font-black text-gray-900">Rp {{ number_format($todayExpense, 0, ',', '.') }}</h4>
                <div class="p-2 bg-red-100 rounded-lg text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                </div>
            </div>
        </div>

        {{-- Card Keuntungan Hari Ini --}}
        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm group relative overflow-hidden transition-all hover:shadow-md">
            <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1 relative z-10">Keuntungan Hari Ini</p>
            <div class="flex items-center justify-between relative z-10">
                <h4 class="text-xl font-black text-gray-900">Rp {{ number_format($todayProfit, 0, ',', '.') }}</h4>
                <div class="p-2 bg-emerald-100 rounded-lg text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </div>
            </div>
        </div>

        {{-- Card Penjualan Hari Ini (Trx) --}}
        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm group relative overflow-hidden transition-all hover:shadow-md">
            <div class="absolute top-0 right-0 w-16 h-16 bg-amber-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <p class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1 relative z-10">Penjualan Hari Ini</p>
            <div class="flex items-center justify-between relative z-10">
                <h4 class="text-xl font-black text-gray-900">{{ number_format($todayOrders) }} Trx</h4>
                <div class="p-2 bg-amber-100 rounded-lg text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ MENU MODUL (navigasi kepala toko, pengganti sidebar) ============ --}}
    <div>
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider border-l-4 border-indigo-500 pl-3 mb-4">Menu Utama</h3>
        @php
            $modules = [
                ['can'=>'access pos','route'=>'pos.index','label'=>'Kasir / POS','desc'=>'Transaksi penjualan','color'=>'bg-indigo-100 text-indigo-700','icon'=>'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z'],
                ['can'=>'view catalog','route'=>'catalog.index','label'=>'Katalog','desc'=>'Kelola produk','color'=>'bg-blue-100 text-blue-700','icon'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                ['can'=>'view store','route'=>'store.stock.index','label'=>'Stok Toko','desc'=>'Inventori & stok','color'=>'bg-emerald-100 text-emerald-700','icon'=>'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                ['can'=>'receive store shipment','route'=>'store.receiving.index','label'=>'Terima Kiriman','desc'=>'Penerimaan barang','color'=>'bg-cyan-100 text-cyan-700','icon'=>'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-3.5l-1 2h-7l-1-2H4'],
                ['can'=>'view stock opname','route'=>'store.opname.index','label'=>'Stock Opname','desc'=>'Hitung stok fisik','color'=>'bg-amber-100 text-amber-700','icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                ['can'=>'view customers','route'=>'customers.index','label'=>'Pelanggan','desc'=>'Data pelanggan','color'=>'bg-purple-100 text-purple-700','icon'=>'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 00-3-3.87'],
                ['can'=>'approve credit','route'=>'credit-approvals.index','label'=>'Persetujuan Kredit','desc'=>'Review kredit','color'=>'bg-rose-100 text-rose-700','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['can'=>'view transfer','route'=>'transfers.index','label'=>'Transfer Toko','desc'=>'Kirim antar toko','color'=>'bg-orange-100 text-orange-700','icon'=>'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                ['can'=>'view customer return','route'=>'returns.customer.index','label'=>'Retur','desc'=>'Retur barang','color'=>'bg-red-100 text-red-700','icon'=>'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'],
                ['can'=>'view settlement','route'=>'settlements.index','label'=>'Settlement','desc'=>'Setoran ke owner','color'=>'bg-teal-100 text-teal-700','icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['can'=>'view report','route'=>'reports.index','label'=>'Laporan','desc'=>'Laporan keuangan','color'=>'bg-yellow-100 text-yellow-700','icon'=>'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['can'=>'view expenses','route'=>'expenses.index','label'=>'Pengeluaran','desc'=>'Catat pengeluaran','color'=>'bg-pink-100 text-pink-700','icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                ['can'=>'print product label','route'=>'labels.picker','label'=>'Cetak Label','desc'=>'Barcode & label','color'=>'bg-slate-100 text-slate-700','icon'=>'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a1 1 0 001-1v-4a1 1 0 00-1-1H9a1 1 0 00-1 1v4a1 1 0 001 1zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h14z'],
                ['can'=>'view product','route'=>'products.index','label'=>'Produk & SKU','desc'=>'Kelola SKU produk','color'=>'bg-violet-100 text-violet-700','icon'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['can'=>'view pos','route'=>'pos.history','label'=>'Riwayat Transaksi','desc'=>'Log transaksi','color'=>'bg-gray-100 text-gray-700','icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            @foreach($modules as $m)
                @can($m['can'])
                <a href="{{ $m['route'] === 'transfers.index' ? route('transfers.index', ['from_store_id' => '']) : route($m['route']) }}" class="relative bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 flex flex-col items-center justify-center text-center gap-2.5 min-h-[118px] sm:min-h-[128px] hover:-translate-y-1 hover:shadow-md hover:border-indigo-200 transition-all group">
                    @if($m['route'] === 'transfers.index' && $trfTotal > 0)
                        <span class="absolute top-3 right-3 bg-red-500 text-white text-[10px] font-bold min-w-[20px] h-5 px-1 rounded-full flex items-center justify-center shadow">{{ $trfTotal }}</span>
                    @endif
                    <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl {{ $m['color'] }} flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-gray-800 leading-tight">{{ $m['label'] }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5 leading-tight">{{ $m['desc'] }}</p>
                    </div>
                </a>
                @endcan
            @endforeach

            {{-- Placeholder modul mendatang --}}
            <div class="relative bg-gray-50/60 rounded-2xl border border-dashed border-gray-200 p-4 sm:p-5 flex flex-col items-center justify-center text-center gap-2.5 min-h-[118px] sm:min-h-[128px] cursor-default select-none">
                <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-indigo-50 text-indigo-400 flex items-center justify-center">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </span>
                <div>
                    <p class="text-sm font-bold text-indigo-400 leading-tight">Menu Lainnya</p>
                    <p class="text-[11px] text-gray-400 mt-0.5 leading-tight">Segera hadir</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group">
            <div>
                <h3 class="text-gray-500 font-bold text-xs uppercase">Penerimaan Barang</h3>
                <p class="text-sm text-gray-400 mt-1">Cek apakah ada kiriman dari gudang yang belum diterima.</p>
            </div>
            <a href="{{ route('store.receiving.index') }}" class="p-3 bg-indigo-50 text-indigo-600 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl flex items-center gap-4 text-amber-800">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="text-sm font-medium">
                Pastikan kasir selalu melakukan <strong>Close Session</strong> di akhir shift untuk menjaga akurasi laporan keuangan.
            </div>
        </div>
    </div>

    {{-- Latest Stock Opname Card --}}
    @if(isset($latestOpname) && $latestOpname)
    <div x-data="{ openOpnameModal: false }" x-init="$watch('openOpnameModal', val => document.body.style.overflow = val ? 'hidden' : '')" class="mt-4">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group cursor-pointer hover:border-indigo-300 transition-colors" @click="openOpnameModal = true">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Stock Opname Terbaru</h3>
                    <p class="text-xs text-gray-500 mt-1">No. <span class="font-mono text-indigo-600 font-semibold">{{ $latestOpname->opname_no }}</span> · {{ $latestOpname->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
            <div class="text-right">
                @php
                    $statusColors = ['draft'=>'bg-gray-100 text-gray-600','submitted'=>'bg-blue-100 text-blue-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700'];
                    $statusLabels = ['draft'=>'Draft','submitted'=>'Disubmit','approved'=>'Disetujui','rejected'=>'Ditolak'];
                @endphp
                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold {{ $statusColors[$latestOpname->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $statusLabels[$latestOpname->status] ?? $latestOpname->status }}</span>
                <p class="text-[10px] text-gray-400 mt-1 group-hover:text-indigo-500">Klik untuk detail &rarr;</p>
            </div>
        </div>

        {{-- Modal --}}
        <template x-teleport="body">
            <div x-show="openOpnameModal" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4 py-6" x-transition.opacity>
                <div @click.away="openOpnameModal = false" class="bg-white rounded-2xl shadow-xl w-full max-w-3xl flex flex-col overflow-hidden" style="max-height: 85vh;" x-transition.scale.origin.bottom>
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 shrink-0">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Detail Stock Opname <span class="font-mono text-indigo-600 text-base ml-2">{{ $latestOpname->opname_no }}</span></h3>
                        <p class="text-xs text-gray-500 mt-1">Lokasi: {{ $latestOpname->location_type === 'store' ? 'Toko' : 'Gudang' }} · Dibuat oleh: {{ $latestOpname->creator->name ?? '-' }}</p>
                    </div>
                    <button type="button" @click="openOpnameModal = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-0 overflow-y-auto flex-1 min-h-0">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] tracking-wider sticky top-0 shadow-sm">
                            <tr>
                                <th class="px-6 py-3">SKU</th>
                                <th class="px-6 py-3">Produk</th>
                                <th class="px-6 py-3 text-right">Sistem</th>
                                <th class="px-6 py-3 text-right">Aktual</th>
                                <th class="px-6 py-3 text-right">Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($latestOpname->items as $item)
                                @php $v = $item->variant; $diff = $item->qty_difference; @endphp
                                <tr class="hover:bg-gray-50 {{ $diff !== null && $diff != 0 ? 'bg-yellow-50/30' : '' }}">
                                    <td class="px-6 py-3 font-mono text-xs">{{ $v?->sku }}</td>
                                    <td class="px-6 py-3 text-xs">{{ $v?->product?->name }} · {{ $v?->color?->name }}/{{ $v?->size?->name }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-gray-700">{{ $item->qty_system }}</td>
                                    <td class="px-6 py-3 text-right text-gray-700">{{ $item->qty_actual ?? '-' }}</td>
                                    <td class="px-6 py-3 text-right font-bold {{ $diff === null ? 'text-gray-400' : ($diff > 0 ? 'text-green-600' : ($diff < 0 ? 'text-red-600' : 'text-gray-500')) }}">
                                        {{ $diff !== null ? ($diff > 0 ? '+'.$diff : $diff) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end items-center gap-3 shrink-0">
                    <a href="{{ route('opname.show', $latestOpname) }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold px-4 py-2">Buka Halaman Opname &rarr;</a>
                    <button type="button" @click="openOpnameModal = false" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-semibold transition-colors">Tutup</button>
                </div>
            </div>
        </template>
    </div>
    @endif
</div>
<div class="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="font-bold text-gray-800">Produk Tersedia di Toko Anda</h3>
                <p class="text-xs text-gray-500 mt-0.5">Menampilkan produk dengan stok aktif di lokasi Anda.</p>
            </div>
            <a href="{{ route('catalog.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">Buka Katalog Lengkap &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
    <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
        <tr>
            <th class="px-6 py-3">Produk</th>
            <th class="px-6 py-3">Brand</th>
            <th class="px-6 py-3 text-right">Stok</th> {{-- Tambahkan Header Stok --}}
            <th class="px-6 py-3 text-right">Harga Jual</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        @forelse($products as $p)
        @php
            // Hitung total stok dari semua varian (sudah terfilter di Controller)
            $totalQty = $p->variants->sum(fn($v) => $v->stocks->sum('qty'));
        @endphp
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-3">
                <div class="flex items-center gap-4">
                    {{-- Gambar Produk --}}
                    <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden border border-gray-100 shrink-0">
                        @php $primaryImg = $p->primaryImage(); @endphp
                        @if($primaryImg && $primaryImg->path)
                            <img src="{{ Storage::url($primaryImg->path) }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-xl bg-gray-50 text-gray-300">👕</div>
                        @endif
                    </div>
                    <div>
                        <span class="font-bold text-gray-800">{{ $p->name }}</span><br>
                        <span class="text-[10px] text-gray-400 font-mono uppercase tracking-tight">{{ $p->model_code }}</span>
                    </div>
                </div>
            </td>
            <td class="px-6 py-3 text-xs text-gray-500">
                {{ $p?->brand?->name ?? '—' }}
            </td>
            {{-- Kolom Stok --}}
            <td class="px-6 py-3 text-right">
                <span class="inline-block px-2 py-1 rounded-lg font-bold text-sm {{ $totalQty <= 5 ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">
                    {{ number_format($totalQty) }} <span class="text-[10px] font-normal uppercase">Pcs</span>
                </span>
            </td>
            <td class="px-6 py-3 text-right font-bold text-gray-900">
                Rp {{ number_format($p->sell_price, 0, ',', '.') }}
            </td>
        </tr>
        @empty
        {{-- ... empty state ... --}}
        @endforelse
    </tbody>
</table>
        </div>
    </div>
</div>
@endsection