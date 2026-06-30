@extends('layouts.app')
@section('title', 'Monitor Gudang')
@section('page-title', 'Monitor Gudang')
@section('breadcrumb', 'Gudang / Monitor')

@section('content')
    @php 
            // Penanda apakah ini layar TV (Fullscreen) atau layar Laptop
        $isFs = request()->query('fs') == '1'; 
    @endphp

    {{-- =========================================================================
    CSS PEMBUNUH LAYOUT BAWAAN (Hanya aktif di Layar Extend)
    Menyembunyikan segala hal saat loading agar sidebar tidak terlihat berkedip
    ========================================================================= --}}
    {{-- =========================================================================
    KONTEN MONITOR (FULLSCREEN KIOSK MODE)
    ========================================================================= --}}
    <div id="fs-monitor-wrapper" class="h-screen flex flex-col bg-[#f8fafc] text-slate-800 overflow-hidden"
        style="-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility;"
        x-data="{ refreshIn: 5 }">

        {{-- 1. HEADER (Top - 8% Height) --}}
        @if($isFs)
            <header
                style="height: 8vh; display: flex; justify-content: space-between; align-items: center; padding: 0 5%; background: white; border-bottom: 1px solid #e2e8f0; shrink-0; shadow-sm;">
                <div>
                    <h1
                        style="font-size: 1.8vw; font-weight: 900; color: #1e293b; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">
                        Live Monitor Gudang</h1>
                    <div style="display: flex; align-items: center; gap: 15px; margin-top: 2px;">
                        <p style="font-size: 0.7vw; color: #94a3b8; font-family: monospace; margin: 0;">Data diperbarui: <span
                                id="last-update-time"
                                style="font-weight: 800; color: #64748b;">{{ now()->format('H:i:s') }}</span></p>
                        <p style="font-size: 0.7vw; color: #94a3b8; margin: 0;">Refresh: <span id="countdown-timer"
                                style="font-weight: 800; color: #6366f1;">5</span>s</p>
                    </div>
                </div>
                <div style="text-align: right;">
                    <p style="font-size: 2.2vw; font-weight: 900; color: #4f46e5; margin: 0; line-height: 1;" id="liveClock">
                        00:00:00</p>
                    <p
                        style="font-size: 0.7vw; color: #94a3b8; font-weight: 800; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.1em;">
                        {{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
            </header>
        @endif

        {{-- 2. MAIN CONTENT (Middle - 52% Height) --}}
        <div style="height: 52vh; display: flex; flex-direction: column; padding: 1.5vh 2vw; gap: 1.5vh; overflow: hidden;">

            {{-- Statistik Atas (12vh) --}}
            <div style="height: 12vh; display: flex; flex-direction: row; gap: 1.5vw; flex-shrink: 0;">
                @foreach($warehouses as $wh)
                    @php
                        $total = \App\Models\Stock::where('location_type', 'warehouse')->where('location_id', $wh->id)->sum('qty');
                        $skuCount = \App\Models\Stock::where('location_type', 'warehouse')->where('location_id', $wh->id)->distinct('product_variant_id')->count();
                    @endphp
                    <div
                        style="flex: 1; background: white; border: 1px solid #e2e8f0; border-radius: 1vw; padding: 1vh 1.2vw; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: center;">
                        <p style="font-size: 0.65vw; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin: 0;">
                            {{ $wh->name }}</p>
                        <p style="font-size: 1.8vw; font-weight: 900; color: #1e293b; margin: 0.2vh 0; line-height: 1;">
                            {{ number_format($total) }}</p>
                        <p style="font-size: 0.7vw; color: #94a3b8; font-weight: 700; margin: 0;">{{ $skuCount }} SKU</p>
                    </div>
                @endforeach
                <div
                    style="flex: 1; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 1vw; padding: 1vh 1.2vw; display: flex; flex-direction: column; justify-content: center;">
                    <p style="font-size: 0.65vw; font-weight: 800; text-transform: uppercase; color: #7c3aed; margin: 0;">
                        Penerimaan Hari Ini</p>
                    <p style="font-size: 1.8vw; font-weight: 900; color: #5b21b6; margin: 0.2vh 0; line-height: 1;">
                        {{ $todayInbounds }}</p>
                    <p style="font-size: 0.7vw; color: #a78bfa; font-weight: 700; margin: 0;">Dokumen</p>
                </div>
            </div>

            {{-- Tabel Utama (40vh) --}}
            <div style="height: 40vh; display: flex; flex-direction: row; gap: 1.5vw; overflow: hidden;">
                {{-- Pengiriman --}}
                <div
                    style="flex: 1; background: white; border: 1px solid #e2e8f0; border-radius: 1vw; display: flex; flex-direction: column; overflow: hidden;">
                    <div
                        style="padding: 1vh 1.5vw; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                        <h2
                            style="font-size: 0.8vw; font-weight: 900; color: #475569; text-transform: uppercase; margin: 0;">
                            Pengiriman Aktif</h2>
                        <span
                            style="font-size: 0.7vw; font-weight: 800; background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 0.2vh 0.8vw; border-radius: 99px;">{{ $inTransit->count() }}</span>
                    </div>
                    <div class="custom-scrollbar" style="flex: 1; overflow-y: auto;">
                        @forelse($inTransit as $s)
                            <div
                                style="padding: 1.2vh 1.5vw; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                                <div style="min-width: 0; flex: 1;">
                                    <p
                                        style="font-family: monospace; font-size: 0.75vw; font-weight: 800; color: #1e293b; margin: 0;">
                                        {{ $s->shipment_no }}</p>
                                    <p
                                        style="font-size: 0.7vw; color: #64748b; margin: 0.2vh 0 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ optional($s->warehouse)->name }} → {{ optional($s->store)->name }}</p>
                                </div>
                                <span
                                    style="font-size: 0.65vw; font-weight: 900; text-transform: uppercase; padding: 0.4vh 0.8vw; border-radius: 99px; white-space: nowrap; margin-left: 1vw; {{ $s->statusColorCss() }}">{{ $s->statusLabel() }}</span>
                            </div>
                        @empty
                            <div
                                style="padding: 5vh; text-align: center; font-size: 0.8vw; color: #94a3b8; font-style: italic;">
                                Tidak ada pengiriman</div>
                        @endforelse
                    </div>
                </div>

                {{-- Stok Kritis --}}
                <div
                    style="flex: 1; background: white; border: 1px solid #e2e8f0; border-radius: 1vw; display: flex; flex-direction: column; overflow: hidden;">
                    <div
                        style="padding: 1vh 1.5vw; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                        <h2
                            style="font-size: 0.8vw; font-weight: 900; color: #475569; text-transform: uppercase; margin: 0;">
                            Stok Kritis Gudang</h2>
                        <span
                            style="font-size: 0.7vw; font-weight: 800; background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 0.2vh 0.8vw; border-radius: 99px;">≤
                            5</span>
                    </div>
                    <div class="custom-scrollbar" style="flex: 1; overflow-y: auto;">
                        @forelse($lowStock as $stock)
                            @php $v = $stock->variant; @endphp
                            <div
                                style="padding: 1.2vh 1.5vw; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                                <div style="min-width: 0; flex: 1;">
                                    <p
                                        style="font-family: monospace; font-size: 0.75vw; font-weight: 800; color: #334155; text-transform: uppercase; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $v?->product?->name ?? '—' }}</p>
                                    <p style="font-size: 0.65vw; color: #94a3b8; margin: 0.2vh 0 0 0;">{{ $v?->sku }}</p>
                                </div>
                                <span
                                    style="font-size: 1vw; font-weight: 900; color: {{ $stock->qty === 0 ? '#dc2626' : '#f59e0b' }}; margin-left: 1vw;">{{ $stock->qty }}</span>
                            </div>
                        @empty
                            <div
                                style="padding: 5vh; text-align: center; font-size: 0.8vw; color: #94a3b8; font-style: italic;">
                                Semua stok aman</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. FOOTER (Bottom Brand Monitor - 40% Height) --}}
        <div
            style="height: 40vh; background: white; border-top: 1px solid #e2e8f0; padding: 1.5vh 2vw; shrink-0; box-shadow: 0 -10px 30px rgba(0,0,0,0.05); overflow: hidden;">
            <h2
                style="font-size: 0.7vw; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4vw; margin-bottom: 1.5vh; text-align: center;">
                Monitor Stok Brand Per Cabang</h2>
            <div
                style="display: flex; flex-direction: row; gap: 1.5vw; justify-content: space-between; height: calc(100% - 3vh);">
                @foreach($stores->take(3) as $st)
                    <div
                        style="flex: 1; min-width: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 1vw; padding: 1vh; display: flex; flex-direction: column;">
                        <div
                            style="background: #1e293b; border-radius: 0.8vw; padding: 0.8vh; text-align: center; margin-bottom: 1vh; flex-shrink: 0;">
                            <p
                                style="font-size: 0.9vw; font-weight: 900; color: white; text-transform: uppercase; letter-spacing: 0.1vw; margin: 0;">
                                {{ $st->name }}</p>
                        </div>
                        <div
                            style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.8vw; flex: 1; overflow: hidden;">
                            @foreach($brands->take(6) as $br)
                                @php
                                    $brandQty = \App\Models\Stock::where('location_type', 'store')
                                        ->where('location_id', $st->id)
                                        ->whereHas('variant.product', fn($q) => $q->where('brand_id', $br->id))
                                        ->sum('qty');
                                @endphp
                                <div
                                    style="background: white; border: 1px solid #e2e8f0; border-radius: 0.8vw; padding: 0.5vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                    <p
                                        style="font-size: 0.65vw; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%;">
                                        {{ $br->name }}</p>
                                    <p
                                        style="font-size: 1.4vw; font-weight: 900; color: #1e293b; margin: 0.2vh 0 0 0; line-height: 1; {{ $brandQty == 0 ? 'opacity: 0.1;' : '' }}">
                                        {{ number_format($brandQty) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Tombol Buka di Jendela Baru (Hanya di mode Laptop) --}}
    @if(!$isFs)
        <div style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; gap: 10px;">
            <button onclick="openMonitorWindow()"
                style="background: #4f46e5; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                🚀 BUKA DI JENDELA BARU (REKOMENDASI TV)
            </button>
            <a href="{{ route('dashboard') }}"
                style="background: #ef4444; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                KEMBALI
            </a>
        </div>

        <script>
            function openMonitorWindow() {
                const url = window.location.origin + window.location.pathname + '?fs=1';
                const w = 1280;
                const h = 720;
                const left = (screen.width / 2) - (w / 2);
                const top = (screen.height / 2) - (h / 2);
                window.open(url, 'WarehouseMonitor', `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=yes, copyhistory=no, width=${w}, height=${h}, top=${top}, left=${left}`);
            }
        </script>
    @endif

    @if($isFs)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // 1. Cabut monitor dari kurungan layout
                const wrapper = document.getElementById('fs-monitor-wrapper');
                document.body.appendChild(wrapper);

                // 2. Hapus sisa sampah DOM
                Array.from(document.body.children).forEach(child => {
                    if (child.id !== 'fs-monitor-wrapper' && child.tagName !== 'SCRIPT' && child.tagName !== 'STYLE') {
                        child.remove();
                    }
                });

                // 3. Tampilkan body murni
                document.body.style.visibility = 'visible';
                document.body.style.margin = '0';
                document.body.style.padding = '0';
                document.body.style.overflow = 'hidden';
                document.body.style.height = '100vh';
                document.body.style.background = '#f8fafc';

                // 4. Jam Digital
                setInterval(() => {
                    const clock = document.getElementById('liveClock');
                    if (clock) {
                        const now = new Date();
                        clock.innerText = now.toLocaleTimeString('id-ID', { hour12: false });
                    }
                }, 1000);

                // 5. [DIHAPUS] Fullscreen Handler HTML5
                // Sengaja dihapus agar tidak bentrok dengan F11
                // document.body.addEventListener('click', enterFS);

                // 6. AJAX REFRESH
                let timeLeft = 5;
                const triggerRefresh = async () => {
                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.set('_t', Date.now());
                        const response = await fetch(url);
                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContent = doc.getElementById('fs-monitor-wrapper').innerHTML;

                        document.getElementById('fs-monitor-wrapper').innerHTML = newContent;

                        const now = new Date();
                        const timeEl = document.getElementById('last-update-time');
                        if (timeEl) timeEl.innerText = now.toLocaleTimeString('id-ID', { hour12: false });

                        timeLeft = 5;
                    } catch (e) { timeLeft = 5; }
                };

                setInterval(() => {
                    timeLeft--;
                    const timer = document.getElementById('countdown-timer');
                    if (timer) timer.innerText = timeLeft;
                    if (timeLeft <= 0) {
                        triggerRefresh();
                        timeLeft = 5;
                    }
                }, 1000);
            });
        </script>
    @endif
@endsection