@extends('layouts.app')
@section('title', 'Setelan Kredit')
@section('page-title', 'Setelan Kredit Pelanggan')
@section('breadcrumb', 'Pengaturan / Kredit')
@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <p class="text-sm text-gray-500 mb-5">
            Batas kredit berlaku <strong>global</strong> untuk semua pelanggan, dihitung dari total utang
            berjalan tiap pelanggan (lintas toko). Kredit melekat ke pelanggan, bukan toko.
        </p>

        <form method="POST" action="{{ route('settings.credit.update') }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Batas Kredit (Rp) <span class="text-red-500">*</span></label>
                <input type="number" name="credit_limit" min="0" step="1"
                    value="{{ old('credit_limit', $creditLimit) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Isi <strong>0</strong> untuk melarang kredit sama sekali (semua transaksi tempo akan ditolak/diberi peringatan).</p>
                @error('credit_limit')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Perilaku Saat Batas Terlampaui <span class="text-red-500">*</span></label>
                <div class="space-y-2">
                    @foreach([
                        ['warning',  'Peringatan',     'Transaksi tetap diproses, kasir hanya diberi peringatan.'],
                        ['block',    'Tolak (Block)',  'Transaksi kredit yang melebihi batas langsung ditolak.'],
                        ['approval', 'Persetujuan Owner', 'Transaksi ditahan sampai disetujui owner. (Modul approval menyusul — sementara berperilaku seperti Tolak.)'],
                    ] as [$val, $label, $desc])
                    <label class="flex items-start gap-3 border border-gray-200 rounded-lg p-3 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="credit_mode" value="{{ $val }}" class="mt-1"
                            {{ old('credit_mode', $creditMode) === $val ? 'checked' : '' }}>
                        <span>
                            <span class="block text-sm font-medium text-gray-800">{{ $label }}</span>
                            <span class="block text-xs text-gray-500">{{ $desc }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
                @error('credit_mode')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="border-t border-gray-100 pt-5">
                <h3 class="text-sm font-bold text-gray-700 mb-3">Loyalty / Poin</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">1 poin per belanja (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="loyalty_earn_divisor" min="0" step="1" value="{{ old('loyalty_earn_divisor', $loyaltyDivisor) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Mis. 10.000 = 1 poin tiap belanja Rp10.000 (nota lunas). Isi 0 untuk menonaktifkan.</p>
                        @error('loyalty_earn_divisor')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nilai 1 poin (Rp)</label>
                        <input type="number" name="loyalty_point_value" min="0" step="1" value="{{ old('loyalty_point_value', $loyaltyValue) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Acuan nilai tukar poin (untuk referensi).</p>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
                    Simpan Setelan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
