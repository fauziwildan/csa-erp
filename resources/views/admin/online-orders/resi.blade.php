<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Resi {{ $onlineOrder->order_no }}</title>
<style>
    @page { size: A5; margin: 8mm; }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:"Segoe UI",Arial,sans-serif;color:#111;font-size:11px;background:#f3f4f6}
    .sheet{width:148mm;min-height:210mm;background:#fff;margin:12px auto;padding:8mm;position:relative}

    .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #111;padding-bottom:6px}
    .brand{font-size:20px;font-weight:800;letter-spacing:-.5px}
    .brand small{display:block;font-size:8px;font-weight:600;letter-spacing:2px;color:#666;margin-top:1px}
    .doc{text-align:right}
    .doc .t{font-size:10px;font-weight:700;letter-spacing:1.5px;color:#666}
    .doc .no{font-family:monospace;font-size:14px;font-weight:800;margin-top:2px}
    .doc .date{font-size:9px;color:#666;margin-top:1px}

    .row{display:flex;gap:6mm;margin-top:6px}
    .box{flex:1;border:1px solid #ddd;border-radius:4px;padding:6px 8px}
    .box .lbl{font-size:8px;font-weight:700;letter-spacing:1px;color:#888;text-transform:uppercase;margin-bottom:3px}
    .box .nm{font-size:12px;font-weight:700}
    .box .ln{font-size:10px;line-height:1.45;color:#333;white-space:pre-line}

    table{width:100%;border-collapse:collapse;margin-top:8px}
    th{background:#111;color:#fff;font-size:8.5px;text-transform:uppercase;letter-spacing:.6px;padding:5px 6px;text-align:left}
    th.r,td.r{text-align:right}
    td{padding:5px 6px;border-bottom:1px solid #eee;font-size:10px;vertical-align:top}
    td .sku{font-family:monospace;font-size:8.5px;color:#777;display:block}
    tfoot td{border-top:2px solid #111;border-bottom:none;font-weight:800;font-size:12px;padding-top:6px}

    .pay{margin-top:8px;border:1px dashed #111;border-radius:4px;padding:7px 9px;background:#fafafa}
    .pay .l{font-size:8px;font-weight:700;letter-spacing:1px;color:#888;text-transform:uppercase}
    .pay .v{font-size:11px;font-weight:700;margin-top:2px}

    .note{margin-top:6px;font-size:9px;color:#555}
    .foot{position:absolute;left:8mm;right:8mm;bottom:8mm;border-top:1px solid #ddd;padding-top:5px;display:flex;justify-content:space-between;font-size:8px;color:#888}
    .sign{margin-top:10px;display:flex;justify-content:space-between;gap:6mm}
    .sign div{flex:1;text-align:center;font-size:9px;color:#666}
    .sign .line{margin-top:34px;border-top:1px solid #999;padding-top:3px}

    .toolbar{max-width:148mm;margin:10px auto 0;text-align:right}
    .toolbar button{background:#111;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer}
    .toolbar a{font-size:12px;color:#555;margin-right:10px;text-decoration:none}
    @media print { body{background:#fff} .toolbar{display:none} .sheet{margin:0;width:auto;min-height:auto;padding:0} }
</style>
</head>
<body>

<div class="toolbar">
    <a href="{{ route('online-orders.show', $onlineOrder) }}">← Kembali</a>
    <button onclick="window.print()">🖨️ Print A5</button>
</div>

<div class="sheet">
    <div class="head">
        <div class="brand">WONDERKEY<small>CLOTHING BRAND</small></div>
        <div class="doc">
            <div class="t">RESI PENGIRIMAN</div>
            <div class="no">{{ $onlineOrder->order_no }}</div>
            <div class="date">{{ $onlineOrder->created_at->format('d M Y H:i') }}</div>
        </div>
    </div>

    <div class="row">
        <div class="box">
            <div class="lbl">Pengirim</div>
            <div class="nm">Wonderkey</div>
            <div class="ln">{{ optional($onlineOrder->warehouse)->name ?? 'Gudang Utama' }}
{{ optional($onlineOrder->warehouse)->address ?? '' }}
{{ optional($onlineOrder->warehouse)->phone ?? '' }}</div>
        </div>
        <div class="box">
            <div class="lbl">Penerima</div>
            <div class="nm">{{ $onlineOrder->customer_name }}</div>
            <div class="ln">{{ $onlineOrder->customer_phone }}
{{ $onlineOrder->address }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:52%">Produk</th>
                <th style="width:16%">Varian</th>
                <th class="r" style="width:8%">Qty</th>
                <th class="r" style="width:24%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($onlineOrder->items as $it)
            <tr>
                <td>{{ $it->product_name }}<span class="sku">{{ $it->sku }}</span></td>
                <td>{{ $it->color }} / {{ $it->size }}</td>
                <td class="r">{{ $it->qty }}</td>
                <td class="r">Rp {{ number_format($it->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                <td class="r">{{ $onlineOrder->total_qty }}</td>
                <td class="r">Rp {{ number_format($onlineOrder->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="pay">
        <div class="l">Pembayaran</div>
        <div class="v">
            {{ $onlineOrder->payment_note ?: 'Transfer saat barang sampai' }}
            @if($onlineOrder->isPaid())
                <span style="color:#15803d"> — LUNAS ({{ optional($onlineOrder->paid_at)->format('d/m/Y') }})</span>
            @endif
        </div>
    </div>

    @if($onlineOrder->notes)
    <div class="note"><b>Catatan:</b> {{ $onlineOrder->notes }}</div>
    @endif

    <div class="sign">
        <div><div class="line">Pengirim</div></div>
        <div><div class="line">Kurir</div></div>
        <div><div class="line">Penerima</div></div>
    </div>

    <div class="foot">
        <span>Wonderkey · catalog.wonderkey.store</span>
        <span>{{ $onlineOrder->order_no }}</span>
    </div>
</div>

</body>
</html>
