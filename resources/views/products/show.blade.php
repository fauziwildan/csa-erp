@extends('layouts.app')
@section('title', $product->name)
@section('page-title', $product->name)
@section('breadcrumb', 'Produk / ' . $product->model_code)

@section('content')
<div class="space-y-5">

    {{-- Header card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row gap-6">
            {{-- Primary image --}}
            @php $img = $product->primaryImage(); @endphp
            <div class="w-full sm:w-40 h-40 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden shrink-0" id="mainProductImageContainer">
                @if($img)
                <img id="mainProductImage" src="{{ $img->url() }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                @else
                <div id="mainProductImagePlaceholder" class="text-gray-300 text-5xl">👕</div>
                @endif
            </div>

            {{-- Details --}}
            <div class="flex-1 space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ $product->brand?->code ?? '?' }}</span>
                            <span class="text-xs text-gray-400 font-mono">{{ $product->model_code }}</span>
                            @if($product->is_active)
                            <span class="text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">Aktif</span>
                            @else
                            <span class="text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Nonaktif</span>
                            @endif
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">{{ $product->name }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5 flex flex-wrap items-center gap-1.5">
                            <span>{{ $product->category?->name ?? '(Kategori dihapus)' }}</span>
                            <span>·</span>
                            <span>{{ $product->productType?->name ?? '(Tipe dihapus)' }}</span>
                            @if($product->grammage)
                            <span>·</span>
                            <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 text-xs px-2 py-0.5 rounded-md font-medium border border-amber-200">
                                Gramasi: {{ $product->grammage->name }}{{ $product->grammage->value ? ' (' . $product->grammage->value . ')' : '' }}
                            </span>
                            @endif
                        </p>
                    </div>

                    <div class="flex gap-2 shrink-0">
                        @can('update product')
                        <a href="{{ route('products.edit', $product) }}"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-3 py-1.5 rounded-lg">Edit</a>
                        @endcan
                        @can('update product')
                        <a href="{{ route('products.variants.create', $product) }}"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-3 py-1.5 rounded-lg">+ Varian</a>
                        @endcan
                        @can('delete product')
                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini? Pastikan semua varian SKU sudah dihapus terlebih dahulu.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus Produk
                            </button>
                        </form>
                    @endcan
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Harga Modal</p>
                        <p class="text-sm font-semibold text-gray-700">Rp {{ number_format($product->base_price, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Harga Jual</p>
                        <p class="text-sm font-semibold text-indigo-700">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Jumlah Varian</p>
                        <p class="text-sm font-semibold text-gray-700">{{ $product->variants->count() }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Stok</p>
                        <p class="text-sm font-semibold text-gray-700">{{ $product->variants->sum(fn($v) => $v->stocks->sum('qty')) }}</p>
                    </div>
                </div>

                @if($product->description)
                <p class="text-sm text-gray-600">{{ $product->description }}</p>
                @endif
            </div>
        </div>

        {{-- Additional images --}}
        @if($product->images->count() > 1)
        <div class="flex gap-2 mt-4 overflow-x-auto pb-1">
            @foreach($product->images as $image)
            <img src="{{ $image->url() }}" alt="" onclick="changeMainImage('{{ $image->url() }}', this)"
                class="thumbnail-img w-16 h-16 rounded-lg object-cover border-2 {{ $image->is_primary ? 'border-indigo-500' : 'border-gray-200' }} shrink-0 cursor-pointer hover:border-indigo-300 transition-colors">
            @endforeach
        </div>
        @endif
    </div>

    {{-- Variants table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Daftar Varian ({{ $product->variants->count() }})</h2>
        </div>

        @if($product->variants->isEmpty())
        <div class="py-12 text-center text-gray-400">
            <p>Belum ada varian.</p>
            @can('update product')
            <a href="{{ route('products.variants.create', $product) }}"
                class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Tambah varian sekarang →</a>
            @endcan
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Foto</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">SKU</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Warna</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Ukuran</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Harga Jual</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Total Stok</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($product->variants as $variant)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="w-10 h-10 rounded border border-gray-100 overflow-hidden bg-gray-50 cursor-pointer hover:opacity-85 transition-opacity" title="Klik untuk lihat gambar utama">
                                @if($variant->image)
                                    <img src="{{ $variant->image->url() }}" class="w-full h-full object-cover" onclick="changeMainImage('{{ $variant->image->url() }}', null)">
                                @elseif($product->images->isNotEmpty())
                                    <img src="{{ $product->images->first()->url() }}" class="w-full h-full object-cover opacity-50" onclick="changeMainImage('{{ $product->images->first()->url() }}', null)">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-[10px] text-gray-300 italic">No Pic</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $variant?->sku }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($variant->color->hex_code)
                                <div class="w-4 h-4 rounded-full border border-gray-300"
                                    style="background-color: {{ $variant->color->hex_code }}"></div>
                                @endif
                                <span class="text-xs text-gray-700">{{ $variant?->color?->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-700">{{ $variant?->size?->name }}</td>
                        <td class="px-4 py-3 text-right text-xs font-medium text-gray-700">
                            Rp {{ number_format($variant->sellPrice(), 0, ',', '.') }}
                            @if($variant->price_adjustment != 0)
                            <span class="text-gray-400">({{ $variant->price_adjustment > 0 ? '+' : '' }}{{ number_format($variant->price_adjustment, 0, ',', '.') }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php $total = $variant->stocks->sum('qty'); @endphp
                            <span class="text-xs font-semibold {{ $total > 0 ? 'text-gray-800' : 'text-red-500' }}">{{ $total }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($variant->is_active)
                            <span class="text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">Aktif</span>
                            @else
                            <span class="text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('update product')
                            <a href="{{ route('products.variants.edit', $variant) }}"
                                class="text-xs text-indigo-600 hover:underline">Edit</a>
                            @endcan
                            @can('delete product')
                            <form action="{{ route('products.variants.destroy', $variant) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Hapus varian SKU ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:underline">Hapus</button>
                            </form>
                        @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
{{-- SCRIPT PINTASAN SCANNER BARCODE --}}
    <script>
        function changeMainImage(url, clickedElement) {
            const container = document.getElementById('mainProductImageContainer');
            if (!container) return;

            let mainImg = document.getElementById('mainProductImage');
            if (!mainImg) {
                const placeholder = document.getElementById('mainProductImagePlaceholder');
                if (placeholder) {
                    placeholder.remove();
                }
                mainImg = document.createElement('img');
                mainImg.id = 'mainProductImage';
                mainImg.className = 'w-full h-full object-cover';
                mainImg.alt = "{{ $product->name }}";
                container.appendChild(mainImg);
            }
            
            mainImg.src = url;

            // Highlight border untuk thumbnail list
            document.querySelectorAll('.thumbnail-img').forEach(function(img) {
                img.classList.remove('border-indigo-500');
                img.classList.add('border-gray-200');
            });

            if (clickedElement && clickedElement.classList.contains('thumbnail-img')) {
                clickedElement.classList.remove('border-gray-200');
                clickedElement.classList.add('border-indigo-500');
            }
        }

        let barcodeBuffer = '';
        let barcodeTimer = null;

        document.addEventListener('keydown', function(e) {
            // Abaikan jika sedang mengetik di input form
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

            if (e.key === 'Enter') {
                if (barcodeBuffer.length > 2) {
                    e.preventDefault();
                    // Redirect otomatis ke halaman index produk + bawa query pencarian barcode-nya
                    window.location.href = "{{ route('products.index') }}?search=" + barcodeBuffer;
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
