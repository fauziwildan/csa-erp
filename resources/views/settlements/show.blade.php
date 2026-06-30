@extends('layouts.app')
@section('title', 'Settlement — ' . $store->name)
@section('page-title', 'Settlement — ' . $store->name)
@section('breadcrumb', 'Settlement / ' . $store->name)
@section('content')
<div class="space-y-5 max-w-4xl">
    <a href="{{ route('settlements.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Kembali</a>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Kewajiban</p>
            <p class="text-xl font-bold text-gray-800">Rp {{ number_format($summary['obligation'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Sudah Disetor</p>
            <p class="text-xl font-bold text-green-600">Rp {{ number_format($summary['settled'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Sisa</p>
            <p class="text-xl font-bold {{ $summary['outstanding'] > 0 ? 'text-red-600' : 'text-gray-700' }}">Rp {{ number_format($summary['outstanding'], 0, ',', '.') }}</p>
        </div>
    </div>

    @can('manage settlement')
    {{-- Catat setoran --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Catat Setoran</h2>
        <form method="POST" action="{{ route('settlements.store', $store) }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Jumlah (Rp) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" min="1" step="1" value="{{ old('amount') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                @error('amount')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal <span class="text-red-500">*</span></label>
                <input type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Metode</label>
                <select name="method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="transfer">Transfer</option>
                    <option value="cash">Tunai</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Bukti (opsional)</label>
                <input type="file" name="proof" accept="image/*" class="w-full text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Catatan</label>
                <input type="text" name="note" value="{{ old('note') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">Simpan Setoran</button>
            </div>
        </form>
    </div>
    @endcan

    {{-- Riwayat setoran --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm">Riwayat Setoran</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Jumlah</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Metode</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Dicatat</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Bukti</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($settlements as $s)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-gray-600">{{ $s->paid_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-right font-semibold">Rp {{ number_format($s->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-2 capitalize text-gray-600">{{ $s->method }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $s->recorder?->name ?? '-' }}<br>{{ $s->note }}</td>
                    <td class="px-4 py-2">
                        @if($s->proof_path)<a href="{{ Storage::url($s->proof_path) }}" target="_blank" class="text-indigo-600 hover:underline text-xs">Lihat</a>@else<span class="text-gray-300">—</span>@endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada setoran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $settlements->links() }}</div>
</div>
@endsection
