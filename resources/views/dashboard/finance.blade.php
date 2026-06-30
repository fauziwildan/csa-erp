@extends('layouts.app')
@section('title', 'Dashboard Finance')
@section('page-title', 'Ringkasan Keuangan')

@section('content')
<div class="dash grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
        <p class="text-gray-500 font-bold text-xs uppercase tracking-widest">Estimasi Omzet ({{ now()->format('F Y') }})</p>
        <h2 class="text-4xl font-black text-indigo-600 mt-2">Rp {{ number_format($monthSales ?? 0) }}</h2>
        <div class="mt-4 p-3 bg-blue-50 rounded-xl flex items-center gap-3">
            <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
            <p class="text-[10px] text-blue-700 font-medium italic">Data dihitung berdasarkan transaksi POS yang sudah tervalidasi.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <a href="{{ route('finance.sales') }}" class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:border-indigo-300 transition-all group">
            <div class="w-10 h-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 2v-6m-8 2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2z"/></svg>
            </div>
            <p class="mt-4 font-bold text-gray-800">Laporan Penjualan</p>
        </a>
        <a href="{{ route('expenses.index') }}" class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:border-indigo-300 transition-all group">
            <div class="w-10 h-10 bg-orange-50 text-orange-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="mt-4 font-bold text-gray-800">Input Pengeluaran</p>
        </a>
    </div>
</div>
@endsection