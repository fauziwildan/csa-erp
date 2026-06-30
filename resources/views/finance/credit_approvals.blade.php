@extends('layouts.app')
@section('title', 'Persetujuan Kredit')
@section('page-title', 'Persetujuan Kredit')
@section('breadcrumb', 'Kredit / Persetujuan')
@section('content')
<div class="space-y-4 max-w-5xl">
    <p class="text-sm text-gray-500">Transaksi tempo yang melebihi batas kredit dan menunggu persetujuan. Stok sudah ditahan; menolak akan mengembalikan stok.</p>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No. Nota</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Toko</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Pelanggan</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Total</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Sisa Utang Nota</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sales as $sale)
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-4 py-3 font-mono text-xs">{{ $sale->sale_no }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $sale->store->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-800">{{ $sale->customer->name ?? $sale->customer_name ?? '-' }}</span>
                        <span class="block text-xs text-gray-400">{{ $sale->customer->phone ?? $sale->customer_phone }}</span>
                        @if($sale->customer)
                        <span class="block text-xs text-red-500">Total utang berjalan: Rp {{ number_format($sale->customer->outstanding_debt, 0, ',', '.') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-amber-600">Rp {{ number_format(max(0, $sale->total_amount - $sale->amount_paid), 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <form method="POST" action="{{ route('credit-approvals.approve', $sale) }}" class="inline" onsubmit="return confirm('Setujui transaksi kredit ini?')">
                            @csrf
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xs font-medium px-3 py-1.5 rounded">Setujui</button>
                        </form>
                        <form method="POST" action="{{ route('credit-approvals.reject', $sale) }}" class="inline" onsubmit="return confirm('Tolak transaksi ini? Stok akan dikembalikan & nota dibatalkan.')">
                            @csrf
                            <input type="hidden" name="reason" value="Ditolak owner">
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-1.5 rounded ml-1">Tolak</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Tidak ada transaksi menunggu persetujuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $sales->links() }}</div>
</div>
@endsection
