@extends('layouts.app')
@section('title', isset($grammage) ? 'Edit Gramasi' : 'Tambah Gramasi')
@section('page-title', isset($grammage) ? 'Edit Gramasi' : 'Tambah Gramasi')
@section('breadcrumb', 'Master Data / Gramasi / ' . (isset($grammage) ? 'Edit' : 'Tambah'))
@section('content')
<div class="max-w-md">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <form method="POST" action="{{ isset($grammage) ? route('master.grammages.update', $grammage) : route('master.grammages.store') }}" class="space-y-4">
            @csrf
            @if(isset($grammage))
                @method('PUT')
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Gramasi <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $grammage->name ?? '') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                    maxlength="50" placeholder="Contoh: 24s, 30s, 20s, 190-200 GSM">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nilai / Keterangan Spesifikasi <span class="text-gray-400 font-normal">(Opsional)</span></label>
                <input type="text" name="value" value="{{ old('value', $grammage->value ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('value') border-red-500 @enderror"
                    maxlength="50" placeholder="Contoh: 180-190 gr/m2 atau Tebal">
                @error('value')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-400 mt-1">Keterangan tambahan ukuran ketebalan atau berat kain.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urutan Tampilan <span class="text-gray-400 font-normal">(Opsional)</span></label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $grammage->sort_order ?? '') }}" min="0"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="Contoh: 1">
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                    {{ old('is_active', $grammage->is_active ?? true) ? 'checked' : '' }}
                    class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                <label for="is_active" class="text-sm font-medium text-gray-700">Aktifkan Gramasi ini</label>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
                    {{ isset($grammage) ? 'Simpan Perubahan' : 'Tambah Gramasi' }}
                </button>
                <a href="{{ route('master.grammages.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium px-5 py-2 rounded-lg">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
