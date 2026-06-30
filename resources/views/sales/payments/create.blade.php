@extends('layouts.app')
@section('title', 'Terima Pembayaran')
@section('page-title', 'Terima Pembayaran')
@section('breadcrumb', 'Pelanggan / Pembayaran')
@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">

        <div class="mb-5 border-b border-gray-100 pb-4 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">No. Nota</span><span class="font-mono">{{ $sale->sale_no }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Pelanggan</span><span class="font-medium">{{ $sale->customer->name ?? $sale->customer_name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Total Nota</span><span>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Sudah Dibayar</span><span>Rp {{ number_format($sale->amount_paid, 0, ',', '.') }}</span></div>
            <div class="flex justify-between text-base font-bold text-amber-600"><span>Sisa Utang</span><span>Rp {{ number_format($sale->remainingDue(), 0, ',', '.') }}</span></div>
        </div>

        <form method="POST" action="{{ route('sales.payments.store', $sale) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Bayar <span class="text-red-500">*</span></label>
                <input type="number" name="amount" min="1" max="{{ $sale->remainingDue() }}" step="1" value="{{ old('amount', $sale->remainingDue()) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Boleh sebagian (cicilan). Lunas otomatis bila mencapai sisa utang.</p>
                @error('amount')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Metode <span class="text-red-500">*</span></label>
                <select name="payment_method_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($methods as $m)
                    <option value="{{ $m->id }}" {{ old('payment_method_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
                @error('payment_method_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Bayar <span class="text-red-500">*</span></label>
                <input type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('paid_at')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bukti Transfer (opsional)</label>
                <input type="file" name="proof" accept="image/*" class="w-full text-sm">
                @error('proof')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <input type="text" name="note" value="{{ old('note') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">Simpan Pembayaran</button>
                <a href="{{ $sale->customer_id ? route('customers.show', $sale->customer_id) : route('customers.index') }}" class="bg-gray-100 text-gray-700 text-sm px-5 py-2 rounded-lg">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
