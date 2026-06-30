@extends('layouts.app')
@section('title', 'Opname ' . $opname->opname_no)
@section('page-title', 'Stock Opname — ' . $opname->opname_no)
@section('breadcrumb', 'Opname / ' . $opname->opname_no)

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        @php
            $statusColors = ['draft'=>'bg-gray-100 text-gray-600','submitted'=>'bg-blue-100 text-blue-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700'];
            $statusLabels = ['draft'=>'Draft','submitted'=>'Disubmit','approved'=>'Disetujui','rejected'=>'Ditolak'];
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-400">No. Opname</p>
                <p class="font-mono font-semibold text-indigo-600">{{ $opname->opname_no }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Lokasi</p>
                <p class="font-medium text-gray-700">
                    {{ $opname->location_type === 'warehouse' ? 'Gudang' : 'Toko' }}: {{ $opname->location()?->name ?? 'ID ' . $opname->location_id }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Status</p>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColors[$opname->status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $statusLabels[$opname->status] ?? $opname->status }}
                </span>
            </div>
            <div>
                <p class="text-xs text-gray-400">Dibuat oleh</p>
                <p class="text-sm text-gray-700">{{ $opname->creator?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Tanggal</p>
                <p class="text-sm text-gray-700">{{ $opname->created_at->format('d/m/Y H:i') }}</p>
            </div>
            @if($opname->notes)
            <div class="col-span-2">
                <p class="text-xs text-gray-400">Catatan</p>
                <p class="text-sm text-gray-700">{{ $opname->notes }}</p>
            </div>
            @endif
            @if($opname->status === 'rejected' && $opname->rejection_reason)
            <div class="col-span-2">
                <p class="text-xs text-gray-400">Alasan Penolakan</p>
                <p class="text-sm text-red-600">{{ $opname->rejection_reason }}</p>
            </div>
            @endif
        </div>

        @if($opname->submitted_at || $opname->approved_at || $opname->rejected_at)
        <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-x-6 gap-y-2 text-xs text-gray-500">
            @if($opname->submitted_at)
            <span>→ Disubmit oleh <strong>{{ $opname->submitter?->name }}</strong> · {{ $opname->submitted_at->format('d/m/Y H:i') }}</span>
            @endif
            @if($opname->approved_at)
            <span class="text-green-600">✓ Disetujui oleh <strong>{{ $opname->approver?->name }}</strong> · {{ $opname->approved_at->format('d/m/Y H:i') }}</span>
            @endif
            @if($opname->rejected_at)
            <span class="text-red-500">✕ Ditolak oleh <strong>{{ $opname->rejecter?->name }}</strong> · {{ $opname->rejected_at->format('d/m/Y H:i') }}</span>
            @endif
        </div>
        @endif
    </div>

    {{-- SUBMIT form — enter actual counts --}}
    @if($opname->isDraft())
        @can('submit stock opname')
        <form method="POST" action="{{ route('opname.submit', $opname) }}" class="space-y-4">
            @csrf
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-700">Masukkan Hitungan Aktual</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Isi kolom "Qty Aktual" sesuai hasil hitungan fisik. Selisih akan dihitung otomatis.</p>
                </div>
                <div class="px-5 py-3 border-b border-gray-100 bg-indigo-50/40">
                    <label class="block text-xs font-medium text-gray-600 mb-1">➕ Tambah varian temuan
                        <span class="font-normal text-gray-400">(mis. barang ada secara fisik tapi stok sistem 0)</span>
                    </label>
                    <div class="relative max-w-md">
                        <input type="text" id="opname-add-search" autocomplete="off"
                            placeholder="Cari SKU atau nama produk…"
                            data-url="{{ route('api.variants.search') }}"
                            data-loc-param="{{ $opname->location_type === 'warehouse' ? 'warehouse_id' : 'store_id' }}"
                            data-loc-id="{{ $opname->location_id }}"
                            data-is-store="{{ $opname->location_type === 'store' ? '1' : '0' }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <div id="opname-add-results"
                            class="hidden absolute z-20 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-auto"></div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">SKU</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Produk</th>
                                @if($opname->location_type === 'store')
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Tipe Harga</th>
                                @endif
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Harga Satuan</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty Sistem</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Total Sistem</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty Aktual</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Harga Aktual</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Selisih Harga</th>
                            </tr>
                        </thead>
                        <tbody id="opname-items-body" class="divide-y divide-gray-100">
                            @foreach($opname->items as $i => $item)
                            @php 
                                $v = $item->variant; 
                                $sellPrice = $v->sellPrice();
                                $retailPrice = $v->retailPrice();
                                $hasSales = $opname->location_type === 'store' ? \App\Models\SaleItem::where('product_variant_id', $v->id)->whereHas('sale', function($q) use ($opname) {
                                    $q->where('store_id', $opname->location_id)->where('created_at', '>', $opname->created_at);
                                })->exists() : false;
                            @endphp
                            <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                            <tr class="item-row" data-sell-price="{{ $sellPrice }}" data-retail-price="{{ $retailPrice }}" data-sys-qty="{{ $item->qty_system }}">
                                <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $v?->sku }}</td>
                                <td class="px-4 py-2 text-xs text-gray-700">
                                    {{ $v?->product?->name }} · {{ $v?->color?->name }} / {{ $v?->size?->name }}
                                </td>
                                @if($opname->location_type === 'store')
                                <td class="px-4 py-2 text-xs">
                                    <select name="items[{{ $i }}][is_ecer]" class="price-type-select text-xs border border-gray-300 rounded px-2 py-1 w-20 bg-gray-50 text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                        <option value="0" {{ (old("items.$i.is_ecer", $item->is_ecer) == 0) ? 'selected' : '' }}>Grosir</option>
                                        <option value="1" {{ (old("items.$i.is_ecer", $item->is_ecer) == 1) ? 'selected' : '' }}>Ecer</option>
                                    </select>
                                </td>
                                @endif
                                <td class="px-4 py-2 text-right text-xs text-gray-600">Rp <span class="format-price actual-unit-price">{{ number_format($sellPrice, 0, ',', '.') }}</span></td>
                                <td class="px-4 py-2 text-right text-xs font-semibold {{ $hasSales ? 'text-green-600' : 'text-gray-700' }}">{{ $item->qty_system }}</td>
                                <td class="px-4 py-2 text-right text-xs text-gray-600 font-semibold">Rp <span class="format-price actual-sys-total">{{ number_format($item->qty_system * $sellPrice, 0, ',', '.') }}</span></td>
                                <td class="px-4 py-2 text-right">
                                    <input type="number" name="items[{{ $i }}][qty_actual]"
                                        value="{{ old('items.' . $i . '.qty_actual', $item->qty_actual ?? '') }}"
                                        min="0" required
                                        class="qty-input w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm text-right focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-2 text-right text-xs font-medium text-gray-700">Rp <span class="actual-price-display">0</span></td>
                                <td class="px-4 py-2 text-right text-xs font-bold diff-price-display text-gray-500">Rp 0</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td colspan="{{ $opname->location_type === 'store' ? 8 : 7 }}" class="px-4 py-3 text-right text-xs font-bold text-gray-700">Total Selisih Harga:</td>
                                <td class="px-4 py-3 text-right text-sm font-black text-gray-800" id="total-diff-display">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="flex items-center justify-between">
                {{-- Kumpulan tombol sebelah kiri --}}
                <div class="flex items-center gap-6">
                    <a href="{{ route('opname.index') }}" class="text-sm text-gray-600 hover:underline">← Kembali</a>
                    
                    @can('delete stock opname')
                    <button type="submit" form="delete-opname-form" onclick="return confirm('Apakah Anda yakin ingin menghapus draft Stock Opname ini secara permanen?')" 
                        class="text-sm text-red-600 font-semibold hover:text-red-800 transition-colors">
                        🗑️ Hapus Draft
                    </button>
                    @endcan
                </div>

                {{-- Tombol submit sebelah kanan --}}
                <button type="submit" onclick="return confirm('Submit opname {{ $opname->opname_no }} untuk persetujuan?')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition-colors">
                    Submit untuk Persetujuan
                </button>
            </div>
        </form>

        {{-- Form tersembunyi untuk proses Delete --}}
        @can('delete stock opname')
        <form id="delete-opname-form" method="POST" action="{{ route('opname.destroy', $opname) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        @endcan
        @endcan
    @endif

    {{-- APPROVE / REJECT — submitted opname --}}
    @if($opname->isSubmitted())
        @can('approve stock opname')
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700">Tindakan Persetujuan</h2>
            <p class="text-xs text-gray-500">Menyetujui opname ini akan mengaplikasikan seluruh selisih stok ke sistem.</p>
            <form method="POST" action="{{ route('opname.approve', $opname) }}">
                @csrf
                <input type="hidden" name="action" value="approve">
                <button type="submit" onclick="return confirm('Setujui dan terapkan penyesuaian stok?')"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold px-5 py-2 rounded-lg text-sm">
                    Setujui & Terapkan Stok
                </button>
            </form>
            <div x-data="{ open: false }">
                <button type="button" @click="open = !open" class="text-sm text-red-500 hover:underline">Tolak opname ini…</button>
                <div x-show="open" x-transition class="mt-3" style="display:none">
                    <form method="POST" action="{{ route('opname.approve', $opname) }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="action" value="reject">
                        <textarea name="rejection_reason" rows="2" required placeholder="Alasan penolakan…"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        <button type="submit" onclick="return confirm('Tolak opname ini?')"
                            class="bg-red-600 hover:bg-red-700 text-white font-semibold px-5 py-2 rounded-lg text-sm">
                            Konfirmasi Penolakan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endcan
    @endif

    {{-- Items table (submitted/approved/rejected view-only) --}}
    @if(!$opname->isDraft())
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
                        @if($opname->location_type === 'store')
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Tipe Harga</th>
                        @endif
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Harga Satuan</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty Sistem</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Total Sistem</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Qty Aktual</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Selisih Qty</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Selisih Harga</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $totalDiffPrice = 0; @endphp
                    @foreach($opname->items as $item)
                    @php 
                        $v = $item->variant; 
                        $diff = $item->qty_difference; 
                        $activePrice = $item->is_ecer ? $v->retailPrice() : $v->sellPrice();
                        $diffPrice = $diff !== null ? $diff * $activePrice : 0;
                        $totalDiffPrice += $diffPrice;
                        $hasSales = $opname->location_type === 'store' ? \App\Models\SaleItem::where('product_variant_id', $v->id)->whereHas('sale', function($q) use ($opname) {
                            $q->where('store_id', $opname->location_id)->where('created_at', '>', $opname->created_at);
                        })->exists() : false;
                    @endphp
                    <tr class="{{ $diff !== null && $diff != 0 ? 'bg-yellow-50' : '' }}">
                        <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $v?->sku }}</td>
                        <td class="px-4 py-2 text-xs text-gray-700">
                            {{ $v?->product?->name }} · {{ $v?->color?->name }} / {{ $v?->size?->name }}
                        </td>
                        @if($opname->location_type === 'store')
                        <td class="px-4 py-2 text-xs font-semibold text-gray-500">
                            {{ $item->is_ecer ? 'Ecer' : 'Grosir' }}
                        </td>
                        @endif
                        <td class="px-4 py-2 text-right text-xs text-gray-600">Rp {{ number_format($activePrice, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right text-xs font-semibold {{ $hasSales ? 'text-green-600' : 'text-gray-700' }}">{{ $item->qty_system }}</td>
                        <td class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Rp {{ number_format($item->qty_system * $activePrice, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right text-xs text-gray-700">{{ $item->qty_actual ?? '—' }}</td>
                        <td class="px-4 py-2 text-right text-xs font-semibold
                            {{ $diff === null ? 'text-gray-400' : ($diff > 0 ? 'text-green-600' : ($diff < 0 ? 'text-red-600' : 'text-gray-500')) }}">
                            @if($diff !== null)
                                {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-xs font-bold {{ $diffPrice > 0 ? 'text-green-600' : ($diffPrice < 0 ? 'text-red-600' : 'text-gray-500') }}">
                            @if($diff !== null)
                                {{ $diffPrice > 0 ? '+' : '' }}Rp {{ number_format($diffPrice, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="{{ $opname->location_type === 'store' ? 8 : 7 }}" class="px-4 py-3 text-right text-xs font-bold text-gray-700">Total Selisih Harga:</td>
                        <td class="px-4 py-3 text-right text-sm font-black {{ $totalDiffPrice > 0 ? 'text-green-600' : ($totalDiffPrice < 0 ? 'text-red-600' : 'text-gray-800') }}">
                            {{ $totalDiffPrice > 0 ? '+' : '' }}Rp {{ number_format($totalDiffPrice, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <a href="{{ route('opname.index') }}" class="text-sm text-gray-600 hover:underline">← Kembali</a>
    @endif

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('opname-items-body');
        const totalDiffDisplay = document.getElementById('total-diff-display');

        function formatIDR(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        }

        function getRows() {
            return tbody ? tbody.querySelectorAll('.item-row') : [];
        }

        function updateCalculations() {
            let totalDiffPrice = 0;

            getRows().forEach(row => {
                const qtyInput = row.querySelector('.qty-input');
                if (!qtyInput) return;
                const priceTypeSelect = row.querySelector('.price-type-select');
                const unitPriceDisplay = row.querySelector('.actual-unit-price');
                const sysTotalDisplay = row.querySelector('.actual-sys-total');
                const actualDisplay = row.querySelector('.actual-price-display');
                const diffDisplay = row.querySelector('.diff-price-display');

                const isEcer = priceTypeSelect ? (priceTypeSelect.value === '1') : false;
                const sellPrice = parseFloat(row.getAttribute('data-sell-price')) || 0;
                const retailPrice = parseFloat(row.getAttribute('data-retail-price')) || 0;
                const price = isEcer ? retailPrice : sellPrice;

                const sysQty = parseInt(row.getAttribute('data-sys-qty')) || 0;

                if (unitPriceDisplay) unitPriceDisplay.textContent = formatIDR(price);
                if (sysTotalDisplay) sysTotalDisplay.textContent = formatIDR(sysQty * price);

                if (qtyInput.value === '') {
                    if (actualDisplay) actualDisplay.textContent = '0';
                    if (diffDisplay) {
                        diffDisplay.textContent = 'Rp 0';
                        diffDisplay.className = 'px-4 py-2 text-right text-xs font-bold diff-price-display text-gray-500';
                    }
                    return;
                }

                const actualQty = parseInt(qtyInput.value) || 0;
                const actualPrice = actualQty * price;
                const diffPrice = (actualQty - sysQty) * price;

                totalDiffPrice += diffPrice;

                if (actualDisplay) actualDisplay.textContent = formatIDR(actualPrice);

                if (diffDisplay) {
                    const sign = diffPrice > 0 ? '+' : '';
                    diffDisplay.textContent = sign + 'Rp ' + formatIDR(diffPrice);
                    diffDisplay.className = 'px-4 py-2 text-right text-xs font-bold diff-price-display '
                        + (diffPrice > 0 ? 'text-green-600' : diffPrice < 0 ? 'text-red-600' : 'text-gray-500');
                }
            });

            if (totalDiffDisplay) {
                const totalSign = totalDiffPrice > 0 ? '+' : '';
                totalDiffDisplay.textContent = totalSign + 'Rp ' + formatIDR(totalDiffPrice);
                totalDiffDisplay.className = 'px-4 py-3 text-right text-sm font-black '
                    + (totalDiffPrice > 0 ? 'text-green-600' : totalDiffPrice < 0 ? 'text-red-600' : 'text-gray-800');
            }
        }

        // Event delegation supaya baris yang ditambah dinamis ikut dihitung
        if (tbody) {
            tbody.addEventListener('input', e => { if (e.target.classList.contains('qty-input')) updateCalculations(); });
            tbody.addEventListener('change', e => { if (e.target.classList.contains('price-type-select')) updateCalculations(); });
        }

        // ---- Tambah varian temuan (varian yang tidak ada di snapshot) ----
        const searchInput = document.getElementById('opname-add-search');
        const resultsBox = document.getElementById('opname-add-results');
        let newIdx = 0, timer = null;

        function makeTd(cls) { const c = document.createElement('td'); c.className = cls; return c; }

        function addRow(r) {
            const idx = newIdx++;
            const isStore = searchInput.dataset.isStore === '1';
            const price = parseFloat(r.price) || 0;
            const stock = parseInt(r.stock) || 0;

            const tr = document.createElement('tr');
            tr.className = 'item-row bg-yellow-50';
            tr.dataset.sellPrice = price;
            tr.dataset.retailPrice = price;
            tr.dataset.sysQty = stock;

            const tdSku = makeTd('px-4 py-2 font-mono text-xs text-gray-700'); tdSku.textContent = r.sku; tr.appendChild(tdSku);
            const tdName = makeTd('px-4 py-2 text-xs text-gray-700'); tdName.textContent = r.name; tr.appendChild(tdName);

            if (isStore) {
                const tdType = makeTd('px-4 py-2 text-xs');
                const sel = document.createElement('select');
                sel.name = `new_items[${idx}][is_ecer]`;
                sel.className = 'price-type-select text-xs border border-gray-300 rounded px-2 py-1 w-20 bg-gray-50 text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500';
                sel.innerHTML = '<option value="0">Grosir</option><option value="1">Ecer</option>';
                tdType.appendChild(sel); tr.appendChild(tdType);
            }

            const tdUnit = makeTd('px-4 py-2 text-right text-xs text-gray-600');
            tdUnit.innerHTML = 'Rp <span class="format-price actual-unit-price"></span>';
            tdUnit.querySelector('span').textContent = formatIDR(price);
            tr.appendChild(tdUnit);

            const tdSys = makeTd('px-4 py-2 text-right text-xs font-semibold text-gray-700'); tdSys.textContent = stock; tr.appendChild(tdSys);

            const tdSysTotal = makeTd('px-4 py-2 text-right text-xs text-gray-600 font-semibold');
            tdSysTotal.innerHTML = 'Rp <span class="format-price actual-sys-total"></span>';
            tdSysTotal.querySelector('span').textContent = formatIDR(stock * price);
            tr.appendChild(tdSysTotal);

            const tdQty = makeTd('px-4 py-2 text-right');
            const inp = document.createElement('input');
            inp.type = 'number'; inp.min = '0'; inp.required = true;
            inp.name = `new_items[${idx}][qty_actual]`;
            inp.className = 'qty-input w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm text-right focus:outline-none focus:ring-2 focus:ring-indigo-500';
            const hid = document.createElement('input');
            hid.type = 'hidden'; hid.name = `new_items[${idx}][variant_id]`; hid.value = r.id;
            tdQty.appendChild(inp); tdQty.appendChild(hid); tr.appendChild(tdQty);

            const tdActual = makeTd('px-4 py-2 text-right text-xs font-medium text-gray-700');
            tdActual.innerHTML = 'Rp <span class="actual-price-display">0</span>'; tr.appendChild(tdActual);

            const tdDiff = makeTd('px-4 py-2 text-right text-xs font-bold diff-price-display text-gray-500');
            tdDiff.textContent = 'Rp 0'; tr.appendChild(tdDiff);

            tbody.appendChild(tr);
            inp.focus();
            updateCalculations();
        }

        function renderResults(list) {
            resultsBox.innerHTML = '';
            if (!list.length) { resultsBox.classList.add('hidden'); return; }
            list.forEach(r => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'block w-full text-left px-3 py-2 text-xs hover:bg-indigo-50 border-b border-gray-50';
                const s1 = document.createElement('span'); s1.className = 'font-mono text-gray-700'; s1.textContent = r.sku;
                const s2 = document.createElement('span'); s2.className = 'text-gray-500'; s2.textContent = ' · ' + r.name;
                const s3 = document.createElement('span'); s3.className = 'text-gray-400'; s3.textContent = ' (stok: ' + (r.stock ?? 0) + ')';
                btn.append(s1, s2, s3);
                btn.addEventListener('click', () => {
                    addRow(r);
                    searchInput.value = '';
                    resultsBox.classList.add('hidden');
                });
                resultsBox.appendChild(btn);
            });
            resultsBox.classList.remove('hidden');
        }

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(timer);
                const q = searchInput.value.trim();
                if (q.length < 1) { resultsBox.classList.add('hidden'); return; }
                timer = setTimeout(() => {
                    const params = new URLSearchParams({ q });
                    params.set(searchInput.dataset.locParam, searchInput.dataset.locId);
                    fetch(searchInput.dataset.url + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(res => res.json())
                        .then(data => renderResults(Array.isArray(data) ? data : []))
                        .catch(() => resultsBox.classList.add('hidden'));
                }, 300);
            });
            document.addEventListener('click', e => {
                if (!resultsBox.contains(e.target) && e.target !== searchInput) resultsBox.classList.add('hidden');
            });
        }

        updateCalculations();
    });
</script>
@endpush
