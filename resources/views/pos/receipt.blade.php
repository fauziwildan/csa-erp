<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $sale->sale_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Courier New', monospace; font-size: 13px; color: #000; background: #fff; width: 72mm; margin: 0 auto; padding: 12px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        .divider-solid { border-top: 1px solid #000; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .row-right { text-align: right; }
        .total-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 15px; margin-top: 8px;}
        .item-name { flex: 1; padding-right: 8px; }
        .item-qty { width: 40px; text-align: center; }
        .item-price { width: 85px; text-align: right; }
        .thanks { margin-top: 16px; text-align: center; font-size: 12px; font-weight: bold; }
        .bank-info { margin-top: 10px; font-size: 11px; text-align: center; line-height: 1.4; border: 1px dashed #666; padding: 6px; border-radius: 4px; }
        
        .barcode-container { margin: 12px 0; text-align: center; }
        .barcode-container svg { width: 100%; max-width: 200px; height: 40px; }
        .barcode-text { font-size: 12px; margin-top: 2px; letter-spacing: 2px; }
        
        @media print {
            @page { margin: 0; }
            body { width: 100%; padding: 4mm; margin: 0; }
            .no-print { display: none; }
            .print-spacer { height: 2.5cm; } 
        }
    </style>
</head>
<body>

<div class="center bold" style="font-size:22px; margin-bottom: 6px;">SevenKey ERP</div>
<div class="center bold" style="font-size:14px; margin-bottom: 2px;">{{ $sale?->store?->name }}</div>
@if($sale->store->address)
<div class="center" style="font-size:11px;color:#444">{{ $sale->store->address }}</div>
@endif

<div class="divider"></div>

<div class="row"><span>No.</span><span class="bold">{{ $sale->sale_no }}</span></div>
<div class="row"><span>Tgl</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
<div class="row"><span>Kasir</span><span>{{ $sale->creator?->name }}</span></div>
<div class="row"><span>Metode</span><span>{{ strtoupper($sale->paymentMethodLabel()) }}</span></div>

<div class="divider"></div>

<div style="margin-bottom:6px">
    <div class="row bold" style="font-size:12px;text-transform:uppercase;">
        <span class="item-name">Item</span>
        <span class="item-qty">Qty</span>
        <span class="item-price">Total</span>
    </div>
</div>
<div class="divider-solid"></div>

@foreach($sale->items as $item)
@php 
    $v = $item->variant; 
    $itemTypeLabel = $item->is_ecer ? '(Ecer)' : '(Grosir)';
@endphp
<div style="margin-bottom:8px">
    <div class="bold" style="font-size:13px">{{ $v?->product?->name }} <span style="font-size: 11px; color: #444;">{{ $itemTypeLabel }}</span></div>
    <div style="font-size:11px;color:#444;margin-bottom:2px;">{{ $v?->sku }} · {{ $v?->color?->name }} / {{ $v?->size?->name }}</div>
    <div class="row">
        <span class="item-name" style="font-size:12px">@ {{ number_format($item->unit_price, 0, ',', '.') }}</span>
        <span class="item-qty">x{{ $item->qty }}</span>
        <span class="item-price bold">{{ number_format($item->subtotal, 0, ',', '.') }}</span>
    </div>
</div>
@endforeach

<div class="divider-solid"></div>

<div class="row"><span>Subtotal</span><span>{{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
@if($sale->discount_amount > 0)
<div class="row"><span>Diskon</span><span style="color:#000">− {{ number_format($sale->discount_amount, 0, ',', '.') }}</span></div>
@endif
<div class="total-row">
    <span>TOTAL</span>
    <span>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
</div>
@if($sale->isSplitPayment())
<div class="row bold" style="margin-top:8px"><span>PEMBAYARAN</span><span></span></div>
@foreach($sale->paymentBreakdown() as $pay)
<div class="row" style="font-size:12px"><span>· {{ $pay['name'] }}</span><span>{{ number_format($pay['amount'], 0, ',', '.') }}</span></div>
@endforeach
<div class="row bold"><span>Total Bayar</span><span>{{ number_format($sale->amount_paid, 0, ',', '.') }}</span></div>
@else
<div class="row" style="margin-top:8px"><span>Bayar</span><span>{{ number_format($sale->amount_paid, 0, ',', '.') }}</span></div>
@endif
@if($sale->change_amount > 0)
<div class="row bold"><span>Kembalian</span><span>{{ number_format($sale->change_amount, 0, ',', '.') }}</span></div>
@endif

<div class="divider"></div>

@if($sale->store->bank_name || $sale->store->bank_account)
<div class="bank-info">
    <div class="bold" style="margin-bottom:2px">PEMBAYARAN TRANSFER:</div>
    @if($sale->store->bank_name)
    <div>Bank: <span class="bold">{{ $sale->store->bank_name }}</span></div>
    @endif
    @if($sale->store->bank_account)
    <div style="font-size:12px;" class="bold">{{ $sale->store->bank_account }}</div>
    @endif
    @if($sale->store->bank_account_name)
    <div>A.N. <span class="bold">{{ $sale->store->bank_account_name }}</span></div>
    @endif
</div>
<div class="divider"></div>
@endif

<!-- QR Code dan Barcode -->
<div style="text-align:center;padding:10px 0">
    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->margin(0)->generate($sale->sale_no) !!}
</div>
<div class="barcode-container">
    @php
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $barcodeSvg = $generator->getBarcode($sale->sale_no, $generator::TYPE_CODE_128, 2, 50, 'black');
    @endphp
    {!! $barcodeSvg !!}
    <div class="barcode-text">{{ $sale->sale_no }}</div>
</div>

<div class="thanks">TERIMA KASIH ATAS KUNJUNGAN ANDA</div>
<div class="center" style="font-size:10px; margin-top: 4px;">Barang yang sudah dibeli dapat diretur dengan menunjukkan struk ini.</div>

<div class="print-spacer"></div>

<!-- Tombol Aksi Web (Sembunyi saat diprint) -->
<div class="no-print" style="margin-top:20px;text-align:center;font-family:sans-serif; display:flex; flex-direction:column; gap:10px;">
    <button onclick="window.print()"
        style="background:#f3f4f6;color:#374151;border:none;padding:14px;border-radius:12px;font-size:14px;cursor:pointer;font-weight:bold;width:100%; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
        🖨️ Cetak (Printer Kasir / PDF)
    </button>
    <button onclick="printKeAplikasiPos()"
        style="background:#4f46e5;color:white;border:none;padding:14px;border-radius:12px;font-size:14px;cursor:pointer;font-weight:bold;width:100%; box-shadow:0 4px 10px rgba(79,70,229,0.3);">
        🔵 Cetak via Aplikasi Bluetooth
    </button>
</div>

<script>
    function printKeAplikasiPos() {
        const dataStruk = {
            store_name: "{{ $sale?->store?->name }}",
            store_address: "{{ $sale->store->address ?? '' }}",
            receipt_no: "{{ $sale->sale_no }}",
            date: "{{ $sale->created_at->format('d/m/Y H:i') }}",
            cashier: "{{ substr($sale->creator?->name, 0, 15) }}",
            
            items: [
                @foreach($sale->items as $item)
                {
                    name: "{{ $item->variant?->product?->name }}",
                    qty: "{{ $item->qty }}",
                    price: "{{ number_format($item->unit_price, 0, ',', '.') }}",
                    total: "{{ number_format($item->subtotal, 0, ',', '.') }}"
                },
                @endforeach
            ],
            
            subtotal: "{{ number_format($sale->subtotal, 0, ',', '.') }}",
            grand_total: "{{ number_format($sale->total_amount, 0, ',', '.') }}",
            paid: "{{ number_format($sale->amount_paid, 0, ',', '.') }}"
        };

        if (window.PrintChannel) {
            window.PrintChannel.postMessage(JSON.stringify(dataStruk));
        } else {
            alert("Fitur ini hanya berjalan jika Anda membuka sistem melalui Aplikasi SevenKey POS di Android/iOS.");
        }
    }
</script>
</body>
</html>