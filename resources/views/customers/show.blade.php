@extends('layouts.app')
@section('title', 'Detail Pelanggan')
@section('page-title', $customer->name)
@section('breadcrumb', 'Pelanggan / Detail')
@section('content')
<div class="space-y-5 max-w-4xl">

    <div class="flex items-center justify-between">
        <a href="{{ route('customers.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Kembali ke daftar</a>
        @can('manage customers')
        <div class="flex gap-2">
            <a href="{{ route('customers.edit', $customer) }}" class="bg-gray-800 text-white text-sm px-4 py-2 rounded-lg">Edit</a>
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Hapus pelanggan ini? Riwayat transaksi tidak ikut terhapus.')">
                @csrf @method('DELETE')
                <button type="submit" class="bg-red-600 text-white text-sm px-4 py-2 rounded-lg">Hapus</button>
            </form>
        </div>
        @endcan
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Utang Berjalan</p>
            <p class="text-2xl font-bold {{ $outstandingDebt > 0 ? 'text-red-600' : 'text-gray-700' }}">Rp {{ number_format($outstandingDebt, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Batas Kredit</p>
            <p class="text-2xl font-bold text-gray-700">Rp {{ number_format($effectiveLimit, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400">{{ is_null($customer->credit_limit) ? 'Mengikuti batas global' : 'Override khusus' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Sisa Plafon</p>
            @php $remaining = max(0, $effectiveLimit - $outstandingDebt); @endphp
            <p class="text-2xl font-bold {{ $remaining <= 0 ? 'text-red-600' : 'text-green-600' }}">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Info pelanggan --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-4 text-sm">
        <div><span class="text-gray-400 block text-xs">No. HP</span>{{ $customer->phone ?? '-' }}</div>
        <div><span class="text-gray-400 block text-xs">Kota</span>{{ $customer->city ?? '-' }}</div>
        <div class="col-span-2"><span class="text-gray-400 block text-xs">Alamat</span>{{ $customer->address ?? '-' }}</div>
        <div><span class="text-gray-400 block text-xs">Poin Loyalty</span>{{ number_format($customer->loyalty_points, 0, ',', '.') }}</div>
        <div><span class="text-gray-400 block text-xs">Status</span>{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</div>
        @if($customer->notes)<div class="col-span-2"><span class="text-gray-400 block text-xs">Catatan</span>{{ $customer->notes }}</div>@endif
    </div>

    {{-- Loyalty / Poin --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-700">Poin Loyalty</h2>
            <span class="text-2xl font-bold text-indigo-600">{{ number_format($customer->loyalty_points, 0, ',', '.') }} <span class="text-sm font-medium text-gray-400">poin</span></span>
        </div>

        @can('manage customers')
        <form method="POST" action="{{ route('customers.loyalty', $customer) }}" class="flex flex-wrap items-end gap-2 mb-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Aksi</label>
                <select name="action" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                    <option value="redeem">Tukar (kurangi)</option>
                    <option value="adjust">Penyesuaian (+/−)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Poin</label>
                <input type="number" name="points" step="1" class="w-24 border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs text-gray-500 mb-1">Catatan</label>
                <input type="text" name="note" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm" placeholder="Opsional">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg">Simpan</button>
        </form>
        @endcan

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-y border-gray-100">
                    <tr>
                        <th class="text-left px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                        <th class="text-left px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Tipe</th>
                        <th class="text-right px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Poin</th>
                        <th class="text-left px-3 py-2 text-xs font-semibold text-gray-600 uppercase">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ledgers as $l)
                    <tr>
                        <td class="px-3 py-1.5 text-gray-500 text-xs">{{ $l->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-1.5 capitalize text-gray-600 text-xs">{{ $l->type }}</td>
                        <td class="px-3 py-1.5 text-right font-semibold {{ $l->points >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $l->points > 0 ? '+' : '' }}{{ number_format($l->points, 0, ',', '.') }}</td>
                        <td class="px-3 py-1.5 text-gray-500 text-xs">{{ $l->note }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-3 py-4 text-center text-gray-400 text-xs">Belum ada aktivitas poin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Riwayat transaksi --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm">Riwayat Transaksi</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-600 uppercase">No. Nota</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Toko</th>
                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Total</th>
                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Dibayar</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs">{{ $sale->sale_no }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $sale->store->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-right">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-2 text-right">Rp {{ number_format($sale->amount_paid, 0, ',', '.') }}</td>
                    <td class="px-4 py-2">
                        @if($sale->payment_status === 'lunas')
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">Lunas</span>
                        @elseif($sale->approval_status === 'pending')
                            <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs">Menunggu Approval</span>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-xs capitalize">{{ $sale->payment_status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right whitespace-nowrap">
                        @can('record payment')
                        @if($sale->payment_status !== 'lunas' && $sale->approval_status !== 'pending')
                            <a href="{{ route('sales.payments.create', $sale) }}" class="text-indigo-600 hover:underline text-xs font-medium">Terima Pembayaran</a>
                        @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $sales->links() }}</div>
</div>
@endsection
