@extends('layouts.app')
@section('title', 'Laporan Reward & Bonus Toko')
@section('page-title', 'Laporan Reward & Bonus Toko')
@section('breadcrumb', 'Keuangan / Reward Toko')

@section('content')
    <div class="space-y-4">
        <!-- Filter Bar -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <form method="GET" action="{{ route('finance.rewards') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Bulan</label>
                    <select name="month"
                        class="w-full md:w-48 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $month == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Tahun</label>
                    <select name="year"
                        class="w-full md:w-32 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @for($y = now()->year; $y >= 2023; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Terapkan
                        Filter</button>
                </div>
            </form>
        </div>

        {{-- Export buttons --}}
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-gray-500 font-medium">Export:</span>
            <a href="{{ route('exports.rewards.pdf', array_merge(request()->query(), ['filename' => 'laporan-reward-bonus-' . now()->format('Y-m-d_H-i-s') . '.pdf'])) }}" download="laporan-reward-bonus.pdf"
                class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-2 rounded-lg transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                PDF
            </a>
            <a href="{{ route('exports.rewards.excel', array_merge(request()->query(), ['filename' => 'laporan-reward-bonus-' . now()->format('Y-m-d_H-i-s') . '.xlsx'])) }}"
                class="inline-flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium px-3 py-2 rounded-lg transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Excel
            </a>
        </div>

        <!-- Alert Info -->
        <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-4 text-sm flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="font-semibold">Informasi Perhitungan Reward</p>
                <ul class="list-disc ml-5 mt-1 text-blue-700 space-y-1">
                    <li><strong>Reward Reguler:</strong> Dibagikan per tahun, dihitung per penjualan barang sesuai
                        konfigurasi master produk.</li>
                    <li><strong>Bonus Target:</strong> Jika total penjualan dalam bulan ini melampaui target bulanan toko,
                        toko akan mendapatkan bonus Rp 1.000.000 untuk setiap kelipatan 1.000 barang yang melebihi target.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Nama Toko</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Target (Pcs)</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Terjual (Pcs)
                            </th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Kelebihan (Pcs)
                            </th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Reward Reguler
                            </th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase bg-green-50">Bonus
                                Target</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-900 uppercase">Total Bulan Ini
                            </th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Status Bonus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($storeRewards as $data)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900">{{ $data['store']->name }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-700">
                                    {{ number_format($data['target'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span
                                        class="font-bold {{ $data['total_qty'] >= $data['target'] && $data['target'] > 0 ? 'text-green-600' : 'text-gray-900' }}">
                                        {{ number_format($data['total_qty'], 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-indigo-600 font-medium">
                                    {{ $data['excess'] > 0 ? '+' . number_format($data['excess'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    Rp {{ number_format($data['regular_reward'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-green-600 bg-green-50/50">
                                    Rp {{ number_format($data['bonus'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900">
                                    Rp {{ number_format($data['total_reward'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    @php $bp = $data['bonus_paid'] ?? 0; $out = max(0, $data['bonus'] - $bp); @endphp
                                    @if($data['bonus'] <= 0)
                                        <span class="text-gray-300">—</span>
                                    @else
                                        <span class="text-xs font-bold {{ $out <= 0 ? 'text-green-600' : 'text-amber-600' }}">
                                            {{ $out <= 0 ? 'LUNAS' : 'Sisa Rp ' . number_format($out, 0, ',', '.') }}
                                        </span>
                                        <span class="block text-[10px] text-gray-400">Dibayar Rp {{ number_format($bp, 0, ',', '.') }}</span>
                                        @can('manage settlement')
                                        @if($out > 0)
                                        <details class="mt-1 text-left">
                                            <summary class="cursor-pointer text-indigo-600 text-[11px]">Catat bayar</summary>
                                            <form method="POST" action="{{ route('finance.bonus.pay') }}" enctype="multipart/form-data" class="mt-2 space-y-1 bg-gray-50 p-2 rounded-lg">
                                                @csrf
                                                <input type="hidden" name="store_id" value="{{ $data['store']->id }}">
                                                <input type="hidden" name="period_month" value="{{ (int) $month }}">
                                                <input type="hidden" name="period_year" value="{{ (int) $year }}">
                                                <input type="number" name="amount" value="{{ (int) $out }}" min="1" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" placeholder="Jumlah">
                                                <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                                                <select name="method" class="w-full border border-gray-300 rounded px-2 py-1 text-xs"><option value="transfer">Transfer</option><option value="cash">Tunai</option></select>
                                                <input type="file" name="proof" accept="image/*" class="w-full text-xs">
                                                <input type="text" name="note" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" placeholder="Catatan">
                                                <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded px-2 py-1 text-xs font-medium">Simpan</button>
                                            </form>
                                        </details>
                                        @endif
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-gray-400">Tidak ada data toko ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($storeRewards) > 0)
                        <tfoot class="bg-gray-50 border-t border-gray-200 font-bold">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right text-gray-700">Total Keseluruhan</td>
                                <td class="px-4 py-3 text-right text-gray-900">Rp
                                    {{ number_format(collect($storeRewards)->sum('regular_reward'), 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-green-600 bg-green-50">Rp
                                    {{ number_format(collect($storeRewards)->sum('bonus'), 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-indigo-700">Rp
                                    {{ number_format(collect($storeRewards)->sum('total_reward'), 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-gray-500 text-xs">Dibayar Rp
                                    {{ number_format(collect($storeRewards)->sum('bonus_paid'), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection