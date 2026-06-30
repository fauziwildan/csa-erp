@extends('layouts.app')
@section('title', 'Sesi Kasir')
@section('page-title', 'Sesi Kasir')

@push('styles')
<style>
    .ks{ }
    .ks-card{
        background:linear-gradient(165deg, rgba(255,255,255,.9), rgba(255,255,255,.72));
        backdrop-filter:blur(24px) saturate(150%); -webkit-backdrop-filter:blur(24px) saturate(150%);
        border:1px solid rgba(255,255,255,.6);
        border-radius:28px;
        box-shadow:0 1px 2px rgba(16,24,40,.04), 0 24px 50px -24px rgba(60,70,120,.30), inset 0 1px 0 rgba(255,255,255,.85);
    }
    .ks-logo{
        width:56px;height:56px;border-radius:18px;display:flex;align-items:center;justify-content:center;color:#fff;
        background:linear-gradient(135deg,#5B5EF6,#7D82FF);
        box-shadow:0 12px 26px -8px rgba(91,94,246,.6), inset 0 1px 0 rgba(255,255,255,.4);
    }
    .ks-input{
        width:100%; height:60px; border-radius:18px; border:1.5px solid rgba(16,24,40,.08);
        background:rgba(255,255,255,.7); padding:0 18px; font-size:1.4rem; font-weight:800; color:#0f172a;
        outline:none; transition:border-color .2s, box-shadow .2s, background .2s;
    }
    .ks-input:focus{ background:#fff; border-color:#5B5EF6; box-shadow:0 0 0 4px rgba(91,94,246,.16); }
    .ks-amount-wrap{ position:relative; }
    .ks-amount-wrap .rp{ position:absolute; left:18px; top:50%; transform:translateY(-50%); font-weight:700; color:#94a3b8; font-size:1rem; pointer-events:none; }
    .ks-amount-wrap .ks-input{ padding-left:46px; }
    .ks-chip{
        font-size:12px; font-weight:700; color:#475467; padding:8px 14px; border-radius:999px;
        background:rgba(255,255,255,.75); border:1px solid rgba(16,24,40,.08); cursor:pointer; transition:all .15s;
    }
    .ks-chip:hover{ border-color:#5B5EF6; color:#5B5EF6; transform:translateY(-1px); }
    .ks-select{ width:100%; height:50px; border-radius:14px; border:1.5px solid rgba(91,94,246,.18); background:#fff; padding:0 14px; font-size:14px; font-weight:600; color:#334155; outline:none; }
    .ks-select:focus{ border-color:#5B5EF6; box-shadow:0 0 0 4px rgba(91,94,246,.14); }
    .ks-textarea{ width:100%; border-radius:16px; border:1.5px solid rgba(16,24,40,.08); background:rgba(255,255,255,.7); padding:12px 16px; font-size:14px; outline:none; transition:border-color .2s, box-shadow .2s; }
    .ks-textarea:focus{ background:#fff; border-color:#5B5EF6; box-shadow:0 0 0 4px rgba(91,94,246,.14); }
    .ks-btn{
        width:100%; height:58px; border:0; border-radius:18px; cursor:pointer; color:#fff; font-weight:800; font-size:15.5px;
        display:flex; align-items:center; justify-content:center; gap:10px;
        background:linear-gradient(135deg,#5B5EF6,#7D82FF);
        box-shadow:0 14px 28px -10px rgba(91,94,246,.6), inset 0 1px 0 rgba(255,255,255,.3);
        transition:transform .2s, box-shadow .2s;
    }
    .ks-btn:hover{ transform:translateY(-2px); box-shadow:0 22px 40px -10px rgba(91,94,246,.7); }
    .ks-btn:active{ transform:translateY(1px); }
    .ks-store{ display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:999px; font-size:12px; font-weight:700; color:#5B5EF6; background:rgba(91,94,246,.08); border:1px solid rgba(91,94,246,.16); }
    /* padding kartu (pakai CSS sendiri biar tak bergantung build Tailwind) */
    .ks-pad{ padding:1.5rem; }
    @media (min-width:640px){ .ks-pad{ padding:2rem; } }
    .ks-info{ min-height:76px; }
    .ks-center{ padding:1.25rem 0; }
    @media (min-width:768px){ .ks-center{ min-height:calc(100dvh - 8rem); display:flex; align-items:center; padding:2rem 0; } }
</style>
@endpush

@section('content')
<div class="ks max-w-2xl mx-auto w-full px-1">

    {{-- area kartu sesi — dipusatkan vertikal di tablet/desktop biar tidak menempel atas & terasa lega --}}
    <div class="ks-center">
    <div class="w-full">

    {{-- ==========================================
         JIKA ADA SESI KASIR YANG SEDANG AKTIF
         ========================================== --}}
    @if($active)
        <div class="ks-card ks-pad">
            <div class="flex items-center gap-4 mb-6">
                <div class="ks-logo">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <h2 class="text-xl font-extrabold text-gray-900 leading-tight">Sesi Kasir Aktif</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Beroperasi di <span class="ks-store"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l1-5h16l1 5M5 9v11h14V9M9 13h6"/></svg>{{ $store->name }}</span></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="rounded-2xl p-4 border border-indigo-100 bg-indigo-50/70">
                    <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-wider mb-1">Waktu Dibuka</p>
                    <p class="font-extrabold text-indigo-900">{{ \Carbon\Carbon::parse($active->opened_at)->format('d M Y, H:i') }}</p>
                </div>
                <div class="rounded-2xl p-4 border border-indigo-100 bg-indigo-50/70">
                    <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-wider mb-1">Modal Awal di Laci</p>
                    <p class="font-extrabold text-indigo-900">Rp {{ number_format($active->opening_amount, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('pos.index') }}" class="ks-btn flex-1 no-underline">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    Lanjut Transaksi Kasir
                </a>
                <button type="button" onclick="document.getElementById('closeSessionForm').classList.toggle('hidden')" class="sm:w-auto px-6 py-4 rounded-2xl bg-red-50 hover:bg-red-100 border border-red-100 text-red-600 font-bold transition-colors">
                    Tutup Sesi
                </button>
            </div>

            <div id="closeSessionForm" class="hidden mt-6 pt-6 border-t border-gray-100">
                <form method="POST" action="{{ route('pos.session.close') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Total Uang Fisik di Laci Saat Ini (Rp) <span class="text-red-500">*</span></label>
                        <input type="text" inputmode="numeric" name="closing_amount" required class="input-currency w-full bg-gray-50 border border-gray-300 rounded-2xl px-4 py-3.5 focus:bg-white focus:ring-2 focus:ring-red-500 outline-none text-lg font-bold" placeholder="Hitung uang tunai...">
                        <p class="text-[11px] text-gray-500 mt-1.5">Masukkan jumlah seluruh uang tunai fisik di laci kasir saat ini (termasuk modal awal).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Catatan Penutupan</label>
                        <textarea name="notes" rows="2" class="ks-textarea" placeholder="Catatan opsional..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 rounded-2xl shadow-md transition-colors mt-1">
                        Konfirmasi Penutupan Sesi
                    </button>
                </form>
            </div>
        </div>

    {{-- ==========================================
         JIKA TIDAK ADA SESI (HARUS BUKA SESI DULU)
         ========================================== --}}
    @else
        <div class="ks-card ks-pad" x-data="sessionApp()">
            <div class="flex items-center gap-4 mb-7">
                <div class="ks-logo">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div>
                    <h2 class="text-xl font-extrabold text-gray-900 leading-tight">Buka Sesi Kasir</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Toko saat ini <span class="ks-store"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l1-5h16l1 5M5 9v11h14V9M9 13h6"/></svg>{{ $store->name }}</span></p>
                </div>
            </div>

            <form method="POST" action="{{ route('pos.session.open') }}" class="space-y-6" @submit="saveSettings()">
                @csrf

                {{-- Modal Awal --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Modal Awal (Tunai) <span class="text-red-500">*</span></label>
                    <div class="ks-amount-wrap">
                        <span class="rp">Rp</span>
                        <input type="text" inputmode="numeric" name="opening_amount" value="0" required class="input-currency ks-input">
                    </div>
                    <div class="flex flex-wrap gap-2 mt-3">
                        @foreach([0=>'Rp 0', 100000=>'100rb', 200000=>'200rb', 500000=>'500rb', 1000000=>'1 jt'] as $val => $lbl)
                            <button type="button" class="ks-chip" onclick="ksSetModal({{ $val }})">{{ $lbl }}</button>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">Uang tunai awal di laci saat memulai shift. Ketuk nominal cepat atau ketik manual.</p>
                </div>

                {{-- Metode Cetak --}}
                <div class="rounded-2xl p-4 bg-indigo-50/60 border border-indigo-100">
                    <label class="flex items-center gap-2 text-sm font-bold text-indigo-900 mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a1 1 0 001-1v-4a1 1 0 00-1-1H9a1 1 0 00-1 1v4a1 1 0 001 1zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h14z"/></svg>
                        Metode Cetak Struk <span class="font-medium text-indigo-400">(perangkat ini)</span>
                    </label>
                    <select x-model="device" class="ks-select">
                        <option value="ios_bluefy">1. iOS / iPad (Browser Bluefy)</option>
                        <option value="pc_usb">2. Laptop / PC (Kabel USB)</option>
                        <option value="pc_bluetooth">3. Laptop / PC (Bluetooth Murni)</option>
                        <option value="android_flutter">4. Android (Aplikasi SevenKey POS)</option>
                        <option value="android_bluetooth">5. Android (Browser Chrome Bluetooth)</option>
                    </select>

                    <div class="mt-3 p-3.5 bg-white rounded-xl text-xs text-gray-600 border border-indigo-50 shadow-sm leading-relaxed ks-info">
                        <p x-show="device === 'ios_bluefy'"><strong>⚠️ iOS:</strong> Buka web ini lewat aplikasi <strong>Bluefy</strong> (App Store). Safari tidak mendukung. Pastikan Bluetooth iPad menyala.</p>
                        <p x-show="device === 'pc_usb'"><strong>💡 PC USB:</strong> Pastikan driver printer generic (POS-80) terinstal. Saat mencetak, muncul pop-up print bawaan sistem — pastikan nama printer benar.</p>
                        <p x-show="device === 'pc_bluetooth'"><strong>⚡ PC Bluetooth:</strong> Transaksi pertama, Chrome minta izin pairing. Selama tak refresh (F5), cetak berikutnya otomatis tanpa pop-up.</p>
                        <p x-show="device === 'android_flutter'"><strong>📱 Aplikasi:</strong> Buka halaman ini dari aplikasi <strong>SevenKey POS</strong>. Koneksi Bluetooth ditangani otomatis.</p>
                        <p x-show="device === 'android_bluetooth'"><strong>⚠️ Android Chrome:</strong> Pastikan URL HTTPS. Pilih nama printer di pop-up Chrome saat mencetak.</p>
                    </div>

                    <button type="button" @click="testPrint()" class="mt-3 inline-flex items-center gap-2 bg-white hover:bg-indigo-600 hover:text-white text-indigo-600 border border-indigo-200 text-xs font-bold px-4 py-2 rounded-xl transition-colors shadow-sm">
                        🖨️ Test Print Perangkat Ini
                    </button>
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Catatan <span class="font-medium text-gray-400">(opsional)</span></label>
                    <textarea name="notes" rows="2" class="ks-textarea" placeholder="Mis. shift pagi, pengganti kasir, dll."></textarea>
                </div>

                <button type="submit" class="ks-btn">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Mulai Sesi Kasir Baru
                </button>
            </form>
        </div>

        <script>
        function ksSetModal(v){
            var el = document.querySelector('input[name="opening_amount"]');
            if(!el) return;
            el.value = Number(v).toLocaleString('id-ID');
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('keyup', { bubbles: true }));
        }
        function sessionApp() {
            return {
                device: localStorage.getItem('pos_print_method') || 'pc_usb',
                saveSettings() { localStorage.setItem('pos_print_method', this.device); },
                async testPrint() {
                    if (this.device === 'pc_usb') {
                        let printFrame = document.createElement('iframe');
                        printFrame.style.display = 'none';
                        document.body.appendChild(printFrame);
                        let testHtml = "<div style='font-family:monospace; text-align:center; padding: 10px; width: 72mm;'><h3>TEST PRINT USB</h3><p>Koneksi Berhasil!</p><p>SevenKey ERP</p></div><div style='height:2cm;'></div>";
                        printFrame.contentDocument.write('<html><head><style>@page { margin: 0; } body { margin: 0; }</style></head><body>' + testHtml + '</body></html>');
                        printFrame.contentDocument.close();
                        printFrame.contentWindow.focus();
                        printFrame.contentWindow.print();
                        setTimeout(() => document.body.removeChild(printFrame), 2000);
                    }
                    else if (this.device === 'android_flutter') {
                        const dummyData = {
                            store_name: "TEST FLUTTER", store_address: "Koneksi Printer Berhasil!",
                            receipt_no: "TEST-000", date: "Hari ini", cashier: "Sistem",
                            items: [{ name: "Kertas Test", qty: 1, price: "0", total: "0" }],
                            subtotal: "0", grand_total: "0", paid: "0"
                        };
                        if (window.PrintChannel) window.PrintChannel.postMessage(JSON.stringify(dummyData));
                        else alert("Buka lewat aplikasi Flutter!");
                    }
                    else {
                        try {
                            const text = "\nTEST OK - " + this.device.toUpperCase() + "\n\n";
                            const btDevice = await navigator.bluetooth.requestDevice({
                                acceptAllDevices: true,
                                optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb']
                            });
                            const server = await btDevice.gatt.connect();
                            const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
                            const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');
                            const encoder = new TextEncoder();
                            const payload = encoder.encode(text);
                            for (let i = 0; i < payload.length; i += 40) {
                                await characteristic.writeValue(payload.slice(i, i + 40));
                            }
                            btDevice.gatt.disconnect();
                        } catch (e) { alert("Gagal Bluetooth: " + e.message); }
                    }
                }
            }
        }
        </script>
    @endif

    </div>
    </div>{{-- /area kartu sesi --}}

    {{-- ==========================================
         RIWAYAT SESI SEBELUMNYA
         ========================================== --}}
    <div class="ks-card overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100/80 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Riwayat Sesi Terakhir</h2>
        </div>

        <div class="divide-y divide-gray-100/80">
            @forelse($history as $h)
                <div class="p-5 sm:p-6 hover:bg-white/40 transition-colors">
                    <div class="flex justify-between items-start mb-4 gap-3">
                        <div>
                            <p class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ \Carbon\Carbon::parse($h->opened_at)->format('d/m/Y H:i') }} &rarr; {{ $h->closed_at ? \Carbon\Carbon::parse($h->closed_at)->format('H:i') : 'Belum Ditutup' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">oleh <span class="font-semibold">{{ $h->user?->name ?? 'User dihapus' }}</span></p>
                        </div>
                        <div class="text-right shrink-0">
                            @php $selisih = $h->closing_amount - $h->expected_amount; @endphp
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border {{ $selisih == 0 ? 'bg-green-50 border-green-200 text-green-700' : ($selisih > 0 ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-red-50 border-red-200 text-red-600') }}">
                                <span class="text-[10px] font-bold uppercase tracking-wider">Selisih</span>
                                <span class="text-sm font-black">Rp {{ number_format($selisih, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="bg-white/70 p-3 rounded-xl border border-gray-100">
                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-1">Modal Awal</p>
                            <p class="text-sm font-black text-gray-800">Rp {{ number_format($h->opening_amount, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-white/70 p-3 rounded-xl border border-gray-100">
                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-1">Harapan Sistem</p>
                            <p class="text-sm font-black text-blue-600">Rp {{ number_format($h->expected_amount, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-white/70 p-3 rounded-xl border border-gray-100">
                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-1">Aktual di Laci</p>
                            <p class="text-sm font-black text-indigo-600">Rp {{ number_format($h->closing_amount, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-100/70 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm text-gray-400 font-medium">Belum ada riwayat sesi kasir di toko ini.</p>
                </div>
            @endforelse
        </div>

        @if($history->hasPages())
            <div class="p-4">{{ $history->links() }}</div>
        @endif
    </div>

</div>
@endsection
