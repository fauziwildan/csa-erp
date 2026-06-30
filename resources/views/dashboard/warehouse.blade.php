@extends('layouts.app')
@section('title', 'Dashboard Gudang')
@section('page-title', 'Operasional Gudang')

@section('content')
<div class="dash">
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 cards-tight">
    {{-- Card Pengeluaran Hari Ini --}}
    <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm group relative overflow-hidden transition-all hover:shadow-md">
        <div class="absolute top-0 right-0 w-16 h-16 bg-red-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
        <p class="text-xs font-bold text-red-500 uppercase tracking-wider mb-1 relative z-10">Pengeluaran Hari Ini</p>
        <div class="flex items-center justify-between relative z-10">
            <h4 class="text-xl font-black text-gray-900">Rp {{ number_format($todayExpense, 0, ',', '.') }}</h4>
            <div class="p-2 bg-red-100 rounded-lg text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
            </div>
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-red-100 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <h3 class="text-gray-500 font-bold text-xs uppercase tracking-wider">Stok Hampir Habis</h3>
            <span class="p-2 bg-red-50 text-red-600 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
        </div>
        <p class="text-2xl font-black text-red-600 mt-3">{{ $lowStock ?? 0 }} <span class="text-xs font-medium text-gray-400 uppercase">Sku</span></p>
        <a href="{{ route('warehouse.stock.index') }}" class="text-[10px] text-red-500 font-bold mt-1 inline-block hover:underline">LIHAT DETAIL →</a>
    </div>

    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
            <h3 class="text-gray-500 font-bold text-xs uppercase tracking-wider">Total Unit Stok</h3>
            <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </span>
        </div>
        <p class="text-2xl font-black text-gray-800 mt-3">{{ number_format($totalWarehouseStock ?? 0) }} <span class="text-xs font-medium text-gray-400 uppercase">Pcs</span></p>
    </div>

    <div class="bg-indigo-600 p-5 rounded-xl shadow-lg text-white flex flex-col justify-between transition-all hover:shadow-indigo-200">
        <div>
            <h3 class="font-bold text-sm uppercase tracking-tight">Monitor Gudang</h3>
            <p class="text-indigo-100 text-[10px] mt-1">Pantau pengiriman real-time.</p>
        </div>
        <a href="{{ route('warehouse.monitor') }}" class="mt-3 bg-white text-indigo-600 text-center py-1.5 rounded-lg font-bold text-xs hover:bg-indigo-50 transition-colors uppercase">Buka Monitor</a>
    </div>
</div>

