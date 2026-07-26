@extends('layouts.app')
@section('title', 'Reset Stok')
@section('page-title', 'Reset Stok')

@section('content')
<div class="max-w-3xl mx-auto py-2"
     x-data="stockReset({
        warehouses: {{ Illuminate\Support\Js::from($warehouses) }},
        stores: {{ Illuminate\Support\Js::from($stores) }},
        variants: {{ Illuminate\Support\Js::from($variants) }}
     })">

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Peringatan --}}
    <div class="mb-5 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 flex gap-3">
        <svg class="w-6 h-6 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div class="text-sm text-amber-800">
            <p class="font-bold">Tindakan administratif — hati-hati.</p>
            <p class="mt-0.5">Reset stok bersifat permanen. Setiap perubahan tetap tercatat di <b>stock ledger</b> &amp; <b>audit log</b>, tapi tidak ada tombol "undo". Pastikan lokasi &amp; nilainya benar.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('stock-reset.execute') }}"
          @submit="if(!confirmed){ $event.preventDefault(); alert('Centang konfirmasi dulu.'); } else if(mode==='partial' && items.length===0){ $event.preventDefault(); alert('Pilih minimal satu SKU.'); } else { readyToSend = true; }"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm divide-y divide-gray-100">
        @csrf

        {{-- Hidden inputs disinkron Alpine --}}
        <input type="hidden" name="location_type" :value="locationType">
        <input type="hidden" name="location_id" :value="locationType==='warehouse' ? warehouseId : storeId">
        <input type="hidden" name="mode" :value="mode">
        <input type="hidden" name="target_qty" :value="targetQty">
        <template x-for="(it, i) in items" :key="it.id">
            <span>
                <input type="hidden" :name="`items[${i}][variant_id]`" :value="it.id">
                <input type="hidden" :name="`items[${i}][qty]`" :value="it.qty">
            </span>
        </template>

        {{-- 1. Lokasi --}}
        <div class="p-5">
            <h3 class="text-sm font-bold text-gray-800 mb-3">1. Pilih Lokasi</h3>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="locationType='warehouse'"
                    :class="locationType==='warehouse' ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/20' : 'border-gray-200 hover:border-gray-300'"
                    class="flex items-center gap-3 border rounded-xl px-4 py-3 text-left transition-all">
                    <svg class="w-6 h-6 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <div>
                        <p class="font-bold text-gray-800 text-sm">Gudang</p>
                        <p class="text-xs text-gray-500">Stok gudang pusat</p>
                    </div>
                </button>
                <button type="button" @click="locationType='store'"
                    :class="locationType==='store' ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/20' : 'border-gray-200 hover:border-gray-300'"
                    class="flex items-center gap-3 border rounded-xl px-4 py-3 text-left transition-all">
                    <svg class="w-6 h-6 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l1-4h16l1 4M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9M4 9h16"/></svg>
                    <div>
                        <p class="font-bold text-gray-800 text-sm">Toko</p>
                        <p class="text-xs text-gray-500">Pilih salah satu toko</p>
                    </div>
                </button>
            </div>

            {{-- Dropdown gudang --}}
            <div x-show="locationType==='warehouse'" class="mt-3">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Gudang</label>
                <select x-model.number="warehouseId" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500/30">
                    <template x-for="w in warehouses" :key="w.id"><option :value="w.id" x-text="w.name"></option></template>
                </select>
            </div>
            {{-- Dropdown toko --}}
            <div x-show="locationType==='store'" class="mt-3">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Toko</label>
                <select x-model.number="storeId" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500/30">
                    <template x-for="s in stores" :key="s.id"><option :value="s.id" x-text="s.name"></option></template>
                </select>
            </div>
        </div>

        {{-- 2. Mode --}}
        <div class="p-5">
            <h3 class="text-sm font-bold text-gray-800 mb-3">2. Mode Reset</h3>
            <div class="space-y-2">
                <label :class="mode==='all' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
                       class="flex items-start gap-3 border rounded-xl px-4 py-3 cursor-pointer transition-all">
                    <input type="radio" value="all" x-model="mode" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <p class="font-bold text-gray-800 text-sm">Reset Semua Stok</p>
                        <p class="text-xs text-gray-500">Set stok <b>semua</b> varian di lokasi ini ke satu nilai.</p>
                    </div>
                </label>
                <label :class="mode==='partial' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
                       class="flex items-start gap-3 border rounded-xl px-4 py-3 cursor-pointer transition-all">
                    <input type="radio" value="partial" x-model="mode" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                    <div>
                        <p class="font-bold text-gray-800 text-sm">Reset Sebagian (pilih SKU)</p>
                        <p class="text-xs text-gray-500">Pilih beberapa SKU tertentu &amp; tentukan qty barunya masing-masing.</p>
                    </div>
                </label>
            </div>

            {{-- Mode ALL: target qty --}}
            <div x-show="mode==='all'" class="mt-4">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Set semua stok menjadi</label>
                <input type="number" min="0" x-model.number="targetQty"
                       class="w-40 border border-gray-200 rounded-lg px-3 py-2 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-indigo-500/30">
                <span class="text-xs text-gray-500 ml-2">(0 = kosongkan semua stok)</span>
            </div>

            {{-- Mode PARTIAL: picker SKU --}}
            <div x-show="mode==='partial'" class="mt-4">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Cari &amp; tambah SKU</label>
                <div class="relative">
                    <input type="text" x-model="search" @focus="showSug=true" @input="showSug=true"
                           placeholder="Ketik SKU atau nama produk..."
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500/30">
                    <div x-show="showSug && search.length>0" @click.outside="showSug=false"
                         class="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg">
                        <template x-for="v in suggestions" :key="v.id">
                            <button type="button" @click="addItem(v)"
                                    class="w-full text-left px-3 py-2 hover:bg-indigo-50 border-b border-gray-50 last:border-0">
                                <span class="font-mono text-xs text-indigo-600" x-text="v.sku"></span>
                                <span class="text-xs text-gray-600 ml-2" x-text="v.label"></span>
                            </button>
                        </template>
                        <div x-show="suggestions.length===0" class="px-3 py-2 text-xs text-gray-400">Tidak ada hasil.</div>
                    </div>
                </div>

                {{-- Daftar terpilih --}}
                <div class="mt-3 space-y-2" x-show="items.length>0">
                    <template x-for="(it, i) in items" :key="it.id">
                        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-mono text-xs text-indigo-600" x-text="it.sku"></p>
                                <p class="text-xs text-gray-500 truncate" x-text="it.label"></p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <span class="text-xs text-gray-500">qty →</span>
                                <input type="number" min="0" x-model.number="it.qty"
                                       class="w-20 border border-gray-200 rounded px-2 py-1 text-sm text-center font-bold">
                            </div>
                            <button type="button" @click="items.splice(i,1)" class="text-gray-400 hover:text-red-500 shrink-0 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <p x-show="items.length===0" class="mt-2 text-xs text-gray-400">Belum ada SKU dipilih.</p>
            </div>
        </div>

        {{-- 3. Konfirmasi + submit --}}
        <div class="p-5 bg-gray-50 rounded-b-2xl">
            <label class="flex items-start gap-3 cursor-pointer mb-4">
                <input type="checkbox" name="confirm" value="1" x-model="confirmed" class="mt-0.5 rounded text-red-600 focus:ring-red-500">
                <span class="text-sm text-gray-700">Saya paham tindakan ini <b>permanen</b> dan akan mengubah data stok di lokasi terpilih.</span>
            </label>
            <button type="submit" :disabled="!confirmed"
                    class="w-full bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-black py-3 rounded-xl text-sm shadow-md transition-all flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="mode==='all' ? 'RESET SEMUA STOK DI LOKASI INI' : 'RESET STOK SKU TERPILIH'"></span>
            </button>
        </div>
    </form>
</div>

<script>
    function stockReset(cfg){
        return {
            warehouses: cfg.warehouses || [],
            stores: cfg.stores || [],
            variants: cfg.variants || [],
            locationType: 'warehouse',
            warehouseId: (cfg.warehouses[0] ? cfg.warehouses[0].id : null),
            storeId: (cfg.stores[0] ? cfg.stores[0].id : null),
            mode: 'all',
            targetQty: 0,
            search: '',
            showSug: false,
            items: [],
            confirmed: false,
            readyToSend: false,
            get suggestions(){
                var q = this.search.toLowerCase().trim();
                if(!q) return [];
                var chosen = this.items.map(function(i){ return i.id; });
                return this.variants.filter(function(v){
                    return chosen.indexOf(v.id)===-1 &&
                        ((v.sku && v.sku.toLowerCase().indexOf(q)!==-1) || (v.label && v.label.toLowerCase().indexOf(q)!==-1));
                }).slice(0, 25);
            },
            addItem(v){
                if(this.items.some(function(i){ return i.id===v.id; })) return;
                this.items.push({ id: v.id, sku: v.sku, label: v.label, qty: 0 });
                this.search = '';
                this.showSug = false;
            }
        };
    }
</script>
@endsection
