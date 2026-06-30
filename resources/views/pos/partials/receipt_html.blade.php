<style>
    .receipt-wrapper { box-sizing: border-box; font-family: 'Courier New', monospace; font-size: 13px; color: #000; background: #fff; width: 72mm; margin: 0 auto; padding: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .receipt-wrapper .center { text-align: center; }
    .receipt-wrapper .bold { font-weight: bold; }
    .receipt-wrapper .divider { border-top: 1px dashed #000; margin: 10px 0; }
    .receipt-wrapper .divider-solid { border-top: 1px solid #000; margin: 10px 0; }
    .receipt-wrapper .row { display: flex; justify-content: space-between; margin-bottom: 4px; }
    .receipt-wrapper .total-row { display: flex; justify-content: space-between; font-weight: bold; font-size: 15px; margin-top: 8px;}
    .receipt-wrapper .item-name { flex: 1; padding-right: 8px; }
    .receipt-wrapper .item-qty { width: 40px; text-align: center; }
    .receipt-wrapper .item-price { width: 85px; text-align: right; }
    .receipt-wrapper .thanks { margin-top: 16px; text-align: center; font-size: 12px; font-weight: bold; }
    .receipt-wrapper .bank-info { margin-top: 10px; font-size: 11px; text-align: center; line-height: 1.4; border: 1px dashed #666; padding: 6px; border-radius: 4px; }
    .receipt-wrapper .barcode-container { margin: 12px 0; text-align: center; }
    .receipt-wrapper .barcode-container svg { width: 100%; max-width: 200px; height: 40px; }
    .receipt-wrapper .barcode-text { font-size: 12px; margin-top: 2px; letter-spacing: 2px; }
    
    @media screen {
        .hide-in-preview { display: none !important; }
        .preview-container { 
            max-height: 70vh; 
            overflow-y: auto; 
            background: #f3f4f6; 
            padding: 20px 0;
            border-radius: 8px;
        }
    }
    
    @media print {
        .no-print, .hide-in-print { display: none !important; }
        .preview-container { background: none !important; padding: 0 !important; overflow: visible !important; }
        .receipt-wrapper { box-shadow: none !important; margin: 0 auto !important; padding: 4mm !important; width: 100% !important; }
    }
</style>

<div class="preview-container">
    @if(auth()->check() && auth()->user()->hasRole('superadmin'))
    <div class="no-print" style="margin: 0 auto 12px; width: 72mm; font-family: sans-serif; text-align: center;">
        <div style="background: #4f46e5; color: white; padding: 6px 12px; border-radius: 8px; font-size: 9px; font-weight: bold; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            SUPERADMIN MODE: PREVIEW STRUK
        </div>
    </div>
    @endif
    <div class="receipt-wrapper">
    <div class="center bold" style="font-size:16px; margin-bottom: 2px; text-transform: uppercase;">{{ $sale?->store?->name }}</div>
    <div class="center" style="font-size:9px; color:#666; margin-bottom: 4px;">SevenKey erp</div>
    @if($sale->store->address)
    <div class="center" style="font-size:11px;color:#444">{{ $sale->store->address }}</div>
    @endif

    <div class="divider"></div>

    <div class="row"><span>No.</span><span class="bold">{{ $sale->sale_no }}</span></div>
    <div class="row"><span>Tgl</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Kasir</span><span>{{ $sale->creator?->name }}</span></div>
    <div class="row"><span>Metode</span><span>{{ strtoupper($sale->paymentMethod?->name) }}</span></div>
    @php
        $statusLabel = strtoupper($sale->payment_status);
    @endphp
    <div class="row bold" style="color: {{ $sale->payment_status === 'lunas' ? '#16a34a' : '#dc2626' }}"><span>Status</span><span>{{ $statusLabel }}</span></div>
    @if($sale->due_date)
    <div class="row"><span>Jatuh Tempo</span><span>{{ \Carbon\Carbon::parse($sale->due_date)->format('d/m/Y') }}</span></div>
    @endif
    @if($sale->customer_name)
    <div class="divider" style="margin: 6px 0;"></div>
    <div class="row"><span>Pelanggan</span><span class="bold">{{ $sale->customer_name }}</span></div>
    @if($sale->customer_phone)
    <div class="row"><span>Telp</span><span>{{ $sale->customer_phone }}</span></div>
    @endif
    @endif

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
            <span class="item-name" style="font-size:12px">@ Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
            <span class="item-qty">x{{ $item->qty }}</span>
            <span class="item-price bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
        </div>
    </div>
    @endforeach

    <div class="divider-solid"></div>

    <div class="row"><span>Subtotal</span><span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
    @if($sale->discount_amount > 0)
    <div class="row"><span>Diskon</span><span style="color:#000">− Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</span></div>
    @endif
    <div class="total-row">
        <span>TOTAL</span>
        <span>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
    </div>
    @php $breakdown = $sale->paymentBreakdown(); @endphp
    @if($sale->isSplitPayment())
    <div class="row bold" style="margin-top:8px"><span>PEMBAYARAN</span><span></span></div>
    @foreach($breakdown as $pay)
    <div class="row"><span>&middot; {{ $pay['name'] }}</span><span>Rp {{ number_format($pay['amount'], 0, ',', '.') }}</span></div>
    @endforeach
    @else
    <div class="row" style="margin-top:8px"><span>Bayar ({{ $sale->payment_status === 'lunas' ? ($sale->paymentMethod?->name ?? 'Tunai') : 'Uang Muka/DP' }})</span><span>Rp {{ number_format($sale->amount_paid, 0, ',', '.') }}</span></div>
    @endif
    @if($sale->payment_status !== 'lunas')
    <div class="row bold" style="color:#dc2626"><span>Sisa Hutang</span><span>Rp {{ number_format(max(0,$sale->total_amount - $sale->amount_paid), 0, ',', '.') }}</span></div>
    @endif
    @if($sale->change_amount > 0)
    <div class="row bold"><span>Kembalian</span><span>Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</span></div>
    @endif

    <div class="divider"></div>

    <div style="margin-top: 15px;">
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
        <div style="text-align:center;padding:5px 0">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(80)->margin(0)->generate($sale->sale_no) !!}
        </div>
        <div class="barcode-container" style="margin: 5px 0;">
            @php
                $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                $barcodeSvg = $generator->getBarcode($sale->sale_no, $generator::TYPE_CODE_128, 2, 40, 'black');
            @endphp
            {!! $barcodeSvg !!}
            <div class="barcode-text" style="font-size: 10px;">{{ $sale->sale_no }}</div>
        </div>

        @if($sale->store->phone && is_array($sale->store->phone))
            @php
                $activePhones = array_filter($sale->store->phone);
                $phoneCount = count($activePhones);
                $cols = $phoneCount <= 2 ? $phoneCount : ceil($phoneCount / 2);
            @endphp
            @if($phoneCount > 0)
                <div class="center" style="font-size:11px; margin-top: 10px; font-weight: bold;">No Telp</div>
                <div style="display: grid; grid-template-columns: repeat({{ $cols }}, 1fr); gap: 2px 4px; font-size: 10px; text-align: center; margin-top: 2px;">
                    @foreach($activePhones as $p)
                        <div>{{ $p }}</div>
                    @endforeach
                </div>
            @endif
        @elseif($sale->store->phone && is_string($sale->store->phone))
            <div class="center" style="font-size:11px; margin-top: 10px;">No Telp</div>
            <div class="center" style="font-size:11px;">{{ $sale->store->phone }}</div>
        @endif

        <div class="thanks">TERIMA KASIH TELAH BERBELANJA</div>
        <div class="center" style="font-size:10px; margin-top: 4px;">Silahkan bawa struk ini untuk retur barang</div>
        <div style="height: 2.5cm;" class="hide-in-preview"></div>
        </div>
</div>
</div>