@extends('layouts.app')
@section('title', 'Settlement Toko → Owner')
@section('page-title', 'Settlement Toko → Owner')
@section('breadcrumb', 'Settlement')
@section('content')
<div class="space-y-4 max-w-5xl">
    <p class="text-sm text-gray-500">Kewajiban setor = hasil jual (nota lunas) − komisi toko (Rp500/item), net retur. Bonus dibayar terpisah.</p>

    {{-- Ringkasan total --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Total Kewajiban</p>
            <p class="text-xl font-bold text-gray-800">Rp {{ number_format($totals['obligation'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Sudah Disetor</p>
            <p class="text-xl font-bold text-green-600">Rp {{ number_format($totals['settled'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase">Sisa Belum Disetor</p>
            <p class="text-xl font-bold {{ $totals['outstanding'] > 0 ? 'text-red-600' : 'text-gray-700' }}">Rp {{ number_format($totals['outstanding'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Toko</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Kewajiban</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Disetor</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Sisa</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $row['store']->name }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($row['obligation'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-green-600">Rp {{ number_format($row['settled'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-bold {{ $row['outstanding'] > 0 ? 'text-red-600' : 'text-gray-500' }}">Rp {{ number_format($row['outstanding'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('settlements.show', $row['store']) }}" class="text-indigo-600 hover:underline text-xs font-medium">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Belum ada toko.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
