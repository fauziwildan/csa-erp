<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pengiriman {{ $shipment->shipment_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 16px; }
        .company { font-size: 18px; font-weight: bold; color: #3730a3; }
        .doc-title { font-size: 14px; font-weight: bold; text-align: right; }
        .doc-no { font-size: 20px; font-weight: bold; color: #3730a3; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .meta-block label { font-size: 10px; text-transform: uppercase; color: #666; letter-spacing: 0.5px; }
        .meta-block p { font-weight: 600; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; font-size: 11px; text-align: center; vertical-align: middle; }
        th { background: #e0e7ff; padding: 8px 10px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
        td { padding: 7px 10px; }
        tfoot td { font-weight: bold; background: #f9fafb; border-top: 2px solid #333; }
        .qr-section { display: flex; justify-content: flex-end; margin-top: 20px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .sig-box { text-align: center; width: 160px; }
        .sig-box .line { border-top: 1px solid #333; margin-top: 60px; padding-top: 4px; font-size: 11px; }
        .status-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; background: #e0e7ff; color: #3730a3; font-weight: 600; }
        @media print { button { display: none; } }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <div class="company">SevenKey ERP</div>
            <div style="color:#666;margin-top:2px;">Fashion Retail Management System</div>
        </div>
        <div style="text-align:right">
            <div class="doc-title">SURAT PENGIRIMAN BARANG</div>
            <div class="doc-no">{{ $shipment->shipment_no }}</div>
            <div style="margin-top:4px"><span class="status-badge">{{ $shipment->statusLabel() }}</span></div>
            <svg id="docBarcode" style="margin-top:6px;max-width:180px"></svg>
        </div>
    </div>

    <div class="meta">
        <div>
            <div class="meta-block" style="margin-bottom:8px">
                <label>Dari Gudang</label>
                <p>{{ optional($shipment->warehouse)->name ?? '—' }}</p>
                <small style="color:#666">{{ optional($shipment->warehouse)->address ?? '' }}</small>
            </div>
            <div class="meta-block">
                <label>Ke Toko</label>
                <p>{{ optional($shipment->store)->name ?? '—' }}</p>
                <small style="color:#666">{{ optional($shipment->store)->address ?? '' }}</small>
            </div>
        </div>
        <div>
            <div class="meta-block" style="margin-bottom:8px">
                <label>Tanggal Dibuat</label>
                <p>{{ $shipment->created_at->format('d F Y') }}</p>
            </div>
            @if($shipment->shipped_at)
            <div class="meta-block" style="margin-bottom:8px">
                <label>Tanggal Kirim</label>
                <p>{{ $shipment->shipped_at->format('d F Y H:i') }}</p>
            </div>
            @endif
            <div class="meta-block">
                <label>Dibuat Oleh</label>
                <p>{{ $shipment->creator?->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    @if($shipment->notes)
    <p style="margin-bottom:12px;color:#555;font-size:11px;font-style:italic">Catatan: {{ $shipment->notes }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>SKU</th>
                <th>Produk</th>
                <th>Warna</th>
                <th>Ukuran</th>
                <th>Qty Kirim</th>
                <th>Qty Terima</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shipment->items as $i => $item)
            @php $v = $item->variant; @endphp
            <tr>
                <td style="color:#999">{{ $i+1 }}</td>
                <td style="font-family:monospace">{{ $v?->sku }}</td>
                <td>{{ $v?->product?->name }}</td>
                <td>{{ $v?->color?->name }}</td>
                <td>{{ $v?->size?->name }}</td>
                <td style="font-weight:600">{{ $item->qty_sent }}</td>
                <td style="color:#666">{{ $item->qty_received ?: '____' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">TOTAL</td>
                <td>{{ $shipment->totalQtySent() }}</td>
                <td>{{ $shipment->totalQtyReceived() ?: '____' }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="sig-box">
            <div class="line">Disiapkan Oleh<br><strong>{{ $shipment->creator?->name ?? '___________' }}</strong></div>
        </div>
        <div class="sig-box">
            <div class="line">Pengirim<br><strong>{{ $shipment->shipper?->name ?? '___________' }}</strong></div>
        </div>
        <div class="sig-box">
            <div class="line">Penerima Toko<br><strong>{{ $shipment->receiver?->name ?? '___________' }}</strong></div>
        </div>
    </div>

    <div style="margin-top:20px;text-align:center">
        <button onclick="window.print()" style="padding:8px 24px;background:#3730a3;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px">
            Cetak Dokumen
        </button>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
JsBarcode("#docBarcode", "{{ $shipment->shipment_no }}", {
    format: 'CODE128', width: 1.5, height: 40, displayValue: true,
    fontSize: 9, margin: 0, background: 'transparent'
});
</script>
</body>
</html>