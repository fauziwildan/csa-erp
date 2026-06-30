@php
    $isCashier = auth()->user()->hasRole('kasir');
    $isStoreHead = auth()->user()->hasRole('kepala toko'); // kepala toko: tanpa sidebar, navigasi via menu modul di dashboard
    $isKiosk = request()->query('kiosk') == 1; // Deteksi jika ada ?kiosk=1 di URL

    // Gabungkan kondisi sembunyikan navigasi
    $hideNavigation = $isCashier || $isKiosk || $isStoreHead;

    // Sidebar default TERTUTUP di layar kasir/POS (biar lega), selain itu terbuka di desktop
    $collapseSidebar = $isCashier || request()->routeIs('pos.index');
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SevenKey ERP</title>
    @include('partials.favicons')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* ===== SevenKey premium theme (global shell) ===== */
        body.app-shell{
            font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
            background:
                radial-gradient(58% 50% at 6% -6%,    rgba(124,130,255,.24), transparent 60%),
                radial-gradient(54% 50% at 99% -2%,   rgba(130,172,255,.22), transparent 60%),
                radial-gradient(68% 64% at 98% 106%,  rgba(165,150,255,.22), transparent 62%),
                radial-gradient(68% 64% at -4% 104%,  rgba(150,180,255,.18), transparent 60%),
                linear-gradient(180deg,#EEF1FF 0%,#E9ECFF 55%,#ECEAFF 100%);
            background-attachment:fixed;
        }
        /* glass topbar */
        .app-shell .topbar{
            background:rgba(255,255,255,.55) !important;
            backdrop-filter:blur(18px) saturate(150%); -webkit-backdrop-filter:blur(18px) saturate(150%);
            border-bottom:1px solid rgba(255,255,255,.6) !important;
            box-shadow:0 1px 0 rgba(255,255,255,.5), 0 10px 30px -20px rgba(60,70,120,.4) !important;
        }
        /* ===== glass sidebar (terang/transparan) ===== */
        .sidebar-glass{
            background:rgba(255,255,255,.58) !important;
            backdrop-filter:blur(28px) saturate(160%); -webkit-backdrop-filter:blur(28px) saturate(160%);
            border-right:1px solid rgba(255,255,255,.55);
            box-shadow:0 24px 70px -34px rgba(60,70,120,.5);
        }
        .sidebar-glass .text-white{ color:#1F2937 !important; }
        .sidebar-glass .text-gray-300{ color:#475467 !important; }
        .sidebar-glass .text-gray-400{ color:#5B6576 !important; }
        /* submenu (border-l) lebih jelas saat hover */
        .sidebar-glass .hover\:text-white:hover{ color:#5B5EF6 !important; }
        .sidebar-glass .bg-gray-950{ background:transparent !important; }
        .sidebar-glass .bg-gray-800{ background:rgba(91,94,246,.10) !important; }
        .sidebar-glass .border-gray-800,
        .sidebar-glass .border-gray-700{ border-color:rgba(16,24,40,.08) !important; }
        .sidebar-glass .hover\:bg-gray-800:hover{ background:rgba(91,94,246,.09) !important; }
        .sidebar-glass .hover\:text-white:hover{ color:#5B5EF6 !important; }
        /* active pill = gradient tema, teks putih */
        .sidebar-glass a.bg-indigo-600,
        .sidebar-glass button.bg-indigo-600{
            background:linear-gradient(135deg,#5B5EF6,#7D82FF) !important;
            color:#fff !important;
            box-shadow:0 8px 18px -6px rgba(91,94,246,.5);
        }
        .sidebar-glass a.bg-indigo-600 *,
        .sidebar-glass button.bg-indigo-600 *{ color:#fff !important; }
        /* ikon 7K & avatar tetap aksen gradient */
        .sidebar-glass .bg-indigo-500{ background:linear-gradient(135deg,#5B5EF6,#7D82FF) !important; color:#fff !important; }
        /* halus: thumbnail scrollbar */
        .app-shell ::-webkit-scrollbar{ width:10px; height:10px; }
        .app-shell ::-webkit-scrollbar-thumb{ background:rgba(91,94,246,.22); border-radius:99px; border:3px solid transparent; background-clip:content-box; }
        .app-shell ::-webkit-scrollbar-thumb:hover{ background:rgba(91,94,246,.4); background-clip:content-box; }

        /* ================= DASHBOARD THEME (semua role: .dash wrapper) ================= */
        .dash .bg-white{
            background:linear-gradient(160deg, rgba(255,255,255,.78), rgba(255,255,255,.56)) !important;
            backdrop-filter:blur(18px) saturate(140%); -webkit-backdrop-filter:blur(18px) saturate(140%);
            border:1px solid rgba(255,255,255,.6) !important;
            border-radius:22px !important;
            box-shadow:0 1px 2px rgba(16,24,40,.04), 0 16px 34px -18px rgba(60,70,120,.20), inset 0 1px 0 rgba(255,255,255,.75) !important;
            transition:transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease !important;
        }
        .dash a.bg-white:hover, .dash .bg-white.hover\:shadow-md:hover{
            transform:translateY(-3px);
            box-shadow:0 1px 2px rgba(16,24,40,.05), 0 26px 50px -20px rgba(60,70,120,.3), inset 0 1px 0 rgba(255,255,255,.8) !important;
        }
        .dash .bg-gradient-to-r.from-indigo-600{
            background:linear-gradient(135deg, rgba(99,102,241,.82), rgba(134,142,255,.66)) !important;
            backdrop-filter:blur(10px) saturate(140%); -webkit-backdrop-filter:blur(10px) saturate(140%);
            border:1px solid rgba(255,255,255,.4) !important;
            border-radius:26px !important;
            box-shadow:0 24px 50px -20px rgba(91,94,246,.45), inset 0 1px 0 rgba(255,255,255,.4) !important;
        }
        .dash .rounded-bl-lg{ backdrop-filter:blur(4px); }
        [x-cloak]{ display:none !important; }
        .dash .cashflow canvas{ max-width:100% !important; }
        .dash .cashflow{ overflow:hidden; }
        .dash .cashflow .donutcol{ transition:width .55s cubic-bezier(.22,1,.36,1); }
        /* Statistik: kartu seragam */
        .dash .statgrid > *{ min-height:112px; display:flex; flex-direction:column; justify-content:space-between; padding:1.05rem 1rem !important; }
        .dash .statgrid svg{ display:none !important; }
        .dash .statgrid .flex{ display:block !important; }
        .dash .statgrid .rounded-bl-lg{ top:.6rem !important; right:.6rem !important; left:auto !important; bottom:auto !important; border-radius:999px !important; padding:.16rem .55rem !important; font-size:8.5px !important; letter-spacing:.04em !important; line-height:1.5 !important; box-shadow:0 1px 2px rgba(16,24,40,.06); }
        .dash .statgrid .text-2xl, .dash .statgrid .text-xl{ font-size:1.5rem !important; line-height:1.15 !important; margin-top:.1rem !important; }
        .dash .statgrid .text-lg{ font-size:1.15rem !important; line-height:1.2 !important; margin-top:.1rem !important; }
        @media (max-width:639px){
            .dash .cards-tight > *{ padding:0.85rem !important; border-radius:16px !important; }
            .dash .cards-tight .text-3xl{ font-size:1.15rem !important; line-height:1.2 !important; }
            .dash .cards-tight .text-xl{ font-size:1.05rem !important; line-height:1.2 !important; }
            .dash .cards-tight .uppercase{ font-size:9.5px !important; letter-spacing:.02em !important; }
            .dash .cards-tight .p-2{ display:none !important; }
            .dash .cards-tight .w-24.h-24, .dash .cards-tight .w-16.h-16{ width:3.5rem !important; height:3.5rem !important; }
            .dash .cards-tight .text-\[11px\]{ font-size:10px !important; }
            .dash .statgrid > *{ min-height:96px; padding:.85rem !important; }
            .dash .statgrid .text-2xl, .dash .statgrid .text-xl{ font-size:1.3rem !important; }
            .dash .statgrid .text-lg{ font-size:1.02rem !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="h-full font-sans antialiased app-shell">

<div class="flex h-full" x-data="{ sidebarOpen: {{ $collapseSidebar ? 'false' : 'window.innerWidth >= 1024' }} }">

    {{-- Sidebar --}}
    @if(!$hideNavigation)
    <aside
        class="sidebar-glass fixed inset-y-0 left-0 z-50 flex flex-col w-64 transition-transform duration-300 ease-in-out"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        {{-- Logo --}}
        <div class="flex items-center justify-between h-16 px-4 border-b border-gray-800 bg-gray-950 shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('icons/logo.png') }}" alt="SevenKey" class="w-8 h-8 rounded-lg shrink-0 object-contain">
                <span class="text-lg font-bold tracking-tight">SevenKey ERP</span>
            </a>
            <button @click="sidebarOpen = false" class="text-gray-400 lg:hidden hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5 text-sm">

            @can('view dashboard')
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            @endcan
            
            @can('view catalog')
            <a href="{{ route('catalog.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('catalog.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Katalog Produk
            </a>
            @endcan

            @can('view product')
            <a href="{{ route('products.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('products.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Produk & SKU
            </a>
            @endcan

            {{-- Master Data --}}
            @can('view master')
            <div x-data="{ open: {{ request()->routeIs('master.*') ? 'true' : 'false' }} }">
                <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('master.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                        Master Data
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="mt-0.5 pl-3 border-l border-gray-700 ml-5 space-y-0.5">
                    @foreach([
                        ['route' => 'master.brands.index',         'label' => 'Brand'],
                        ['route' => 'master.categories.index',     'label' => 'Kategori'],
                        ['route' => 'master.product-types.index',  'label' => 'Jenis Produk'],
                        ['route' => 'master.colors.index',         'label' => 'Warna'],
                        ['route' => 'master.sizes.index',          'label' => 'Ukuran'],
                        ['route' => 'master.warehouses.index',     'label' => 'Gudang'],
                        ['route' => 'master.stores.index',         'label' => 'Toko'],
                        ['route' => 'master.payment-methods.index','label' => 'Metode Bayar'],
                        ['route' => 'master.return-reasons.index', 'label' => 'Alasan Retur'],
                    ] as $item)
                    <a href="{{ route($item['route']) }}"
                        class="block px-3 py-1.5 rounded-md text-xs transition-colors {{ request()->routeIs($item['route']) ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                        {{ $item['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endcan

            {{-- Gudang --}}
            @can('view warehouse')
            <div x-data="{ open: {{ request()->routeIs('warehouse.*') ? 'true' : 'false' }} }">
                <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('warehouse.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        Gudang
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="mt-0.5 pl-3 border-l border-gray-700 ml-5 space-y-0.5">
                    @foreach([
                        ['route' => 'warehouse.stock.index',    'label' => 'Stok Gudang'],
                        ['route' => 'warehouse.inbound.index',  'label' => 'Barang Masuk'],
                        ['route' => 'warehouse.outbound.index', 'label' => 'Barang Keluar'],
                        ['route' => 'warehouse.shipments.index','label' => 'Pengiriman ke Toko'],
                        ['route' => 'warehouse.monitor',        'label' => 'Monitor Gudang'],
                    ] as $item)
                    <a href="{{ route($item['route']) }}"
                        class="block px-3 py-1.5 rounded-md text-xs transition-colors {{ request()->routeIs($item['route']) ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                        {{ $item['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endcan

            {{-- Toko --}}
            @can('view store')
            <div x-data="{ open: {{ request()->routeIs('store.*') || request()->routeIs('pos.history') ? 'true' : 'false' }} }">
                <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('store.*') || request()->routeIs('pos.history') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Toko
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="mt-0.5 pl-3 border-l border-gray-700 ml-5 space-y-0.5">
                    @foreach([
                        ['route' => 'store.stock.index',    'label' => 'Stok Toko'],
                        ['route' => 'store.receiving.index','label' => 'Terima Kiriman'],
                        ['route' => 'store.opname.index',   'label' => 'Stock Opname'],
                        ['route' => 'pos.history',          'label' => 'Riwayat Transaksi'],
                    ] as $item)
                    <a href="{{ route($item['route']) }}"
                        class="block px-3 py-1.5 rounded-md text-xs transition-colors {{ request()->routeIs($item['route']) ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                        {{ $item['label'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endcan

            @can('view customers')
            <a href="{{ route('customers.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('customers.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 00-3-3.87"/></svg>
                Pelanggan
            </a>
            @endcan

            @can('approve credit')
            @php
                $pendingCredit = \App\Models\Sale::where('approval_status', 'pending')
                    ->when(! auth()->user()->hasAnyRole(['owner', 'superadmin']), fn ($q) => $q->whereIn('store_id', auth()->user()->stores->pluck('id')))
                    ->count();
            @endphp
            <a href="{{ route('credit-approvals.index') }}"
                class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('credit-approvals.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Persetujuan Kredit
                </span>
                @if($pendingCredit > 0)<span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingCredit }}</span>@endif
            </a>
            @endcan

            @can('view transfer')
            <a href="{{ route('transfers.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('transfers.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Transfer Antar Toko
            </a>
            @endcan

            @can('access pos')
            <a href="{{ route('pos.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('pos.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Kasir / POS
            </a>
            @endcan

            @can('print product label')
            <a href="{{ route('labels.picker') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('labels.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Cetak Label
            </a>
            @endcan

            @canany(['view customer return', 'view store return'])
            <div x-data="{ open: {{ request()->routeIs('returns.*') ? 'true' : 'false' }} }">
                <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('returns.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        Retur
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="mt-0.5 pl-3 border-l border-gray-700 ml-5 space-y-0.5">
                    @can('view customer return')
                    <a href="{{ route('returns.customer.index') }}" class="block px-3 py-1.5 rounded-md text-xs {{ request()->routeIs('returns.customer.*') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">Retur Konsumen</a>
                    @endcan
                    @can('view store return')
                    <a href="{{ route('returns.store.index') }}" class="block px-3 py-1.5 rounded-md text-xs {{ request()->routeIs('returns.store.*') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">Retur ke Gudang</a>
                    @endcan
                </div>
            </div>
            @endcanany

            @can('view stock opname')
            <a href="{{ route('opname.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('opname.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Stock Opname
            </a>
            @endcan

            @can('view settlement')
            <a href="{{ route('settlements.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('settlements.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Settlement
            </a>
            @endcan

            @can('view finance')
            <a href="{{ route('finance.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('finance.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Finance
            </a>
            @endcan

            @can('view report')
            <a href="{{ route('reports.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('reports.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Laporan
            </a>
            @endcan

            @can('view expenses')
            <a href="{{ route('expenses.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('expenses.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Pengeluaran
            </a>
            @endcan

            @can('manage users')
            <div x-data="{ open: {{ request()->routeIs('admin.*') ? 'true' : 'false' }} }">
                <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        Administrasi
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="mt-0.5 pl-3 border-l border-gray-700 ml-5 space-y-0.5">
                    <a href="{{ route('admin.users.index') }}" class="block px-3 py-1.5 rounded-md text-xs {{ request()->routeIs('admin.users.*') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">Pengguna</a>
                    <a href="{{ route('admin.roles.index') }}" class="block px-3 py-1.5 rounded-md text-xs {{ request()->routeIs('admin.roles.*') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">Role & Permission</a>
                    <a href="{{ route('admin.audit-logs.index') }}" class="block px-3 py-1.5 rounded-md text-xs {{ request()->routeIs('admin.audit-logs.*') ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">Audit Log</a>
                </div>
            </div>
            @endcan

            @can('manage settings')
            <a href="{{ route('settings.credit') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('settings.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Pengaturan
            </a>
            @endcan

        </nav>

        {{-- User Info --}}
        <div class="p-3 border-t border-gray-800 shrink-0">
            <div class="flex items-center gap-3">
                @if(Auth::user()->avatar)
                    <img src="{{ Storage::url(Auth::user()->avatar) }}" class="w-8 h-8 rounded-full object-cover">
                @else
                    <div class="flex items-center justify-center w-8 h-8 text-xs font-bold uppercase bg-indigo-500 rounded-full shrink-0">
                        {{ substr(Auth::user()->name, 0, 2) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-400 capitalize truncate">{{ Auth::user()->getRoleNames()->first() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="p-1 text-gray-400 rounded hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>
    @endif

    {{-- Mobile overlay --}}
    @if(!$hideNavigation)
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        style="display:none"></div>
    @endif

    {{-- Main --}}
<div
    class="flex flex-col flex-1 min-h-screen transition-all duration-300"
    :class="{
        'lg:pl-64': sidebarOpen && !{{ $hideNavigation ? 'true' : 'false' }},
        'lg:pl-0': !sidebarOpen || {{ $hideNavigation ? 'true' : 'false' }}
    }"
>

        {{-- Topbar --}}
        <header class="topbar sticky top-0 z-30 flex items-center gap-4 px-4 bg-white border-b border-gray-200 shadow-sm h-14">
            @if(!$hideNavigation)
            <button @click="sidebarOpen = !sidebarOpen" class="p-1 text-gray-500 rounded hover:text-gray-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            @endif

            {{-- Tombol Home untuk yang tanpa sidebar (kepala toko): jalan balik ke menu/dashboard --}}
            @if($hideNavigation && !$isCashier && !$isKiosk)
            <a href="{{ route('dashboard') }}" title="Menu Utama"
               class="inline-flex items-center gap-2 pl-1.5 pr-3 py-1.5 rounded-xl hover:bg-gray-100 transition-colors">
                <img src="{{ asset('icons/logo.png') }}" alt="SevenKey" class="w-7 h-7 rounded-lg object-contain">
                <span class="hidden sm:block text-sm font-bold text-gray-700">Menu</span>
            </a>
            @endif

            <div class="flex-1 min-w-0">
                <h1 class="text-sm font-semibold text-gray-800 truncate">@yield('page-title', 'Dashboard')</h1>
                @hasSection('breadcrumb')
                <p class="text-xs text-gray-400 truncate">@yield('breadcrumb')</p>
                @endif
            </div>

            <div class="flex items-center gap-3 shrink-0">
    <span class="hidden text-xs text-gray-400 md:block">
        {{ now()->isoFormat('dddd, D MMMM Y') }}
    </span>

@can('close cash session')
    @if (request()->is('pos'))
        <a
            href="{{ url('pos/session') }}"
            class="hidden md:inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-100"
        >
            Akhiri Sesi
        </a>
    @endif
@endcan

    <div x-data="{ open: false }" class="relative">
        <button
            @click="open = !open"
            class="flex items-center gap-2 hover:bg-gray-100 rounded-lg px-2 py-1.5 transition-colors"
        >
            @if(Auth::user()->avatar)
                <img src="{{ Storage::url(Auth::user()->avatar) }}" class="w-7 h-7 rounded-full object-cover shadow-sm border border-gray-100">
            @else
                <div class="flex items-center justify-center text-xs font-bold text-indigo-700 uppercase bg-indigo-100 rounded-full w-7 h-7">
                    {{ substr(Auth::user()->name, 0, 2) }}
                </div>
            @endif

            <span class="hidden text-sm font-medium text-gray-700 md:block">
                {{ Auth::user()->name }}
            </span>
        </button>

        <div
            x-show="open"
            @click.outside="open = false"
            x-transition
            class="absolute right-0 z-50 w-48 py-1 mt-1 bg-white border border-gray-100 rounded-lg shadow-lg"
            style="display:none"
        >
            <a
                href="{{ route('profile.edit') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
            >
                Profil Saya
            </a>

            <hr class="my-1 border-gray-100">

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full px-4 py-2 text-sm text-left text-red-600 hover:bg-red-50"
                >
                    Keluar
                </button>
            </form>
        </div>
    </div>
</div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            class="flex items-center justify-between p-3 mx-4 mt-3 text-sm text-green-800 border border-green-200 rounded-lg bg-green-50">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
            <button @click="show = false" class="ml-2 text-green-400 hover:text-green-600">×</button>
        </div>
        @endif

        @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="flex items-center justify-between p-3 mx-4 mt-3 text-sm text-red-800 border border-red-200 rounded-lg bg-red-50">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                {{ session('error') }}
            </div>
            <button @click="show = false" class="ml-2 text-red-400 hover:text-red-600">×</button>
        </div>
        @endif

        @if(session('warning'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="flex items-center justify-between p-3 mx-4 mt-3 text-sm text-yellow-800 border border-yellow-200 rounded-lg bg-yellow-50">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ session('warning') }}
            </div>
            <button @click="show = false" class="ml-2 text-yellow-400 hover:text-yellow-600">×</button>
        </div>
        @endif

        @if($errors->any())
        <div class="p-3 mx-4 mt-3 text-sm text-red-800 border border-red-200 rounded-lg bg-red-50">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- Content --}}
        <main class="flex-1 p-4 md:p-6">
            @yield('content')
        </main>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@stack('scripts')

{{-- SCRIPT PENGIRIM (Khusus Superadmin & Owner) --}}
@if(auth()->check() && auth()->user()->hasAnyRole(['superadmin', 'super admin', 'owner']))
<script>
    console.log('✅ Fitur Cast Pengirim Aktif!');

    document.addEventListener('keydown', function(e) {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

        if (e.key === '1') {
            e.preventDefault();
            console.log('👉 Tombol 1 ditekan, mengirim perintah cast...');
            
            let oldPopup = document.getElementById('cast-popup');
            if(oldPopup) oldPopup.remove();

            // Membuat elemen utama
            let btn = document.createElement('div');
            btn.id = 'cast-popup';
            // PENTING: Gunakan inline style agar pasti muncul di atas
            btn.style.cssText = "position: fixed; top: 24px; right: 24px; z-index: 99999; transition: all 0.3s ease;";
            
            // Popup Loading
            btn.innerHTML = `
                <div style="background-color: #4f46e5; color: white; padding: 12px 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); font-weight: bold; display: flex; align-items: center; gap: 12px;">
                    <svg style="animation: spin 1s linear infinite; height: 20px; width: 20px; color: white;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg> 
                    Melempar layar...
                </div>
            `;
            document.body.appendChild(btn);

            fetch('{{ route('api.cast.trigger') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if(!res.ok) throw new Error('API Error: ' + res.status);
                return res.json();
            })
            .then(data => {
                console.log('✅ Berhasil:', data);
                // Popup Berhasil
                btn.innerHTML = `
                    <div style="background-color: #10b981; color: white; padding: 12px 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); font-weight: bold; display: flex; align-items: center; gap: 8px;">
                        🚀 Layar berhasil dilempar!
                    </div>
                `;
                setTimeout(() => btn.remove(), 3000);
            })
            .catch(err => {
                console.error('❌ Gagal Cast:', err);
                // Popup Gagal
                btn.innerHTML = `
                    <div style="background-color: #ef4444; color: white; padding: 12px 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); font-weight: bold; display: flex; align-items: center; gap: 8px;">
                        ⚠️ Gagal melempar layar
                    </div>
                `;
                setTimeout(() => btn.remove(), 3000);
            });
        }
    });
</script>
<style>
    @keyframes spin { 100% { transform: rotate(360deg); } }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
@endif

{{-- SCRIPT PEMBUKA LAYAR MONITOR (Hanya dimuat jika user punya akses ke menu Gudang) --}}
@can('view warehouse')
<script>
document.addEventListener('keydown', async function(e) {
    // Abaikan jika sedang mengetik secara manual di input box
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

    // PERBAIKAN: Gunakan Alt + 1 agar alat scanner barcode tidak sengaja memicunya!
    if (e.altKey && e.key === '1') {
        e.preventDefault(); 
        
        // Tambahkan sinyal rahasia ?fs=1 di ujung URL
        const monitorUrl = '{{ route("warehouse.monitor") }}?fs=1'; 

        try {
            if ('getScreenDetails' in window) {
                const screenDetails = await window.getScreenDetails();
                // Cari monitor HDMI (Layar Eksternal)
                const externalScreen = screenDetails.screens.find(s => s.isInternal === false) || screenDetails.screens[1];

                if (externalScreen) {
                    // Buka tepat di koordinat monitor HDMI dan hilangkan semua bar browser
                    const features = `left=${externalScreen.availLeft},top=${externalScreen.availTop},width=${externalScreen.availWidth},height=${externalScreen.availHeight},menubar=no,toolbar=no,location=no,status=no`;
                    window.open(monitorUrl, 'MonitorGudangWindow', features);
                } else {
                    window.open(monitorUrl, 'MonitorGudangWindow');
                }
            } else {
                window.open(monitorUrl, 'MonitorGudangWindow');
            }
        } catch (err) {
            console.error("Gagal mendeteksi layar:", err);
            window.open(monitorUrl, 'MonitorGudangWindow');
        }

        // Tampilkan Notifikasi F11 di Layar Utama
        let oldPopup = document.getElementById('f11-popup');
        if(oldPopup) oldPopup.remove();

        let btn = document.createElement('div');
        btn.id = 'f11-popup';
        btn.style.cssText = "position: fixed; top: 24px; right: 24px; z-index: 99999; transition: all 0.5s ease; animation: slideIn 0.3s ease-out;";
        
        btn.innerHTML = `
            <div style="background-color: #4f46e5; color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); display: flex; flex-direction: column; gap: 8px; border: 2px solid #818cf8; width: 350px;">
                <div style="display: flex; align-items: center; gap: 12px; font-weight: 900; font-size: 16px; letter-spacing: 0.5px;">
                    <span style="font-size: 24px;">🚨</span> MONITOR DIBUKA!
                </div>
                <div style="font-size: 14px; font-weight: normal; line-height: 1.5;">
                    Layar saat ini sedang aktif di TV.<br><br>
                    <strong style="color: #fde047; font-size: 15px;">SEGERA TEKAN TOMBOL [F11]</strong><br>
                    di keyboard Anda SEKARANG agar monitor gudang Fullscreen Permanen.
                </div>
            </div>
        `;
        document.body.appendChild(btn);
        
        // Hapus setelah 8 detik
        setTimeout(() => {
            if(document.getElementById('f11-popup')) {
                btn.style.opacity = '0';
                btn.style.transform = 'translateY(-20px)';
                setTimeout(() => btn.remove(), 500);
            }
        }, 8000);
    }
});
</script>
@endcan

{{-- GLOBAL CURRENCY FORMATTER --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    function formatCurrency(value) {
        if (value === null || value === undefined) return '';
        let valStr = value.toString();
        let isNegative = valStr.startsWith('-');
        let numberString = valStr.replace(/\D/g, ''); 
        if (numberString === '') return isNegative ? '-' : '';
        let formatted = parseInt(numberString, 10).toLocaleString('id-ID');
        return isNegative ? '-' + formatted : formatted;
    }

    document.body.addEventListener('input', function(e) {
        if (e.target.classList.contains('input-currency')) {
            let start = e.target.selectionStart;
            let oldLength = e.target.value.length;
            
            let formatted = formatCurrency(e.target.value);
            e.target.value = formatted;
            
            let newLength = formatted.length;
            let diff = newLength - oldLength;
            // setSelectionRange doesn't work well if minus sign is added/removed sometimes, but it's generally ok
            e.target.setSelectionRange(start + diff, start + diff);
        }
    });

    document.querySelectorAll('.input-currency').forEach(function(input) {
        if (input.value) {
            input.value = formatCurrency(input.value);
        }
    });

    document.body.addEventListener('submit', function(e) {
        let form = e.target;
        form.querySelectorAll('.input-currency').forEach(function(input) {
            // Remove dots, keep minus sign
            input.value = input.value.replace(/\./g, '');
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (navigator.geolocation) {
        // Cek apakah cookie sudah ada (simpan selama 1 hari agar tidak berulang kali dicari)
        if (!document.cookie.includes('user_lat=')) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                document.cookie = `user_lat=${lat}; path=/; max-age=86400; SameSite=Strict`;
                document.cookie = `user_lon=${lon}; path=/; max-age=86400; SameSite=Strict`;
            }, function(error) {
                console.warn("Location access denied or failed.");
            }, {
                enableHighAccuracy: true
            });
        }
    }
});
</script>

</body>
</html>
