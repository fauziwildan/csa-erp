@extends('layouts.app')
@section('title', 'Transfer Antar Toko')
@section('page-title', 'Transfer Antar Toko')
@section('breadcrumb', 'Transfer')

@section('content')
<div class="space-y-4">

    <div class="flex items-center justify-between">
        <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end flex-1 mr-4">
            @php
                $isRestricted = !auth()->user()->hasAnyRole(['superadmin', 'owner', 'finance', 'admin gudang']);
                $primaryStore = auth()->user()->primaryStore();
            @endphp
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Toko Asal</label>
                <select name="from_store_id" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua</option>
                    @foreach(($isRestricted ? auth()->user()->stores : $stores) as $s)
                    <option value="{{ $s->id }}" {{ $fromStoreId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Toko Tujuan</label>
                <select name="to_store_id" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ request('to_store_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua</option>
                    @foreach(\App\Models\Transfer::STATUS_LABELS as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('transfers.index') }}" class="bg-gray-100 text-gray-600 text-sm px-4 py-2 rounded-lg self-end">Reset</a>
        </form>

        @can('request store transfer')
        <a href="{{ route('transfers.create') }}"
            class="shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-lg text-sm">
            + Buat Transfer
        </a>
        @endcan
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No. Transfer</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Dari Toko</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Ke Toko</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Dibuat</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transfers as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-indigo-600">{{ $t->transfer_no }}</td>
                        <td class="px-4 py-3 text-xs text-gray-700">{{ $t->fromStore->name }}</td>
                        <td class="px-4 py-3 text-xs text-gray-700">{{ $t->toStore->name }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $t->statusColor() }}">{{ $t->statusLabel() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-xs text-gray-700">{{ $t->items->sum('qty_requested') }}</td>
                        <td class="px-4 py-3 text-xs text-gray-400">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('transfers.show', $t) }}" class="text-xs text-indigo-600 hover:underline">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Tidak ada transfer</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transfers->hasPages())
        <div class="border-t border-gray-200 px-4 py-3">{{ $transfers->links() }}</div>
        @endif
    </div>

</div>
@endsection
