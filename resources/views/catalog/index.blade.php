@extends('layouts.app')
@section('title', 'Katalog Produk')
@section('page-title', 'Katalog Produk')
@section('breadcrumb', 'Katalog')

@section('content')
<div class="space-y-4">

    {{-- Filters --}}
    {{-- Filters (Tambahkan id="filter-form" pada form) --}}
    <form method="GET" id="filter-form" class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-500 mb-1">Cari Produk</label>
            {{-- Tambahkan id="searchInput" pada input --}}
            <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                placeholder="Nama produk atau kode model…"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Brand</label>
            <select name="brand_id" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Semua Brand</option>
                @foreach($brands as $b)
                <option value="{{ $b->id }}" {{ request('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Kategori</label>
            <select name="category_id" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Semua Kategori</option>
                @foreach($categories as $c)
                <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Gramasi</label>
            <select name="grammage_id" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Semua Gramasi</option>
                @foreach($grammages as $g)
                <option value="{{ $g->id }}" {{ request('grammage_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Urutkan</label>
            <select name="sort_by" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="created_at" {{ request('sort_by') == 'created_at' || !request('sort_by') ? 'selected' : '' }}>Baru Diinput</option>
                <option value="stock" {{ request('sort_by') == 'stock' ? 'selected' : '' }}>Jumlah Stok</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Arah</label>
            <select name="sort_dir" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="desc" {{ request('sort_dir') == 'desc' || !request('sort_dir') ? 'selected' : '' }}>DESC (Terbanyak/Terbaru)</option>
                <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>ASC (Tersedikit/Terlama)</option>
            </select>
        </div>
        <button type="submit" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg self-end">Cari</button>
        <a href="{{ route('catalog.index') }}" class="bg-gray-100 text-gray-600 text-sm px-4 py-2 rounded-lg self-end">Reset</a>
    </form>

    <div class="flex items-center justify-between">
        <p class="text-xs text-gray-400">{{ $products->total() }} produk ditemukan</p>
    </div>

    {{-- Product Grid --}}
    @if($products->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        @foreach($products as $product)
        @php
            $img   = $product->primaryImage();
            $total = $product->variants->sum(fn($v) => $v->stocks->sum('qty'));
            $varCount = $product->variants->count();
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md hover:border-indigo-200 transition group">
            {{-- Image --}}
            <div class="aspect-square bg-gray-100 overflow-hidden relative">
                @if($img && $img->path)
                <img src="{{ Storage::url($img->path) }}"
                    alt="{{ $product->name }}"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                @endif

                {{-- Stock badge --}}
                @if($total <= 0)
                <div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-semibold px-2 py-0.5 rounded-full">Habis</div>
                @elseif($total <= 5)
                <div class="absolute top-2 left-2 bg-yellow-400 text-white text-xs font-semibold px-2 py-0.5 rounded-full">Hampir Habis</div>
                @endif
            </div>

            {{-- Info --}}
            <div class="p-3">
                <div class="flex items-start justify-between gap-1 mb-1">
                    @if($product->brand)
                    <p class="text-xs text-indigo-500 font-medium">{{ $product?->brand?->name }}</p>
                    @else
                    <div></div>
                    @endif
                    @if($product->grammage)
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded shrink-0" title="Gramasi">
                        {{ $product->grammage->name }}
                    </span>
                    @endif
                </div>
                <p class="text-xs font-semibold text-gray-800 leading-snug line-clamp-2">{{ $product->name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $product->model_code }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-sm font-bold text-gray-900">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">{{ $varCount }} SKU</p>
                </div>
                <div class="mt-1.5 flex items-center gap-1.5">
                    <span class="text-xs {{ $total > 0 ? 'text-green-600' : 'text-gray-400' }}">
                        Stok: {{ $total }}
                    </span>
                </div>

                @can('view product')
                <a href="{{ route('products.show', $product) }}"
                    class="mt-2 block w-full text-center text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 py-1.5 rounded-lg font-medium transition">
                    Lihat Detail
                </a>
                @endcan
            </div>
        </div>
        @endforeach
    </div>

    @if($products->hasPages())
    <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
        {{ $products->links() }}
    </div>
    @endif

    @else
    <div class="bg-white rounded-xl border border-gray-200 py-20 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <p class="text-gray-400 text-sm">Tidak ada produk ditemukan</p>
        <a href="{{ route('catalog.index') }}" class="mt-3 inline-block text-xs text-indigo-600 hover:underline">Reset filter</a>
    </div>
    @endif

</div>
{{-- SCRIPT GLOBAL SCANNER BARCODE --}}
    <script>
        let barcodeBuffer = '';
        let barcodeTimer = null;

        document.addEventListener('keydown', function(e) {
            // Abaikan jika user sedang mengetik manual di input form
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) && e.target.id !== 'searchInput') {
                return;
            }

            if (e.key === 'Enter') {
                if (barcodeBuffer.length > 2) {
                    e.preventDefault(); 
                    
                    let searchInput = document.getElementById('searchInput');
                    let filterForm = document.getElementById('filter-form');
                    
                    if (searchInput && filterForm) {
                        searchInput.value = barcodeBuffer;
                        filterForm.submit(); // Submit pencarian otomatis!
                    }
                }
                barcodeBuffer = '';
            } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                barcodeBuffer += e.key;
                clearTimeout(barcodeTimer);
                barcodeTimer = setTimeout(() => { barcodeBuffer = ''; }, 50);
            }
        });
    </script>
@endsection
