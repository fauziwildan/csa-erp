@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    /* ===== Dashboard premium glass (semua kartu .bg-white di dalam .dash) ===== */
    .dash .bg-white{
        background:linear-gradient(160deg, rgba(255,255,255,.78), rgba(255,255,255,.56)) !important;
        backdrop-filter:blur(18px) saturate(140%); -webkit-backdrop-filter:blur(18px) saturate(140%);
        border:1px solid rgba(255,255,255,.6) !important;
        border-radius:22px !important;
        box-shadow:
            0 1px 2px rgba(16,24,40,.04),
            0 16px 34px -18px rgba(60,70,120,.20),
            inset 0 1px 0 rgba(255,255,255,.75) !important;
        transition:transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease !important;
    }
    .dash a.bg-white:hover, .dash .bg-white.hover\:shadow-md:hover{
        transform:translateY(-3px);
        box-shadow:
            0 1px 2px rgba(16,24,40,.05),
            0 26px 50px -20px rgba(60,70,120,.3),
            inset 0 1px 0 rgba(255,255,255,.8) !important;
    }
    /* Welcome banner → gradient transparan (glass), tidak solid */
    .dash .bg-gradient-to-r.from-indigo-600{
        background:linear-gradient(135deg, rgba(99,102,241,.82), rgba(134,142,255,.66)) !important;
        backdrop-filter:blur(10px) saturate(140%); -webkit-backdrop-filter:blur(10px) saturate(140%);
        border:1px solid rgba(255,255,255,.4) !important;
        border-radius:26px !important;
        box-shadow:0 24px 50px -20px rgba(91,94,246,.45), inset 0 1px 0 rgba(255,255,255,.4) !important;
    }
    /* badge sudut kartu sedikit lebih halus */
    .dash .rounded-bl-lg{ backdrop-filter:blur(4px); }

    /* ===== HP: kartu lebih ringkas, muat 2 kolom ===== */
    @media (max-width:639px){
        .dash .cards-tight > *{ padding:0.85rem !important; border-radius:16px !important; }
        .dash .cards-tight .text-3xl{ font-size:1.15rem !important; line-height:1.2 !important; }
        .dash .cards-tight .text-xl{ font-size:1.05rem !important; line-height:1.2 !important; }
        .dash .cards-tight .uppercase{ font-size:9.5px !important; letter-spacing:.02em !important; }
        .dash .cards-tight .p-2{ display:none !important; }            /* sembunyikan ikon box di HP */
        .dash .cards-tight .w-24.h-24,
        .dash .cards-tight .w-16.h-16{ width:3.5rem !important; height:3.5rem !important; } /* perkecil dekorasi */
        .dash .cards-tight .text-\[11px\]{ font-size:10px !important; }

    }
    /* Arus Kas: kartu interaktif 1-card */
    [x-cloak]{ display:none !important; }
    /* canvas donut tak boleh melebihi kolomnya (cegah overflow/scroll kanan) */
    .dash .cashflow canvas{ max-width:100% !important; }
    .dash .cashflow{ overflow:hidden; }
    /* animasi menyusutnya donut (pakai width inline, bukan class Tailwind dinamis) */
    .dash .cashflow .donutcol{ transition:width .55s cubic-bezier(.22,1,.36,1); }

    /* ===== Statistik: kartu seragam & aesthetic ===== */
    .dash .statgrid > *{
        min-height:112px;
        display:flex; flex-direction:column; justify-content:space-between;
        padding:1.05rem 1rem !important;
    }
    .dash .statgrid svg{ display:none !important; }              /* ikon inline yg tak konsisten dihilangkan */
    .dash .statgrid .flex{ display:block !important; }           /* number tak perlu flex (ikon hilang) */
    /* badge sudut → chip melayang rapi (warna kategori dipertahankan) */
    .dash .statgrid .rounded-bl-lg{
        top:.6rem !important; right:.6rem !important; left:auto !important; bottom:auto !important;
        border-radius:999px !important;
        padding:.16rem .55rem !important;
        font-size:8.5px !important; letter-spacing:.04em !important; line-height:1.5 !important;
        box-shadow:0 1px 2px rgba(16,24,40,.06);
    }
    /* hierarki angka & label konsisten */
    .dash .statgrid .text-2xl,
    .dash .statgrid .text-xl{ font-size:1.5rem !important; line-height:1.15 !important; margin-top:.1rem !important; }
    .dash .statgrid .text-lg{ font-size:1.15rem !important; line-height:1.2 !important; margin-top:.1rem !important; }
    @media (max-width:639px){
        .dash .statgrid > *{ min-height:96px; padding:.85rem !important; }
        .dash .statgrid .text-2xl, .dash .statgrid .text-xl{ font-size:1.3rem !important; }
        .dash .statgrid .text-lg{ font-size:1.02rem !important; }
    }
