@extends('layouts.app')
@section('title', 'Master Gramasi')
@section('page-title', 'Master Gramasi')
@section('breadcrumb', 'Master Data / Gramasi')
@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-500">Total {{ $grammages->total() }} gramasi produk</p>
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('master.grammages.index') }}" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama gramasi..."
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 text-sm rounded-lg">Cari</button>
            </form>
            @can('create master')
            <a href="{{ route('master.grammages.create') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg whitespace-nowrap shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Gramasi
            </a>
            @endcan
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="w-10"></th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Nama Gramasi</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Nilai / Spesifikasi</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Urutan</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="sortable-tbody">
                @forelse($grammages as $grammage)
                <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $grammage->id }}">
                    <td class="px-4 py-3 text-center">
                        <svg class="w-5 h-5 text-gray-400 cursor-move sort-handle" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $grammages->firstItem() + $loop->index }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-900">
                        {{ $grammage->name }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ $grammage->value ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $grammage->sort_order }}</td>
                    <td class="px-4 py-3">
                        @can('update master')
                        <form method="POST" action="{{ route('master.grammages.toggle', $grammage) }}" class="inline-block">
                            @csrf
                            @method('PATCH')
                            <button type="submit" title="Klik untuk ubah status aktif/nonaktif"
                                class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full transition-colors {{ $grammage->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $grammage->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                {{ $grammage->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                        @else
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full {{ $grammage->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $grammage->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                            {{ $grammage->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        @endcan
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @can('update master')
                            <a href="{{ route('master.grammages.edit', $grammage) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium px-2 py-1 rounded hover:bg-indigo-50">Edit</a>
                            @endcan
                            @can('delete master')
                            <form method="POST" action="{{ route('master.grammages.destroy', $grammage) }}" onsubmit="return confirm('Hapus gramasi {{ $grammage->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium px-2 py-1 rounded hover:bg-red-50">Hapus</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Belum ada data gramasi produk</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($grammages->hasPages())
        <div class="border-t border-gray-200 px-4 py-3">{{ $grammages->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('sortable-tbody');
        if (el) {
            Sortable.create(el, {
                handle: '.sort-handle',
                animation: 150,
                onEnd: function (evt) {
                    let orderedIds = Array.from(el.children).map(tr => tr.getAttribute('data-id')).filter(id => id);
                    if (orderedIds.length === 0) return;

                    fetch('{{ route('master.grammages.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ ordered_ids: orderedIds })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            window.location.reload();
                        }
                    })
                    .catch(error => console.error('Error reordering:', error));
                }
            });
        }
    });
</script>
@endpush
