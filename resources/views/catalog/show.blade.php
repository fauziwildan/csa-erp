@extends('layouts.app')
@section('title', $productVariant?->product?->name)
@section('page-title', $productVariant?->product?->name)
@section('breadcrumb', 'Katalog / ' . $productVariant?->product?->name)

@section('content')
@php $product = $productVariant->product; @endphp
<div class="max-w-4xl mx-auto space-y-5">

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

            {{-- Images --}}
            <div class="p-5 border-b md:border-b-0 md:border-r border-gray-100">
                @php $img = $product->primaryImage(); @endphp
                <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden">
                    @if($img && $img->path)
                    <img src="{{ Storage::url($img->path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-20 h-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @endif
                </div>
                @if($product->images->count() > 1)
                <div class="flex gap-2 mt-3 overflow-x-auto">
                    @foreach($product->images as $image)
                    <div class="w-16 h-16 rounded-lg border border-gray-200 overflow-hidden shrink-0 bg-gray-100">
                        <img src="{{ Storage::url($image->path) }}" alt="" class="w-full h-full object-cover">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Product info --}}
            <div class="p-5 space-y-4">
                @if($product->brand)
                <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wide">{{ $product?->brand?->name }}</p>
                @endif
                <h1 class="text-xl font-bold text-gray-900">{{ $product->name }}</h1>
                <p class="text-xs text-gray-400 font-mono">{{ $product->model_code }}</p>

                <div>
                    <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($productVariant->sellPrice(), 0, ',', '.') }}</p>
                    @if($productVariant->price_adjustment != 0)
                    <p class="text-xs text-gray-400">
                        Harga dasar: Rp {{ number_format($product->sell_price, 0, ',', '.') }}
                        + adjustment: {{ $productVariant->price_adjustment > 0 ? '+' : '' }}Rp {{ number_format($productVariant->price_adjustment, 0, ',', '.') }}
                    </p>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-400">Warna</p>
                        <p class="font-medium text-gray-700">{{ $productVariant?->color?->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Ukuran</p>
                        <p class="font-medium text-gray-700">{{ $productVariant?->size?->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">SKU</p>
                        <p class="font-mono text-xs font-semibold text-indigo-600">{{ $productVariant?->sku }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Kategori</p>
                        <p class="font-medium text-gray-700">{{ $product->category?->name ?? '—' }}</p>
                    </div>
                    @if($product->grammage)
                    <div>
                        <p class="text-xs text-gray-400">Gramasi</p>
                        <p class="font-medium text-gray-700">{{ $product->grammage->name }}{{ $product->grammage->value ? ' (' . $product->grammage->value . ')' : '' }}</p>
                    </div>
                    @endif
                </div>

                {{-- Stock info --}}
                @php
                    $storeStock = $productVariant->stocks->where('location_type', 'store')->sum('qty');
                    $whStock    = $productVariant->stocks->where('location_type', 'warehouse')->sum('qty');
                @endphp
                <div class="bg-gray-50 rounded-lg p-3 grid grid-cols-2 gap-3">
                    <div class="text-center">
                        <p class="text-xs text-gray-400">Stok Toko</p>
                        <p class="text-lg font-bold {{ $storeStock > 0 ? 'text-green-600' : 'text-gray-400' }}">{{ $storeStock }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-400">Stok Gudang</p>
                        <p class="text-lg font-bold {{ $whStock > 0 ? 'text-blue-600' : 'text-gray-400' }}">{{ $whStock }}</p>
                    </div>
                </div>

                @if($product->description)
                <div>
                    <p class="text-xs text-gray-400 mb-1">Deskripsi</p>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $product->description }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- All variants --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Semua Varian Produk Ini</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">SKU</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Warna</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Ukuran</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Harga</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Stok Toko</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Stok Gudang</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($product->variants as $v)
                    @php
                        $vs = $v->stocks->where('location_type','store')->sum('qty');
                        $vw = $v->stocks->where('location_type','warehouse')->sum('qty');
                    @endphp
                    <tr class="{{ $v->id === $productVariant->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-2 font-mono text-xs {{ $v->id === $productVariant->id ? 'text-indigo-700 font-bold' : 'text-gray-700' }}">
                            {{ $v?->sku }}
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-700">{{ $v?->color?->name }}</td>
                        <td class="px-4 py-2 text-xs text-gray-700">{{ $v?->size?->name }}</td>
                        <td class="px-4 py-2 text-right text-xs text-gray-700">Rp {{ number_format($v->sellPrice(), 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right text-xs {{ $vs > 0 ? 'text-green-600 font-semibold' : 'text-gray-400' }}">{{ $vs }}</td>
                        <td class="px-4 py-2 text-right text-xs {{ $vw > 0 ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">{{ $vw }}</td>
                        <td class="px-4 py-2 text-right">
                            @if($v->id !== $productVariant->id)
                            <a href="{{ route('catalog.show', $v) }}" class="text-xs text-indigo-600 hover:underline">Pilih</a>
                            @else
                            <span class="text-xs text-indigo-400 font-medium">Aktif</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('catalog.index') }}" class="text-sm text-gray-600 hover:underline">← Kembali ke Katalog</a>
</div>
{{-- SCRIPT PINTASAN SCANNER BARCODE --}}
    <script>
        let barcodeBuffer = '';
        let barcodeTimer = null;

        document.addEventListener('keydown', function(e) {
            // Abaikan jika sedang mengetik di input form
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

            if (e.key === 'Enter') {
                if (barcodeBuffer.length > 2) {
                    e.preventDefault();
                    // Lempar otomatis ke halaman Katalog + bawa query pencarian barcode-nya
                    window.location.href = "{{ route('catalog.index') }}?search=" + barcodeBuffer;
                }
                barcodeBuffer = '';
            } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                barcodeBuffer += e.key;
                clearTimeout(barcodeTimer);
                // Jeda 50ms membedakan ketikan tangan vs ketikan kilat scanner
                barcodeTimer = setTimeout(() => { barcodeBuffer = ''; }, 50);
            }
        });
    </script>
@endsection
