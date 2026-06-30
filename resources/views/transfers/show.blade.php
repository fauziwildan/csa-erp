@extends('layouts.app')
@section('title', 'Transfer ' . $transfer->transfer_no)
@section('page-title', 'Transfer — ' . $transfer->transfer_no)
@section('breadcrumb', 'Transfer / ' . $transfer->transfer_no)

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Info header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between gap-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm flex-1">
                <div>
                    <p class="text-xs text-gray-400">No. Transfer</p>
                    <p class="font-mono font-semibold text-indigo-600">{{ $transfer->transfer_no }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Dari Toko</p>
                    <p class="font-medium text-gray-700">{{ $transfer->fromStore->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Ke Toko</p>
                    <p class="font-medium text-gray-700">{{ $transfer->toStore->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Status</p>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $transfer->statusColor() }}">{{ $transfer->statusLabel() }}</span>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Dibuat oleh</p>
                    <p class="text-sm text-gray-700">{{ $transfer->creator?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Tanggal</p>
                    <p class="text-sm text-gray-700">{{ $transfer->created_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($transfer->notes)
                <div class="col-span-2">
                    <p class="text-xs text-gray-400">Catatan</p>
                    <p class="text-sm text-gray-700">{{ $transfer->notes }}</p>
                </div>
                @endif
                @if($transfer->isRejected() && $transfer->rejection_reason)
                <div class="col-span-2">
                    <p class="text-xs text-gray-400">Alasan Penolakan</p>
                    <p class="text-sm text-red-600">{{ $transfer->rejection_reason }}</p>
                </div>
                @endif
            </div>
            @can('print transfer')
            <a href="{{ route('transfers.print', $transfer) }}" target="_blank"
                class="shrink-0 text-xs text-gray-600 border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50">
                Cetak
            </a>
            @endcan
        </div>

        {{-- Timeline --}}
        @if($transfer->approved_at || $transfer->rejected_at || $transfer->shipped_at || $transfer->received_at)
        <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-x-6 gap-y-2 text-xs text-gray-500">
            @if($transfer->approved_at)
            <span>✓ Disetujui oleh <strong>{{ $transfer->approver?->name }}</strong> · {{ $transfer->approved_at->format('d/m/Y H:i') }}</span>
            @endif
            @if($transfer->rejected_at)
            <span class="text-red-500">✕ Ditolak oleh <strong>{{ $transfer->rejecter?->name }}</strong> · {{ $transfer->rejected_at->format('d/m/Y H:i') }}</span>
            @endif
            @if($transfer->shipped_at)
            <span>→ Dikirim oleh <strong>{{ $transfer->shipper?->name }}</strong> · {{ $transfer->shipped_at->format('d/m/Y H:i') }}</span>
            @endif
            @if($transfer->received_at)
            <span class="text-green-600">✓ Diterima oleh <strong>{{ $transfer->receiver?->name }}</strong> · {{ $transfer->received_at->format('d/m/Y H:i') }}</span>
            @endif
        </div>
        @endif
    </div>

    {{-- APPROVE / REJECT — only for pending transfers --}}
    @if($transfer->isPending())
        @can('approve store transfer')
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700">Tindakan Persetujuan</h2>
            <form method="POST" action="{{ route('transfers.approve', $transfer) }}">
                @csrf
                <button type="submit" onclick="return confirm('Setujui transfer {{ $transfer->transfer_no }}?')"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold px-5 py-2 rounded-lg text-sm">
                    Setujui Transfer
                </button>
            </form>
            <div x-data="{ open: false }">
                <button type="button" @click="open = !open" class="text-sm text-red-500 hover:underline">Tolak transfer ini…</button>
                <div x-show="open" x-transition class="mt-3" style="display:none">
                    <form method="POST" action="{{ route('transfers.reject', $transfer) }}" class="space-y-3">
                        @csrf
                        <textarea name="rejection_reason" rows="2" required placeholder="Alasan penolakan…"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        <button type="submit" onclick="return confirm('Tolak transfer ini?')"
                            class="bg-red-600 hover:bg-red-700 text-white font-semibold px-5 py-2 rounded-lg text-sm">
                            Konfirmasi Penolakan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endcan
    @endif

    {{-- SHIP FORM — only for approved transfers + permission --}}
    @if($transfer->isApproved() && auth()->user()->can('request store transfer') && $transfer->userCanShip(auth()->user()))
    <form method="POST" action="{{ route('transfers.ship', $transfer) }}" class="space-y-4">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Proses Pengiriman</h2>
                <p class="text-xs text-gray-400 mt-0.5">Masukkan jumlah yang benar-benar dikirim. Stok toko asal akan dikurangi.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">SKU</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Produk</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Diminta</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty Kirim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($transfer->items as $i => $item)
                        @php $v = $item->variant; @endphp
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $v?->sku }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $v?->product?->name }} · {{ $v?->color?->name }} / {{ $v?->size?->name }}</td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-gray-700">{{ $item->qty_requested }}</td>
                            <td class="px-4 py-2 text-right">
                                <input type="number" name="items[{{ $i }}][qty_sent]"
                                    value="{{ $item->qty_requested }}" min="0" max="{{ $item->qty_requested }}"
                                    class="w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm text-right focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="flex items-center justify-between">
            <a href="{{ route('transfers.index', ['from_store_id' => '']) }}" class="text-sm text-gray-600 hover:underline">← Kembali</a>
            <button type="submit"
                onclick="return confirm('Konfirmasi pengiriman {{ $transfer->transfer_no }}?')"
                class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm">
                Konfirmasi Pengiriman
            </button>
        </div>
    </form>

    {{-- RECEIVE FORM — only for shipped transfers + permission --}}
    @elseif($transfer->isShipped() && auth()->user()->can('receive transfer') && $transfer->userCanReceive(auth()->user()))
    <form method="POST" action="{{ route('transfers.receive', $transfer) }}" class="space-y-4">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Konfirmasi Penerimaan</h2>
                <p class="text-xs text-gray-400 mt-0.5">Masukkan jumlah aktual yang diterima. Stok toko tujuan akan ditambahkan.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">SKU</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Produk</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty Kirim</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty Terima</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($transfer->items as $i => $item)
                        @php $v = $item->variant; @endphp
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $v?->sku }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $v?->product?->name }} · {{ $v?->color?->name }} / {{ $v?->size?->name }}</td>
                            <td class="px-4 py-2 text-right text-xs font-semibold text-gray-700">{{ $item->qty_sent }}</td>
                            <td class="px-4 py-2 text-right">
                                <input type="number" name="items[{{ $i }}][qty_received]"
                                    value="{{ $item->qty_sent }}" min="0" max="{{ $item->qty_sent }}"
                                    class="w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm text-right focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="flex items-center justify-between">
            <a href="{{ route('transfers.index', ['from_store_id' => '']) }}" class="text-sm text-gray-600 hover:underline">← Kembali</a>
            <button type="submit"
                onclick="return confirm('Konfirmasi penerimaan {{ $transfer->transfer_no }}?')"
                class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm">
                Konfirmasi Penerimaan
            </button>
        </div>
    </form>

    {{-- VIEW-ONLY items table for all other states --}}
    @else
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Detail Item</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">SKU</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Produk</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Diminta</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Dikirim</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Diterima</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($transfer->items as $item)
                    @php $v = $item->variant; @endphp
                    <tr>
                        <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $v?->sku }}</td>
                        <td class="px-4 py-2 text-xs text-gray-700">{{ $v?->product?->name }} · {{ $v?->color?->name }} / {{ $v?->size?->name }}</td>
                        <td class="px-4 py-2 text-right text-xs text-gray-700">{{ $item->qty_requested }}</td>
                        <td class="px-4 py-2 text-right text-xs text-gray-700">{{ $item->qty_sent ?: '—' }}</td>
                        <td class="px-4 py-2 text-right text-xs font-semibold
                            {{ $item->qty_sent > 0 && $item->qty_received < $item->qty_sent ? 'text-yellow-600' : ($item->qty_received > 0 ? 'text-green-600' : 'text-gray-500') }}">
                            {{ $item->qty_received ?: '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <a href="{{ route('transfers.index', ['from_store_id' => '']) }}" class="text-sm text-gray-600 hover:underline">← Kembali</a>
    @endif

</div>
@endsection