</style>
@endpush

@section('content')
    <div class="space-y-6 dash">

        {{-- 1. Welcome Banner & Filter --}}
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-xl p-6 text-white flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
            <div>
                <h2 class="text-xl font-bold">Selamat datang, {{ Auth::user()->name }}!</h2>
                <p class="text-indigo-200 text-sm mt-1">
                    {{ now()->isoFormat('dddd, D MMMM Y') }} · Role:
                    <span class="capitalize font-medium">{{ Auth::user()->getRoleNames()->first() }}</span>
                </p>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="shrink-0 flex items-center gap-3 flex-wrap md:justify-end">
                @if(request('store_date_filter'))<input type="hidden" name="store_date_filter" value="{{ request('store_date_filter') }}">@endif
                @if(request('top_date_filter'))<input type="hidden" name="top_date_filter" value="{{ request('top_date_filter') }}">@endif
                
                <!-- Filter Gudang Baru -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                    <label for="warehouse_id" class="text-xs font-medium text-white/90 whitespace-nowrap hidden sm:block">Gudang:</label>
                    <div class="relative min-w-[160px]">
                        <select name="warehouse_id" id="warehouse_id" onchange="this.form.submit()" class="appearance-none w-full rounded-lg border border-white/20 bg-white/10 backdrop-blur-md px-3 py-2 pr-8 text-sm text-white shadow-sm transition focus:border-white focus:ring-2 focus:ring-white/30 outline-none cursor-pointer hover:bg-white/15">
                            <option value="" class="text-gray-900">Semua Gudang</option>
                            <!-- Pastikan variabel $warehouses dikirim dari DashboardController -->
                            @if(isset($warehouses))
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" class="text-gray-900" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>  
                </div>

                <!-- Filter Toko -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                    <label for="store_id" class="text-xs font-medium text-white/90 whitespace-nowrap hidden sm:block">Toko:</label>
                    <div class="relative min-w-[160px]">
                        <select name="store_id" id="store_id" onchange="this.form.submit()" class="appearance-none w-full rounded-lg border border-white/20 bg-white/10 backdrop-blur-md px-3 py-2 pr-8 text-sm text-white shadow-sm transition focus:border-white focus:ring-2 focus:ring-white/30 outline-none cursor-pointer hover:bg-white/15">
                            <option value="" class="text-gray-900">Semua Toko</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" class="text-gray-900" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>  
                </div>
            </form>
        </div>

        {{-- Pelanggan Jatuh Tempo Card --}}
        @if(isset($approachingDueSales) && $approachingDueSales->count() > 0)
        <div x-data="{ open: window.innerWidth >= 768 }" class="bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden">
            <button type="button" @click="open = !open" class="w-full bg-red-50/50 border-b border-red-100 px-5 sm:px-6 py-4 flex items-center justify-between gap-3 text-left">
                <h4 class="text-sm font-bold text-red-800 flex items-center gap-2 min-w-0">
                    <svg class="w-5 h-5 text-red-600 animate-pulse shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="truncate">Peringatan Jatuh Tempo</span>
                    <span class="shrink-0 text-xs bg-red-600 text-white px-2 py-0.5 rounded-full font-bold">{{ $approachingDueSales->count() }}</span>
                </h4>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="hidden md:inline text-xs bg-red-100 text-red-800 px-2.5 py-0.5 rounded-full font-bold">Mendekati / Lewat Tempo</span>
                    <svg class="w-5 h-5 text-red-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-transition.opacity class="divide-y divide-gray-100" style="display:none">
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

        {{-- 2. FINANCIAL & EXECUTIVE SUMMARY (KHUSUS SUPERADMIN / OWNER) --}}
        @hasanyrole('superadmin|owner')
        @php
            $incomeTotal = $monthSales ?? 0;
            $expenseTotal = $totalExpense ?? 0; 
            $profitTotal = $incomeTotal - $expenseTotal;
        @endphp
        @if(auth()->user()->hasAnyRole(['superadmin', 'owner']))
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">Ringkasan Reward & Dividen Tahun {{ now()->year }}</h3>
                <span class="text-xs font-medium bg-gray-100 text-gray-500 px-3 py-1 rounded-full uppercase tracking-widest">Confidential</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-2 gap-3 sm:gap-6 cards-tight">
                {{-- Card Reward Toko --}}
                <div class="bg-white rounded-2xl shadow-sm border border-indigo-100 p-6 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 rounded-full opacity-50"></div>
                    <p class="text-xs font-semibold text-indigo-400 uppercase">Total Akumulasi Reward Toko</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900">Rp {{ number_format($rewardToko, 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        <p class="text-[11px] text-gray-500">Dihitung dari {{ number_format($totalItemsSold) }} pcs produk terjual.</p>
                    </div>
                </div>

                {{-- Card Dividen Owner --}}
                <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full opacity-50"></div>
                    <p class="text-xs font-semibold text-emerald-400 uppercase">Total Dividen Owner</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-emerald-600">Rp {{ number_format($rewardOwner, 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <p class="text-[11px] text-gray-500">Alokasi keuntungan bersih pribadi tahun {{ now()->year }}.</p>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <div class="mb-2">
            <h3 class="text-lg font-bold text-gray-800 border-l-4 border-indigo-500 pl-3">Ringkasan Finansial Eksekutif</h3>
        </div>

        {{-- Daily Financial Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 cards-tight">
            {{-- Card Pemasukan Hari Ini --}}
            <a href="{{ route('finance.index') }}" class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm hover:border-blue-400 transition-all hover:shadow-md group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <p class="text-xs font-bold text-blue-500 uppercase tracking-wider mb-1 relative z-10">Pemasukan Hari Ini</p>
                <div class="flex items-center justify-between relative z-10">
                    <h4 class="text-xl font-black text-gray-900">Rp {{ number_format($todaySales, 0, ',', '.') }}</h4>
                    <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </a>

            {{-- Card Pengeluaran Hari Ini --}}
            <a href="{{ route('finance.index') }}" class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm hover:border-red-400 transition-all hover:shadow-md group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-red-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <p class="text-xs font-bold text-red-500 uppercase tracking-wider mb-1 relative z-10">Pengeluaran Hari Ini</p>
                <div class="flex items-center justify-between relative z-10">
                    <h4 class="text-xl font-black text-gray-900">Rp {{ number_format($todayExpense, 0, ',', '.') }}</h4>
                    <div class="p-2 bg-red-100 rounded-lg text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                    </div>
                </div>
            </a>

            {{-- Card Keuntungan Hari Ini --}}
            <a href="{{ route('finance.index') }}" class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm hover:border-emerald-400 transition-all hover:shadow-md group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1 relative z-10">Keuntungan Hari Ini</p>
                <div class="flex items-center justify-between relative z-10">
                    <h4 class="text-xl font-black text-gray-900">Rp {{ number_format($todayProfit, 0, ',', '.') }}</h4>
                    <div class="p-2 bg-emerald-100 rounded-lg text-emerald-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                </div>
            </a>

            {{-- Card Penjualan Hari Ini (Trx) --}}
            <a href="{{ route('finance.index') }}" class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm hover:border-amber-400 transition-all hover:shadow-md group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-amber-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <p class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1 relative z-10">Penjualan Hari Ini</p>
                <div class="flex items-center justify-between relative z-10">
                    <h4 class="text-xl font-black text-gray-900">{{ number_format($todayOrders) }} Trx</h4>
                    <div class="p-2 bg-amber-100 rounded-lg text-amber-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                    </div>
                </div>
            </a>
        </div>

        {{-- Arus Kas — SATU kartu interaktif: klik segmen → detail geser dari kanan, bisa ditutup --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6 cashflow overflow-hidden"
             x-data="{
                active: null,
                labels: @js(['Pendapatan','Pengeluaran','Laba Bersih']),
                values: @js([(int) $incomeTotal, (int) $expenseTotal, (int) $profitTotal]),
                colors: @js(['#3b82f6','#ef4444','#10b981']),
                bg:     @js(['#eff6ff','#fef2f2','#ecfdf5']),
                descs:  @js([
                    'Total pendapatan kotor dari penjualan keseluruhan berdasarkan filter.',
                    'Total biaya pengeluaran (operasional, dll) berdasarkan filter gudang/toko.',
                    'Sisa keuntungan bersih (Pendapatan dikurangi Pengeluaran).'
                ]),
                icons: @js([
                    '<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z\'/>',
                    '<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6\'/>',
                    '<path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6\'/>'
                ])
             }"
             @cashflow-select.window="active = $event.detail.idx">

            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-semibold text-gray-800">📊 Arus Kas (Bulan Ini)</h3>
                <button type="button" x-show="active!==null" x-cloak @click="active=null"
                        class="inline-flex items-center gap-1 text-xs font-medium text-gray-400 hover:text-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Tutup
                </button>
            </div>
            <p x-show="active===null" class="text-xs text-gray-500 mb-3">Klik salah satu bagian grafik untuk melihat detailnya.</p>

            <div class="flex items-center gap-4">
                {{-- Donut --}}
                <div class="donutcol min-w-0" style="width:100%" :style="active!==null ? 'width:40%' : 'width:100%'">
                    <div class="relative h-44 sm:h-52 w-full flex items-center justify-center cursor-pointer">
                        <canvas id="financeDonutChart"></canvas>
                    </div>
                    {{-- legenda custom (hilang saat ada segmen aktif) --}}
                    <div x-show="active===null" x-transition.opacity class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 mt-3">
                        <template x-for="(l,i) in labels" :key="i">
                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                                <span class="w-2.5 h-2.5 rounded-full" :style="`background:${colors[i]}`"></span>
                                <span x-text="l"></span>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Detail — muncul & geser dari kanan saat segmen diklik --}}
                <div x-show="active!==null" x-cloak
                     x-transition:enter="transition ease-out duration-500 delay-150"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     class="flex-1 min-w-0 rounded-2xl p-4 flex flex-col justify-center text-left"
                     :style="active!==null ? ('background:' + bg[active]) : ''">
                    <div class="w-9 h-9 rounded-full bg-white flex items-center justify-center mb-2.5 shadow-sm shrink-0" :style="active!==null ? ('color:' + colors[active]) : ''">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-html="active!==null ? icons[active] : ''"></svg>
                    </div>
                    <p class="text-[10px] font-bold uppercase tracking-wide text-gray-500" x-text="active!==null ? labels[active] : ''"></p>
                    <p class="text-lg sm:text-2xl font-black mt-0.5 break-words leading-tight" :style="active!==null ? ('color:' + colors[active]) : ''" x-text="active!==null ? ('Rp ' + values[active].toLocaleString('id')) : ''"></p>
                    <p class="text-[11px] text-gray-500 mt-2 leading-relaxed" x-text="active!==null ? descs[active] : ''"></p>
                </div>
            </div>
        </div>
        @endhasanyrole

        {{-- 3. STATISTIK SISTEM & MODUL LABEL --}}
        <div class="mb-2 mt-8">
            <h3 class="text-lg font-bold text-gray-800 border-l-4 border-purple-500 pl-3">Statistik Sistem & Modul</h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 statgrid">
            
            <a href="{{ route('master.brands.index') }}" class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm hover:border-purple-300 transition-colors relative overflow-hidden group">
                <span class="absolute top-0 right-0 bg-gray-100 text-gray-500 text-[9px] font-bold px-2 py-1 rounded-bl-lg group-hover:bg-purple-100 group-hover:text-purple-700 transition-colors">MASTER DATA</span>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-2">Brand Aktif</p>
                <div class="flex items-end justify-between mt-1">
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['brands'] }}</p>
                    <svg class="w-6 h-6 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                </div>
            </a>

            <a href="{{ route('products.index') }}" class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm hover:border-indigo-300 transition-colors relative overflow-hidden group">
                <span class="absolute top-0 right-0 bg-gray-100 text-gray-500 text-[9px] font-bold px-2 py-1 rounded-bl-lg group-hover:bg-indigo-100 group-hover:text-indigo-700 transition-colors">KATALOG</span>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-2">Produk Aktif</p>
                <div class="flex items-end justify-between mt-1">
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['products'] }}</p>
                    <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </div>
            </a>

            <a href="{{ route('labels.picker') }}" class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm hover:border-teal-300 transition-colors relative overflow-hidden group">
                <span class="absolute top-0 right-0 bg-gray-100 text-gray-500 text-[9px] font-bold px-2 py-1 rounded-bl-lg group-hover:bg-teal-100 group-hover:text-teal-700 transition-colors">KATALOG</span>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-2">Total SKU Varian</p>
                <div class="flex items-end justify-between mt-1">
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['variants'] }}</p>
                    <svg class="w-6 h-6 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                </div>
            </a>

            <a href="{{ route('master.warehouses.index') }}" class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm hover:border-blue-300 transition-colors relative overflow-hidden group">
                <span class="absolute top-0 right-0 bg-gray-100 text-gray-500 text-[9px] font-bold px-2 py-1 rounded-bl-lg group-hover:bg-blue-100 group-hover:text-blue-700 transition-colors">MASTER DATA</span>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-2">Titik Gudang</p>
                <div class="flex items-end justify-between mt-1">
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['warehouses'] }}</p>
                    <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                </div>
            </a>

            <a href="{{ route('master.stores.index') }}" class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm hover:border-green-300 transition-colors relative overflow-hidden group">
                <span class="absolute top-0 right-0 bg-gray-100 text-gray-500 text-[9px] font-bold px-2 py-1 rounded-bl-lg group-hover:bg-green-100 group-hover:text-green-700 transition-colors">MASTER DATA</span>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-2">Cabang Toko</p>
                <div class="flex items-end justify-between mt-1">
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['stores'] }}</p>
                    <svg class="w-6 h-6 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                </div>
            </a>
        </div>

        {{-- 4. INVENTORY & RETURNS --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 statgrid">
            <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm relative overflow-hidden">
                <span class="absolute top-0 right-0 bg-blue-50 text-blue-600 text-[9px] font-bold px-2 py-1 rounded-bl-lg">GUDANG</span>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide mt-2">Valuasi Aset Gudang</p>
                <p class="text-lg font-bold text-gray-900 mt-1">Rp {{ number_format($warehouseStockValue, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm relative overflow-hidden">
                <span class="absolute top-0 right-0 bg-green-50 text-green-600 text-[9px] font-bold px-2 py-1 rounded-bl-lg">TOKO</span>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide mt-2">Valuasi Aset Toko</p>
                <p class="text-lg font-bold text-gray-900 mt-1">Rp {{ number_format($storeStockValue, 0, ',', '.') }}</p>
            </div>
            <a href="{{ route('returns.customer.index') }}" class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm hover:border-orange-300 transition-colors relative overflow-hidden group">
                <span class="absolute top-0 right-0 bg-orange-50 text-orange-600 text-[9px] font-bold px-2 py-1 rounded-bl-lg">RETUR</span>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide mt-2">Retur (Bulan Ini)</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ $monthReturns }}</p>
            </a>
            <a href="{{ route('returns.customer.index') }}" class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm hover:border-red-300 transition-colors relative overflow-hidden group">
                <span class="absolute top-0 right-0 bg-red-50 text-red-600 text-[9px] font-bold px-2 py-1 rounded-bl-lg">RETUR</span>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide mt-2">Retur Pending</p>
                <p class="text-xl font-bold {{ $pendingReturns > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ $pendingReturns }}</p>
            </a>
        </div>

        {{-- Latest Stock Opname Card --}}
        @if(isset($latestOpname) && $latestOpname)
        <div x-data="{ openOpnameModal: false }" x-init="$watch('openOpnameModal', val => document.body.style.overflow = val ? 'hidden' : '')">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between group cursor-pointer hover:border-indigo-300 transition-colors gap-4" @click="openOpnameModal = true">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm">Stock Opname Terbaru</h3>
                        <p class="text-xs text-gray-500 mt-1">No. <span class="font-mono text-indigo-600 font-semibold">{{ $latestOpname->opname_no }}</span> · {{ $latestOpname->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <div class="text-left sm:text-right w-full sm:w-auto flex justify-between sm:block items-center">
                    @php
                        $statusColors = ['draft'=>'bg-gray-100 text-gray-600','submitted'=>'bg-blue-100 text-blue-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700'];
                        $statusLabels = ['draft'=>'Draft','submitted'=>'Disubmit','approved'=>'Disetujui','rejected'=>'Ditolak'];
                    @endphp
                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold {{ $statusColors[$latestOpname->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $statusLabels[$latestOpname->status] ?? $latestOpname->status }}</span>
                    <p class="text-[10px] text-gray-400 mt-1 sm:mt-1 group-hover:text-indigo-500">Klik untuk detail &rarr;</p>
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
                                        <td class="px-6 py-3 font-mono text-xs">{{ $v ? $v?->sku : '-' }}</td>
                                        <td class="px-6 py-3 text-xs">
                                            @if($v)
                                                {{ $v?->product?->name ?? '-' }} · {{ $v?->color?->name ?? '-' }}/{{ $v?->size?->name ?? '-' }}
                                            @else
                                                <span class="text-red-500 italic">Varian tidak ditemukan</span>
                                            @endif
                                        </td>
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
                </div>
            </template>
        </div>
        @endif

        {{-- 5. QUICK ACCESS & TOP SELLING --}}
        <div class="flex flex-col lg:flex-row gap-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:w-1/2 w-full">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">🚀 Akses Menu Cepat</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    @php
                        $quickLinks = [
                            ['route' => 'catalog.index', 'label' => 'Katalog', 'color' => 'bg-indigo-50 text-indigo-700', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                            ['route' => 'products.index', 'label' => 'Produk', 'color' => 'bg-purple-50 text-purple-700', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                            ['route' => 'warehouse.stock.index', 'label' => 'Stok Gudang', 'color' => 'bg-blue-50 text-blue-700', 'icon' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'],
                            ['route' => 'warehouse.shipments.index', 'label' => 'Pengiriman', 'color' => 'bg-cyan-50 text-cyan-700', 'icon' => 'M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20'],
                            ['route' => 'transfers.index', 'label' => 'Transfer', 'color' => 'bg-orange-50 text-orange-700', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                            ['route' => 'returns.customer.index', 'label' => 'Retur', 'color' => 'bg-red-50 text-red-700', 'icon' => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'],
                            ['route' => 'reports.index', 'label' => 'Laporan', 'color' => 'bg-yellow-50 text-yellow-700', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ]
                    @endphp
                    @foreach($quickLinks as $link)
                        <a href="{{ route($link['route']) }}" class="flex flex-col items-center gap-2 p-3 rounded-xl {{ $link['color'] }} hover:opacity-80 transition-opacity text-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}" /></svg>
                            <span class="text-xs font-medium leading-tight">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:w-1/2 w-full flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">🏆 Top Selling Produk</h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
                        @if(request('warehouse_id'))<input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">@endif
                        @if(request('store_id'))<input type="hidden" name="store_id" value="{{ request('store_id') }}">@endif
                        @if(request('store_date_filter'))<input type="hidden" name="store_date_filter" value="{{ request('store_date_filter') }}">@endif
                        
                        <select name="top_date_filter" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-900 text-[11px] rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block py-1 pl-2 pr-8 outline-none cursor-pointer">
                            <option value="today" {{ $topDateFilter === 'today' ? 'selected' : '' }}>Hari Ini</option>
                            <option value="7_days" {{ $topDateFilter === '7_days' ? 'selected' : '' }}>7 Hari</option>
                            <option value="30_days" {{ $topDateFilter === '30_days' ? 'selected' : '' }}>30 Hari</option>
                            <option value="this_month" {{ $topDateFilter === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                        </select>
                    </form>
                </div>
                <div class="space-y-2 flex-1 max-h-[280px] overflow-y-auto pr-2">
                    @forelse($topProducts as $idx => $product)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <span class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold {{ $idx < 3 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600' }}">{{ $idx + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $product->product_name }}</p>
                                <p class="text-xs text-gray-400">Rp {{ number_format($product->total_revenue, 0, ',', '.') }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold text-indigo-600">{{ number_format($product->total_qty) }} pcs</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-8">Belum ada data penjualan</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 6. LAINNYA: ANALITIK PENJUALAN TOKO DLL --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">🏪 Penjualan per Toko</h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                        @if(request('warehouse_id'))<input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">@endif
                        @if(request('store_id'))<input type="hidden" name="store_id" value="{{ request('store_id') }}">@endif
                        @if(request('top_date_filter'))<input type="hidden" name="top_date_filter" value="{{ request('top_date_filter') }}">@endif
                        <select name="store_date_filter" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-900 text-[11px] rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block py-1 pl-2 pr-8 outline-none cursor-pointer">
                            <option value="today" {{ $storeDateFilter === 'today' ? 'selected' : '' }}>Hari Ini</option>
                            <option value="7_days" {{ $storeDateFilter === '7_days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                            <option value="30_days" {{ $storeDateFilter === '30_days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                            <option value="this_month" {{ $storeDateFilter === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                        </select>
                    </form>
                </div>
                <div class="relative h-[300px] w-full"><canvas id="salesPerStore"></canvas></div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col">
                <div class="flex items-center justify-between mb-4"><h3 class="text-sm font-semibold text-gray-800">🏪 Performa Toko</h3></div>
                <div class="relative flex-1 min-h-[250px] w-full flex items-center justify-center"><canvas id="storeChart"></canvas></div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:col-span-2">
                <div class="flex items-center justify-between mb-4"><h3 class="text-sm font-semibold text-gray-800">📈 Tren Penjualan (30 Hari)</h3></div>
                <div class="relative h-[300px] w-full"><canvas id="revenueChart"></canvas></div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col">
                <div class="flex items-center justify-between mb-4"><h3 class="text-sm font-semibold text-gray-800">💳 Metode Pembayaran</h3></div>
                <div class="relative flex-1 min-h-[250px] w-full flex items-center justify-center"><canvas id="paymentChart"></canvas></div>
            </div>
        </div>

        {{-- 7. SYSTEM INFO --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-800 mb-3">Informasi Sistem</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-gray-500">
                <div><span class="font-medium text-gray-700">Aplikasi</span><br>SevenKey ERP v1.0</div>
                <div><span class="font-medium text-gray-700">Laravel</span><br>{{ app()->version() }}</div>
                <div><span class="font-medium text-gray-700">PHP</span><br>{{ PHP_VERSION }}</div>
                <div><span class="font-medium text-gray-700">Login Terakhir</span><br>{{ Auth::user()->last_login_at?->diffForHumans() ?? 'Baru pertama kali' }}</div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── Interactive Finance Donut (NEW) ──
            @hasanyrole('superadmin|owner')
            const ctxFinance = document.getElementById('financeDonutChart');
            if (ctxFinance) {
                const fLabels = ['Pendapatan', 'Pengeluaran', 'Laba Bersih'];
                const fData = [{{ $incomeTotal }}, {{ $expenseTotal }}, {{ $profitTotal }}];
                const fColors = ['#3b82f6', '#ef4444', '#10b981']; // Biru, Merah, Hijau
                const fBgColors = ['#eff6ff', '#fef2f2', '#ecfdf5']; 
                const fIcons = [
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />',
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />'
                ];
                const fDesc = [
                    'Total pendapatan kotor dari penjualan keseluruhan berdasarkan filter.',
                    'Total biaya pengeluaran (operasional, dll) berdasarkan filter gudang/toko.',
                    'Sisa keuntungan bersih (Pendapatan dikurangi Pengeluaran).'
                ];

                window.financeChart = new Chart(ctxFinance, {
                    type: 'doughnut',
                    data: {
                        labels: fLabels,
                        datasets: [{
                            data: fData,
                            backgroundColor: fColors,
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        cutout: '70%',
                        animation: { duration: 700 },
                        transitions: { resize: { animation: { duration: 0 } } }, // resize TANPA animasi → tak flicker
                        plugins: { legend: { display: false } },
                        onClick: function(event, elements) {
                            if (elements.length > 0) {
                                window.dispatchEvent(new CustomEvent('cashflow-select', { detail: { idx: elements[0].index } }));
                            }
                        }
                    }
                });
            }
            @endhasanyrole

            // ── Sales by Store (BAR) ──
            const ctxSalesStore = document.getElementById('salesPerStore');
            if (ctxSalesStore) {
                const barStoreLabels = @json($salesByStore->pluck('store_name'));
                const barStoreData = @json($salesByStore->pluck('total_revenue'));
                const barStoreColors = ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd', '#818cf8', '#4f46e5'];

                new Chart(ctxSalesStore, {
                    type: 'bar',
                    data: {
                        labels: barStoreLabels,
                        datasets: [{
                            label: 'Revenue',
                            data: barStoreData,
                            backgroundColor: barStoreLabels.map((_, i) => barStoreColors[i % barStoreColors.length]),
                            borderRadius: 4,
                            maxBarThickness: 48
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { callback: v => 'Rp ' + (v / 1000).toLocaleString('id') + 'k' }, grid: { color: '#f3f4f6' } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }

            // ── Revenue Chart (LINE) ──
            const ctxRev = document.getElementById('revenueChart');
            if (ctxRev) {
                const revenueLabels = @json($chartLabels);
                const revenueData = @json($chartRevenue);
                const ordersData = @json($chartOrders);

                new Chart(ctxRev, {
                    type: 'line',
                    data: {
                        labels: revenueLabels,
                        datasets: [
                            {
                                label: 'Pendapatan', data: revenueData, borderColor: '#4f46e5',
                                backgroundColor: 'rgba(79, 70, 229, 0.1)', borderWidth: 2, fill: true,
                                tension: 0.4, pointRadius: 0, pointHoverRadius: 4, yAxisID: 'y'
                            },
                            {
                                label: 'Jumlah Order', data: ordersData, borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)', borderWidth: 2, borderDash: [5, 5],
                                fill: false, tension: 0.4, pointRadius: 0, pointHoverRadius: 4, yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        maintainAspectRatio: false, responsive: true,
                        plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                        scales: {
                            x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                            y: { type: 'linear', display: true, position: 'left', grid: { color: '#f3f4f6' }, beginAtZero: true, ticks: { callback: v => 'Rp ' + (v / 1000).toLocaleString('id') + 'k' } },
                            y1: { type: 'linear', display: true, position: 'right', grid: { display: false }, beginAtZero: true, ticks: { callback: v => v + ' trx' } }
                        },
                        interaction: { mode: 'nearest', axis: 'x', intersect: false }
                    }
                });
            }

            // ── Sales by Store (DOUGHNUT) ──
            const ctxStorePie = document.getElementById('storeChart');
            if (ctxStorePie) {
                const doughnutStoreLabels = @json($salesByStore->pluck('store_name'));
                const doughnutStoreData = @json($salesByStore->pluck('total_revenue'));
                const doughnutStoreColors = ['#4f46e5', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308'];

                new Chart(ctxStorePie, {
                    type: 'doughnut',
                    data: { labels: doughnutStoreLabels, datasets: [{ data: doughnutStoreData, backgroundColor: doughnutStoreColors, borderWidth: 0, hoverOffset: 4 }] },
                    options: { maintainAspectRatio: false, responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 15, font: { size: 11 } } }, tooltip: { callbacks: { label: function (context) { return ' Rp ' + context.raw.toLocaleString('id'); } } } } }
                });
            }

            // ── Payment Method (DOUGHNUT) ──
            const ctxPayPie = document.getElementById('paymentChart');
            if (ctxPayPie) {
                const paymentLabels = @json($paymentDistribution->pluck('method_name'));
                const paymentData = @json($paymentDistribution->pluck('total_amount'));
                const paymentColors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b'];

                new Chart(ctxPayPie, {
                    type: 'doughnut',
                    data: { labels: paymentLabels.map(l => l ? l.toUpperCase() : 'UNKNOWN'), datasets: [{ data: paymentData, backgroundColor: paymentColors, borderWidth: 0, hoverOffset: 4 }] },
                    options: { maintainAspectRatio: false, responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 15, font: { size: 11 } } }, tooltip: { callbacks: { label: function (context) { return ' Rp ' + context.raw.toLocaleString('id'); } } } } }
                });
            }

        });
    </script>
@endpush