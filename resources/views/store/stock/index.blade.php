@extends('layouts.app')
@section('title', 'Stok Toko')
@section('page-title', 'Stok Toko')
@section('breadcrumb', 'Toko / Stok')

@section('content')
<style>[x-cloak]{display:none!important}</style>
<div class="space-y-4">

    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Toko</label>
            <select name="store_id" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @foreach($stores as $s)
                <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
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
            <label class="block text-xs font-medium text-gray-500 mb-1">Cari</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="SKU / nama produk…"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-44 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="bg-gray-800 text-white text-sm px-4 py-2 rounded-lg self-end">Filter</button>
        <a href="{{ route('store.stock.index') }}" class="bg-gray-100 text-gray-600 text-sm px-4 py-2 rounded-lg self-end">Reset</a>
    </form>

    <div x-data="{ mode: (localStorage.getItem('storeStockView') || 'grid') }"
         x-init="$watch('mode', v => localStorage.setItem('storeStockView', v))"
         class="space-y-4">

        {{-- Info + toggle tampilan --}}
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-sm text-gray-500">
                @if($store)
                Stok aktif di <span class="font-semibold text-gray-700">{{ $store->name }}</span>
                — <span class="font-semibold text-gray-700">{{ $stocks->total() }}</span> SKU
                @endif
            </div>
            <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden bg-white">
                <button type="button" @click="mode='grid'"
                    :class="mode==='grid' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                    class="px-3 py-1.5 text-xs font-medium flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Katalog
                </button>
                <button type="button" @click="mode='table'"
                    :class="mode==='table' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                    class="px-3 py-1.5 text-xs font-medium flex items-center gap-1.5 transition-colors border-l border-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    Tabel
                </button>
            </div>
        </div>

        {{-- ===== KATALOG (kartu bergambar) ===== --}}
        <div x-show="mode==='grid'" x-cloak>
            @if($stocks->count())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @foreach($stocks as $stock)
                @php
                    $v = $stock->variant;
                    $p = optional($v)->product;
                    $img = $v?->image
                        ?? optional($p)->images?->firstWhere('is_primary', true)
                        ?? optional($p)->images?->first();
                    $qtyColor = $stock->qty <= 3 ? 'bg-red-500' : ($stock->qty <= 10 ? 'bg-amber-400' : 'bg-emerald-500');
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md hover:border-indigo-200 transition group">
                    {{-- Gambar --}}
                    <div class="aspect-square bg-gray-100 overflow-hidden relative">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        @if($img && $img->path)
                        <img src="{{ Storage::url($img->path) }}" alt="{{ optional($p)->name }}"
                            loading="lazy" onerror="this.remove()"
                            class="relative w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @endif

                        {{-- Badge Qty --}}
                        <div class="absolute top-2 right-2 {{ $qtyColor }} text-white text-xs font-bold px-2 py-0.5 rounded-full shadow">
                            {{ $stock->qty }}
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="p-3">
                        <p class="text-[11px] font-mono text-gray-400 truncate">{{ optional($v)->sku ?? '—' }}</p>
                        <p class="text-xs font-semibold text-gray-800 leading-snug line-clamp-2 mt-0.5">{{ optional($p)->name ?? '—' }}</p>
                        <div class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-600">
                            @if($v?->color?->hex_code)
                            <span class="w-3 h-3 rounded-full border border-gray-300 shrink-0" style="background-color: {{ $v->color->hex_code }}"></span>
                            @endif
                            <span class="truncate">{{ $v?->color?->name ?? '—' }} · {{ $v?->size?->name ?? '—' }}</span>
                        </div>
                        @if($v)
                        <p class="mt-1.5 text-sm font-bold text-gray-900">Rp {{ number_format($v->sellPrice(), 0, ',', '.') }}</p>
                        @endif
                        @if($p)
                        <a href="{{ route('products.show', $p) }}"
                            class="mt-2 block w-full text-center text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 py-1.5 rounded-lg font-medium transition">
                            Lihat Detail
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="bg-white rounded-xl border border-gray-200 py-20 text-center text-gray-400 text-sm">Tidak ada stok ditemukan</div>
            @endif
        </div>

        {{-- ===== TABEL ===== --}}
        <div x-show="mode==='table'" x-cloak class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">SKU</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Produk</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Warna</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Ukuran</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Harga Jual</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($stocks as $stock)
                        @php $v = $stock->variant; $p = $v->product; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $v?->sku }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('products.show', $p) }}" class="text-xs text-indigo-600 hover:underline font-medium">{{ $p->name }}</a>
                                <p class="text-xs text-gray-400">{{ $p->brand->code }}</p>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-1.5">
                                    @if($v->color->hex_code)
                                    <div class="w-3.5 h-3.5 rounded-full border border-gray-300" style="background-color: {{ $v->color->hex_code }}"></div>
                                    @endif
                                    <span class="text-xs text-gray-700">{{ $v?->color?->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $v?->size?->name }}</td>
                            <td class="px-4 py-2 text-right text-xs text-gray-700">Rp {{ number_format($v->sellPrice(), 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">
                                <span class="text-sm font-bold {{ $stock->qty <= 3 ? 'text-red-600' : ($stock->qty <= 10 ? 'text-yellow-600' : 'text-gray-900') }}">
                                    {{ $stock->qty }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Tidak ada stok ditemukan</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination (dipakai bersama kedua tampilan) --}}
        @if($stocks->hasPages())
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">{{ $stocks->links() }}</div>
        @endif
    </div>

</div>
@endsection