{{-- Latest Stock Opname Card --}}
@if(isset($latestOpname) && $latestOpname)
<div x-data="{ openOpnameModal: false }" x-init="$watch('openOpnameModal', val => document.body.style.overflow = val ? 'hidden' : '')" class="mt-4">
    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between group cursor-pointer hover:border-indigo-300 transition-colors gap-4" @click="openOpnameModal = true">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-sm">Stock Opname Terbaru</h3>
                <p class="text-xs text-gray-500 mt-1">No. <span class="font-mono text-indigo-600 font-semibold">{{ $latestOpname->opname_no }}</span> · {{ $latestOpname->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        <div class="text-left sm:text-right w-full sm:w-auto flex justify-between sm:block items-center">
            @php
                $statusColors = ['draft'=>'bg-gray-100 text-gray-600','submitted'=>'bg-blue-100 text-blue-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700'];
                $statusLabels = ['draft'=>'Draft','submitted'=>'Disubmit','approved'=>'Disetujui','rejected'=>'Ditolak'];
            @endphp
            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold {{ $statusColors[$latestOpname->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $statusLabels[$latestOpname->status] ?? $latestOpname->status }}</span>
            <p class="text-[10px] text-gray-400 mt-1 sm:mt-1 group-hover:text-indigo-500">Klik untuk detail &rarr;</p>
        </div>
    </div>

    {{-- Modal --}}
    <template x-teleport="body">
        <div x-show="openOpnameModal" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4 py-6" x-transition.opacity>
            <div @click.away="openOpnameModal = false" class="bg-white rounded-2xl shadow-xl w-full max-w-3xl flex flex-col overflow-hidden" style="max-height: 85vh;" x-transition.scale.origin.bottom>
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 shrink-0">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Detail Stock Opname <span class="font-mono text-indigo-600 text-base ml-2">{{ $latestOpname->opname_no }}</span></h3>
                    <p class="text-xs text-gray-500 mt-1">Lokasi: {{ $latestOpname->location_type === 'store' ? 'Toko' : 'Gudang' }} · Dibuat oleh: {{ $latestOpname->creator->name ?? '-' }}</p>
                </div>
                <button type="button" @click="openOpnameModal = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-0 overflow-y-auto flex-1 min-h-0">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] tracking-wider sticky top-0 shadow-sm">
                        <tr>
                            <th class="px-6 py-3">SKU</th>
                            <th class="px-6 py-3">Produk</th>
                            <th class="px-6 py-3 text-right">Sistem</th>
                            <th class="px-6 py-3 text-right">Aktual</th>
                            <th class="px-6 py-3 text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($latestOpname->items as $item)
                            @php $v = $item->variant; $diff = $item->qty_difference; @endphp
                            <tr class="hover:bg-gray-50 {{ $diff !== null && $diff != 0 ? 'bg-yellow-50/30' : '' }}">
                                <td class="px-6 py-3 font-mono text-xs">{{ $v?->sku }}</td>
                                <td class="px-6 py-3 text-xs">{{ $v?->product?->name }} · {{ $v?->color?->name }}/{{ $v?->size?->name }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-700">{{ $item->qty_system }}</td>
                                <td class="px-6 py-3 text-right text-gray-700">{{ $item->qty_actual ?? '-' }}</td>
                                <td class="px-6 py-3 text-right font-bold {{ $diff === null ? 'text-gray-400' : ($diff > 0 ? 'text-green-600' : ($diff < 0 ? 'text-red-600' : 'text-gray-500')) }}">
                                    {{ $diff !== null ? ($diff > 0 ? '+'.$diff : $diff) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end items-center gap-3 shrink-0">
                <a href="{{ route('opname.show', $latestOpname) }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold px-4 py-2">Buka Halaman Opname &rarr;</a>
                <button type="button" @click="openOpnameModal = false" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-semibold transition-colors">Tutup</button>
            </div>
        </div>
    </template>
</div>
@endif

<div class="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="font-bold text-gray-800">Produk Terbaru di Sistem</h3>
                <p class="text-xs text-gray-500 mt-0.5">Menampilkan 10 data produk terbaru.</p>
            </div>
            <a href="{{ route('products.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">Lihat Semua Produk &rarr;</a>
        </div>
        <div class="overflow-x-auto">
           <table class="w-full text-sm text-left">
    <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
        <tr>
            <th class="px-6 py-3">Produk</th>
            <th class="px-6 py-3">Brand</th>
            <th class="px-6 py-3 text-right">Stok</th> {{-- Tambahkan Header Stok --}}
            <th class="px-6 py-3 text-right">Harga Jual</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        @forelse($products as $p)
        @php
            // Hitung total stok dari semua varian (sudah terfilter di Controller)
            $totalQty = $p->variants->sum(fn($v) => $v->stocks->sum('qty'));
        @endphp
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-3">
                <div class="flex items-center gap-4">
                    {{-- Gambar Produk --}}
                    <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden border border-gray-100 shrink-0">
                        @php $primaryImg = $p->primaryImage(); @endphp
                        @if($primaryImg && $primaryImg->path)
                            <img src="{{ Storage::url($primaryImg->path) }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-xl bg-gray-50 text-gray-300">👕</div>
                        @endif
                    </div>
                    <div>
                        <span class="font-bold text-gray-800">{{ $p->name }}</span><br>
                        <span class="text-[10px] text-gray-400 font-mono uppercase tracking-tight">{{ $p->model_code }}</span>
                    </div>
                </div>
            </td>
            <td class="px-6 py-3 text-xs text-gray-500">
                {{ $p?->brand?->name ?? '—' }}
            </td>
            {{-- Kolom Stok --}}
            <td class="px-6 py-3 text-right">
                <span class="inline-block px-2 py-1 rounded-lg font-bold text-sm {{ $totalQty <= 5 ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">
                    {{ number_format($totalQty) }} <span class="text-[10px] font-normal uppercase">Pcs</span>
                </span>
            </td>
            <td class="px-6 py-3 text-right font-bold text-gray-900">
                Rp {{ number_format($p->sell_price, 0, ',', '.') }}
            </td>
        </tr>
        @empty
        {{-- ... empty state ... --}}
        @endforelse
    </tbody>
</table>
        </div>
    </div>
</div>
@endsection