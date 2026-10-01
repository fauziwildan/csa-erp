@extends('layouts.app')
@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk')
@section('breadcrumb', 'Produk / ' . $product->model_code . ' / Edit')

@section('content')
<div class="max-w-3xl mx-auto">
<form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Informasi Produk</h2>
            <span class="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-1 rounded">{{ $product->model_code }}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Brand <span class="text-red-500">*</span></label>
                <select name="brand_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($brands as $b)
                    <option value="{{ $b->id }}" {{ old('brand_id', $product->brand_id) == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                <select name="category_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @if($cat->children->count())
                        @foreach($cat->children as $sub)
                        <option value="{{ $sub->id }}" {{ old('category_id', $product->category_id) == $sub->id ? 'selected' : '' }}>&nbsp;&nbsp;↳ {{ $sub->name }}</option>
                        @endforeach
                    @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Produk <span class="text-red-500">*</span></label>
                <select name="product_type_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($productTypes as $pt)
                    <option value="{{ $pt->id }}" {{ old('product_type_id', $product->product_type_id) == $pt->id ? 'selected' : '' }}>{{ $pt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gramasi <span class="text-gray-400 font-normal">(Opsional)</span></label>
                <select name="grammage_id"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Tanpa Gramasi --</option>
                    @foreach($grammages as $g)
                    <option value="{{ $g->id }}" {{ old('grammage_id', $product->grammage_id) == $g->id ? 'selected' : '' }}>
                        {{ $g->name }}{{ $g->value ? ' (' . $g->value . ')' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" x-data="{ 
            sellPrice: '{{ old('sell_price', (int)$product->sell_price) }}', 
            retailPrice: '{{ old('retail_price', $product->retail_price ? (int)$product->retail_price : '') }}',
            basePrice: '{{ old('base_price', (int)$product->base_price) }}',
            updatePrices() {
                let numBase = parseInt(this.basePrice.replace(/\D/g, '')) || 0;
                let rStore = parseInt(document.querySelector('input[name=reward_store]')?.value?.replace(/\D/g, '')) || 500;
                let rOwner = parseInt(document.querySelector('input[name=reward_owner]')?.value?.replace(/\D/g, '')) || 4500;
                
                let numSell = numBase + rStore + rOwner;
                this.sellPrice = new Intl.NumberFormat('id-ID').format(numSell);
                this.retailPrice = new Intl.NumberFormat('id-ID').format(numSell + 20000);
            },
            updateRetailPrice() {
                let numSell = parseInt(this.sellPrice.replace(/\D/g, '')) || 0;
                this.retailPrice = new Intl.NumberFormat('id-ID').format(numSell + 20000);
            }
        }">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Modal <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-sm text-gray-400">Rp</span>
                    <input type="text" inputmode="numeric" name="base_price" value="{{ old('base_price', (int)$product->base_price) }}" required
                        x-model="basePrice" @input="updatePrices"
                        class="input-currency w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual (Sistem) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-sm text-gray-400">Rp</span>
                    <input type="text" inputmode="numeric" name="sell_price" value="{{ old('sell_price', (int)$product->sell_price) }}" required
                        x-model="sellPrice" @input="updateRetailPrice"
                        class="input-currency w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Ecer <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-sm text-gray-400">Rp</span>
                    <input type="text" inputmode="numeric" name="retail_price" value="{{ old('retail_price', (int)$product->retail_price) }}" required
                        x-model="retailPrice"
                        class="input-currency w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            {{-- Letakkan di bawah input harga jual di products/edit.blade.php --}}
            @if(auth()->user()->hasAnyRole(['superadmin', 'owner']))
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-emerald-50 rounded-xl border border-emerald-100 mt-4">
                <div>
                    <label class="block text-sm font-bold text-emerald-900 mb-1">Reward Toko (Update)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-sm text-emerald-400">Rp</span>
                        <input type="text" inputmode="numeric" name="reward_store" value="{{ old('reward_store', (int)($product->reward_store ?? 500)) }}" required
                            class="input-currency w-full border border-emerald-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 mb-1">Reward Owner (Update)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-sm text-emerald-400">Rp</span>
                        <input type="text" inputmode="numeric" name="reward_owner" value="{{ old('reward_owner', (int)($product->reward_owner ?? 4500)) }}" required
                            class="input-currency w-full border border-emerald-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
            <textarea name="description" rows="3"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
            <label for="is_active" class="text-sm text-gray-700">Produk Aktif</label>
        </div>
    </div>

    {{-- Existing images --}}
    @if($product->images->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Foto Saat Ini</h2>
        <div class="flex flex-wrap gap-3">
            @foreach($product->images as $image)
            <div class="relative group">
                <img src="{{ $image->url() }}" alt=""
                    class="w-20 h-20 object-cover rounded-lg border-2 {{ $image->is_primary ? 'border-indigo-500' : 'border-gray-200' }}">
                <label class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity text-xs">
                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="sr-only">
                    ×
                </label>
                @if($image->is_primary)
                <span class="absolute bottom-1 left-1 bg-indigo-500 text-white text-xs px-1 rounded">Utama</span>
                @endif
            </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-400">Hover pada foto dan klik × untuk menghapus</p>
    </div>
    @endif

    {{-- New images --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Tambah Foto Baru</h2>
        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        <p class="text-[11px] text-amber-600 mt-2 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
            Ukuran maksimal per file adalah <strong>10 MB</strong>. Jika lebih dari itu, proses simpan akan gagal.
        </p>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('products.show', $product) }}" class="text-sm text-gray-600 hover:underline">← Batal</a>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm">
            Simpan Perubahan
        </button>
    </div>
</form>
</div>
@endsection
