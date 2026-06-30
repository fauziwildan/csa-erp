@extends('layouts.app')
@section('title', 'Data Pelanggan')
@section('page-title', 'Data Pelanggan')
@section('breadcrumb', 'Pelanggan')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Total {{ $customers->total() }} pelanggan · Batas kredit global: <strong>Rp {{ number_format($globalLimit, 0, ',', '.') }}</strong></p>
        @can('manage customers')
        <a href="{{ route('customers.create') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Tambah Pelanggan
        </a>
        @endcan
    </div>

    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 flex gap-3">
        <div class="flex-1"><input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / nomor HP..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
        <button type="submit" class="bg-gray-800 text-white text-sm px-4 py-2 rounded-lg">Filter</button>
        <a href="{{ route('customers.index') }}" class="bg-gray-100 text-gray-700 text-sm px-4 py-2 rounded-lg text-center">Reset</a>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Nama</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No. HP</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Transaksi</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Utang Berjalan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Limit</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($customers as $customer)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500">{{ $customers->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $customer->name }}</td>
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $customer->phone ?? '-' }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format($customer->tx_count, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-semibold {{ $customer->debt > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        Rp {{ number_format($customer->debt, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if(is_null($customer->credit_limit))
                            <span class="text-gray-400">Global</span>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded">Rp {{ number_format($customer->credit_limit, 0, ',', '.') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($customer->is_active)
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">Aktif</span>
                        @else
                            <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-xs">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('customers.show', $customer) }}" class="text-indigo-600 hover:underline text-xs font-medium">Detail</a>
                        @can('manage customers')
                        <a href="{{ route('customers.edit', $customer) }}" class="text-gray-500 hover:underline text-xs font-medium ml-2">Edit</a>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">Belum ada pelanggan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $customers->links() }}</div>
</div>
@endsection
