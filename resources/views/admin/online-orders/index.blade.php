@extends('layouts.app')
@section('title', 'Pesanan Online')
@section('page-title', 'Pesanan Online')
@section('breadcrumb', 'Pesanan Online')

@section('content')
<div class="space-y-4">

    {{-- Ringkasan Cashflow & Stockflow --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Menunggu Bayar</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $summary['pending_count'] }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Rp {{ number_format($summary['pending_value'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Lunas (Cashflow Masuk)</p>
            <p class="text-2xl font-bold text-green-600 mt-1">Rp {{ number_format($summary['paid_value'], 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $summary['paid_count'] }} pesanan</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Stok Keluar (Stockflow)</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $summary['paid_qty'] }} <span class="text-sm font-medium text-gray-400">pcs</span></p>
            <p class="text-xs text-gray-400 mt-0.5">dari pesanan lunas</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Dibatalkan</p>
            <p class="text-2xl font-bold text-gray-400 mt-1">{{ $summary['cancelled'] }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Semua</option>
                @foreach(['pending' => 'Menunggu Pembayaran', 'paid' => 'Lunas', 'cancelled' => 'Dibatalkan'] as $k => $v)
                <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Dari</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Sampai</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Cari</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. order / nama / HP"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-52">
        </div>
        <button type="submit" class="bg-gray-800 text-white text-sm px-4 py-2 rounded-lg">Filter</button>
        <a href="{{ route('online-orders.index') }}" class="bg-gray-100 text-gray-600 text-sm px-4 py-2 rounded-lg">Reset</a>
    </form>

    {{-- Daftar --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No. Order</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Pembeli</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Total</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $o)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 font-mono text-xs font-semibold text-indigo-600">
                            <a href="{{ route('online-orders.show', $o) }}" class="hover:underline">{{ $o->order_no }}</a>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-gray-600">{{ $o->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2.5">
                            <p class="text-xs font-medium text-gray-800">{{ $o->customer_name }}</p>
                            <p class="text-xs text-gray-400">{{ $o->customer_phone ?: '—' }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-right text-xs font-semibold text-gray-700">{{ $o->total_qty }}</td>
                        <td class="px-4 py-2.5 text-right text-xs font-bold text-gray-900">Rp {{ number_format($o->total_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $o->statusColor() }}">{{ $o->statusLabel() }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <a href="{{ route('online-orders.show', $o) }}" class="text-xs text-indigo-600 hover:underline">Detail</a>
                            @if($o->isPaid())
                            <a href="{{ route('online-orders.resi', $o) }}" target="_blank" class="text-xs text-gray-600 hover:underline ml-2">Resi</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Belum ada pesanan online.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="border-t border-gray-200 px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </div>

</div>
@endsection
