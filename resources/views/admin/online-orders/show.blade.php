@extends('layouts.app')
@section('title', 'Pesanan ' . $onlineOrder->order_no)
@section('page-title', 'Pesanan ' . $onlineOrder->order_no)
@section('breadcrumb', 'Pesanan Online / Detail')

@section('content')
<div class="space-y-4 max-w-5xl">

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="font-mono text-lg font-bold text-gray-800">{{ $onlineOrder->order_no }}</h2>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $onlineOrder->statusColor() }}">{{ $onlineOrder->statusLabel() }}</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">
                Dibuat {{ $onlineOrder->created_at->format('d/m/Y H:i') }} · Gudang: {{ optional($onlineOrder->warehouse)->name ?? '—' }}
            </p>
            @if($onlineOrder->isPaid())
            <p class="text-xs text-green-600 mt-1">
                Lunas {{ optional($onlineOrder->paid_at)->format('d/m/Y H:i') }} oleh {{ optional($onlineOrder->payer)->name ?? '—' }}
            </p>
            @endif
            @if($onlineOrder->isCancelled())
            <p class="text-xs text-red-600 mt-1">Dibatalkan: {{ $onlineOrder->cancel_reason }}</p>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if($onlineOrder->isPaid())
            <a href="{{ route('online-orders.resi', $onlineOrder) }}" target="_blank"
                class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold px-4 py-2 rounded-lg">🧾 Print Resi (A5)</a>
            @endif

            @can('manage online order')
            @if($onlineOrder->isPending())
            <form method="POST" action="{{ route('online-orders.pay', $onlineOrder) }}"
                onsubmit="return confirm('Konfirmasi pembayaran {{ $onlineOrder->order_no }}? Stok gudang akan dipotong.')">
                @csrf
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    ✓ Konfirmasi Lunas
                </button>
            </form>
            <div x-data="{ open: false }">
                <button @click="open = true" class="bg-red-50 hover:bg-red-100 text-red-600 text-sm font-semibold px-4 py-2 rounded-lg">Batalkan</button>
                <div x-show="open" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="open = false">
                    <form method="POST" action="{{ route('online-orders.cancel', $onlineOrder) }}" class="bg-white rounded-xl p-5 w-full max-w-sm space-y-3">
                        @csrf
                        <h3 class="font-semibold text-gray-800">Batalkan Pesanan</h3>
                        <input name="cancel_reason" required maxlength="255" placeholder="Alasan pembatalan"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="open = false" class="text-sm text-gray-600 px-3 py-2">Batal</button>
                            <button type="submit" class="bg-red-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Batalkan Pesanan</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Pembeli & pengiriman --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-sm font-semibold text-gray-700">Data Pengiriman</h3>
            <div>
                <p class="text-xs text-gray-400">Nama</p>
                <p class="text-sm text-gray-800">{{ $onlineOrder->customer_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">No. HP / WA</p>
                <p class="text-sm text-gray-800">{{ $onlineOrder->customer_phone ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Alamat</p>
                <p class="text-sm text-gray-800 whitespace-pre-line">{{ $onlineOrder->address }}</p>
            </div>
            @if($onlineOrder->notes)
            <div>
                <p class="text-xs text-gray-400">Catatan</p>
                <p class="text-sm text-gray-600">{{ $onlineOrder->notes }}</p>
            </div>
            @endif
            <div class="pt-2 border-t border-gray-100">
                <p class="text-xs text-gray-400">Pembayaran</p>
                <p class="text-sm text-gray-700">{{ $onlineOrder->payment_note ?: '—' }}</p>
            </div>
        </div>

        {{-- Item --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Item Pesanan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">SKU</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">Produk</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">Qty</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">Harga</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($onlineOrder->items as $it)
                        <tr>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ $it->sku }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-700">
                                {{ $it->product_name }}
                                <span class="text-gray-400">· {{ $it->color }} / {{ $it->size }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-right text-xs font-semibold">{{ $it->qty }}</td>
                            <td class="px-4 py-2.5 text-right text-xs text-gray-600">Rp {{ number_format($it->unit_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right text-xs font-semibold">Rp {{ number_format($it->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right text-xs font-bold text-gray-700">TOTAL</td>
                            <td class="px-4 py-3 text-right text-sm font-black text-gray-900">Rp {{ number_format($onlineOrder->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Stockflow --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Pergerakan Stok (Stockflow)</h3>
            <p class="text-xs text-gray-400 mt-0.5">Tercatat otomatis saat pembayaran dikonfirmasi.</p>
        </div>
        @if($ledgers->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">SKU</th>
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">Tipe</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">Perubahan</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">Sebelum</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase">Sesudah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($ledgers as $l)
                    <tr>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ optional($l->variant)->sku ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-xs text-gray-600">{{ $l->type }}</td>
                        <td class="px-4 py-2.5 text-right text-xs font-bold {{ $l->qty < 0 ? 'text-red-600' : 'text-green-600' }}">{{ $l->qty > 0 ? '+' : '' }}{{ $l->qty }}</td>
                        <td class="px-4 py-2.5 text-right text-xs text-gray-500">{{ $l->qty_before }}</td>
                        <td class="px-4 py-2.5 text-right text-xs font-semibold text-gray-800">{{ $l->qty_after }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-5 py-8 text-center text-xs text-gray-400">
            Belum ada pergerakan stok — stok dipotong setelah pembayaran dikonfirmasi.
        </div>
        @endif
    </div>

    <a href="{{ route('online-orders.index') }}" class="inline-block text-sm text-gray-600 hover:underline">← Kembali ke daftar</a>
</div>
@endsection
