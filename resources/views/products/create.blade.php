@extends('layouts.app')
@section('title', 'Tambah Produk')
@section('page-title', 'Tambah Produk')
@section('breadcrumb', 'Produk / Tambah')

@section('content')
<div class="max-w-3xl mx-auto">
<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Informasi Produk</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Brand <span class="text-red-500">*</span></label>
                <select name="brand_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('brand_id') border-red-500 @enderror">
                    <option value="">-- Pilih Brand --</option>
                    @foreach($brands as $b)
                    <option value="{{ $b->id }}" {{ old('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                    @endforeach
                </select>
                @error('brand_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                <select name="category_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('category_id') border-red-500 @enderror">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @if($cat->children->count())
                        @foreach($cat->children as $sub)
                        <option value="{{ $sub->id }}" {{ old('category_id') == $sub->id ? 'selected' : '' }}>&nbsp;&nbsp;↳ {{ $sub->name }}</option>
                        @endforeach
                    @endif
                    @endforeach
                </select>
                @error('category_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Produk <span class="text-red-500">*</span></label>
                <select name="product_type_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('product_type_id') border-red-500 @enderror">
                    <option value="">-- Pilih Jenis --</option>
                    @foreach($productTypes as $pt)
                    <option value="{{ $pt->id }}" {{ old('product_type_id') == $pt->id ? 'selected' : '' }}>{{ $pt->name }}</option>
                    @endforeach
                </select>
                @error('product_type_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gramasi <span class="text-gray-400 font-normal">(Opsional)</span></label>
                <select name="grammage_id"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('grammage_id') border-red-500 @enderror">
                    <option value="">-- Tanpa Gramasi --</option>
                    @foreach($grammages as $g)
                    <option value="{{ $g->id }}" {{ old('grammage_id') == $g->id ? 'selected' : '' }}>
                        {{ $g->name }}{{ $g->value ? ' (' . $g->value . ')' : '' }}
                    </option>
                    @endforeach
                </select>
                @error('grammage_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                placeholder="e.g. Kaos Polo Slim Fit"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" x-data="{ 
            sellPrice: '{{ old('sell_price') }}', 
            retailPrice: '{{ old('retail_price') }}',
            basePrice: '{{ old('base_price', 0) }}',
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
                    <input type="text" inputmode="numeric" name="base_price" value="{{ old('base_price', 0) }}" required
                        x-model="basePrice" @input="updatePrices"
                        class="input-currency w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('base_price') border-red-500 @enderror">
                </div>
                @error('base_price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual (Sistem) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-sm text-gray-400">Rp</span>
                    <input type="text" inputmode="numeric" name="sell_price" value="{{ old('sell_price', 0) }}" required
                        x-model="sellPrice" @input="updateRetailPrice"
                        class="input-currency w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('sell_price') border-red-500 @enderror">
                </div>
                @error('sell_price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Ecer <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-sm text-gray-400">Rp</span>
                    <input type="text" inputmode="numeric" name="retail_price" value="{{ old('retail_price', '') }}" required
                        x-model="retailPrice"
                        class="input-currency w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('retail_price') border-red-500 @enderror">
                </div>
                @error('retail_price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            {{-- Letakkan di bawah input harga jual di products/create.blade.php --}}
            @if(auth()->user()->hasAnyRole(['superadmin', 'owner']))
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                <div>
                    <label class="block text-sm font-bold text-indigo-900 mb-1">Reward Toko (Per Item)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-sm text-indigo-400">Rp</span>
                        <input type="text" inputmode="numeric" name="reward_store" value="{{ old('reward_store', 500) }}" required
                            class="input-currency w-full border border-indigo-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <p class="text-[10px] text-indigo-400 mt-1">* Nominal yang didapat toko saat item ini terjual.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-indigo-900 mb-1">Reward Owner (Per Item)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-sm text-indigo-400">Rp</span>
                        <input type="text" inputmode="numeric" name="reward_owner" value="{{ old('reward_owner', 4500) }}" required
                            class="input-currency w-full border border-indigo-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <p class="text-[10px] text-indigo-400 mt-1">* Nominal dividen owner saat item ini terjual.</p>
                </div>
            </div>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
            <textarea name="description" rows="3" placeholder="Deskripsi produk (opsional)…"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('description') }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                {{ old('is_active', '1') ? 'checked' : '' }}
                class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
            <label for="is_active" class="text-sm text-gray-700">Produk Aktif</label>
        </div>
    </div>

    {{-- Images --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Foto Produk</h2>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Gambar <span class="text-gray-400 font-normal">(maks 5 file, JPG/PNG)</span></label>
            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <p class="text-[11px] text-amber-600 mt-2 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                Ukuran maksimal per file adalah <strong>10 MB</strong>. Jika lebih dari itu, proses simpan akan gagal.
            </p>
            @error('images.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('products.index') }}" class="text-sm text-gray-600 hover:underline">← Batal</a>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm">
            Simpan Produk
        </button>
    </div>
</form>
</div>
@endsection
