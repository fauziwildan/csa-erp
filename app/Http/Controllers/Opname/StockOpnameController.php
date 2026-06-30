<?php

namespace App\Http\Controllers\Opname;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\ReferenceNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('view stock opname');
        $user       = Auth::user();
        $warehouses = Warehouse::orderBy('name')->get();
        $stores     = Store::orderBy('name')->get();

        $q = StockOpname::with(['creator'])
            ->when($r->status,        fn($q) => $q->where('status', $r->status))
            ->when($r->location_type, fn($q) => $q->where('location_type', $r->location_type))
            ->orderBy('created_at', 'desc');

        if (!$user->hasAnyRole(['superadmin', 'owner', 'finance', 'admin gudang'])) {
            $storeIds = $user->stores->pluck('id')->toArray();
            $q->where(fn($q) => $q
                ->where(fn($q) => $q->where('location_type', 'store')->whereIn('location_id', $storeIds))
            );
        }

        $opnames = $q->paginate(20)->withQueryString();
        return view('opname.index', compact('opnames', 'warehouses', 'stores'));
    }

    public function create()
    {
        $this->authorize('create stock opname');
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $stores     = Store::where('is_active', true)->orderBy('name')->get();
        return view('opname.create', compact('warehouses', 'stores'));
    }

    public function store(Request $r)
    {
        $this->authorize('create stock opname');
        $r->validate([
            'location_type' => 'required|in:warehouse,store',
            'location_id'   => 'required|integer',
            'notes'         => 'nullable|string|max:500',
        ]);

        $opname = DB::transaction(function () use ($r) {
            $opname = StockOpname::create([
                'opname_no'     => ReferenceNumberService::opname(),
                'location_type' => $r->location_type,
                'location_id'   => $r->location_id,
                'status'        => 'draft',
                'notes'         => $r->notes,
                'created_by'    => Auth::id(),
            ]);

            $stocks = Stock::where('location_type', $r->location_type)
                ->where('location_id', $r->location_id)
                ->where('qty', '>', 0)
                ->get();

            foreach ($stocks as $stock) {
                StockOpnameItem::create([
                    'stock_opname_id'    => $opname->id,
                    'product_variant_id' => $stock->product_variant_id,
                    'qty_system'         => $stock->qty,
                    'qty_actual'         => null,
                    'qty_difference'     => null,
                ]);
            }

            AuditLogService::log('create', 'StockOpname', "Buat opname {$opname->opname_no}", null, null, StockOpname::class, $opname->id);
            return $opname;
        });

        return redirect()->route('opname.show', $opname)->with('success', "Opname {$opname->opname_no} dibuat. Masukkan hitungan aktual.");
    }

    public function show(StockOpname $opname)
    {
        $this->authorize('view stock opname');
        $opname->load(['creator', 'submitter', 'approver', 'rejecter', 'items.variant.product', 'items.variant.color', 'items.variant.size']);
        return view('opname.show', compact('opname'));
    }

    public function submit(Request $r, StockOpname $opname)
    {
        $this->authorize('submit stock opname');

        if (!$opname->isDraft()) {
            return back()->with('error', 'Opname tidak dalam status draft.');
        }

        $r->validate([
            'items'                    => 'nullable|array',
            'items.*.id'               => 'required|exists:stock_opname_items,id',
            'items.*.qty_actual'       => 'required|integer|min:0',
            'items.*.is_ecer'          => 'nullable|boolean',
            // Varian temuan yang tidak ada di snapshot (mis. stok sistem 0)
            'new_items'                => 'nullable|array',
            'new_items.*.variant_id'   => 'required|exists:product_variants,id',
            'new_items.*.qty_actual'   => 'required|integer|min:0',
            'new_items.*.is_ecer'      => 'nullable|boolean',
        ]);

        if (empty($r->items) && empty($r->new_items)) {
            return back()->with('error', 'Tidak ada item untuk disubmit. Tambahkan minimal satu varian.');
        }

        DB::transaction(function () use ($r, $opname) {
            foreach ($r->items ?? [] as $row) {
                $item       = StockOpnameItem::findOrFail($row['id']);
                $qtyActual  = (int) $row['qty_actual'];
                $item->update([
                    'qty_actual'     => $qtyActual,
                    'qty_difference' => $qtyActual - $item->qty_system,
                    'is_ecer'        => isset($row['is_ecer']) ? filter_var($row['is_ecer'], FILTER_VALIDATE_BOOLEAN) : false,
                ]);
            }

            // Tambahkan varian temuan baru — qty_system diambil dari stok terkini lokasi ini.
            foreach ($r->new_items ?? [] as $row) {
                $variantId = (int) $row['variant_id'];

                // Lewati jika varian sudah ada di opname ini (hindari duplikat)
                if ($opname->items()->where('product_variant_id', $variantId)->exists()) {
                    continue;
                }

                $sysQty = Stock::where('product_variant_id', $variantId)
                    ->where('location_type', $opname->location_type)
                    ->where('location_id', $opname->location_id)
                    ->value('qty') ?? 0;

                $qtyActual = (int) $row['qty_actual'];
                StockOpnameItem::create([
                    'stock_opname_id'    => $opname->id,
                    'product_variant_id' => $variantId,
                    'qty_system'         => $sysQty,
                    'qty_actual'         => $qtyActual,
                    'qty_difference'     => $qtyActual - $sysQty,
                    'is_ecer'            => isset($row['is_ecer']) ? filter_var($row['is_ecer'], FILTER_VALIDATE_BOOLEAN) : false,
                ]);
            }

            $opname->update([
                'status'       => 'submitted',
                'submitted_at' => now(),
                'submitted_by' => Auth::id(),
            ]);
        });

        AuditLogService::log('submit', 'StockOpname', "Submit opname {$opname->opname_no}", null, null, StockOpname::class, $opname->id);
        return back()->with('success', 'Opname disubmit untuk persetujuan.');
    }

    public function approve(Request $r, StockOpname $opname)
    {
        $this->authorize('approve stock opname');

        if (!$opname->isSubmitted()) {
            return back()->with('error', 'Opname belum disubmit.');
        }

        $r->validate(['rejection_reason' => 'nullable|string|max:500']);

        if ($r->action === 'reject') {
            $r->validate(['rejection_reason' => 'required|string|max:500']);
            $opname->update([
                'status'           => 'rejected',
                'rejection_reason' => $r->rejection_reason,
                'rejected_at'      => now(),
                'rejected_by'      => Auth::id(),
            ]);
            AuditLogService::log('reject', 'StockOpname', "Tolak opname {$opname->opname_no}", null, null, StockOpname::class, $opname->id);
            return back()->with('success', 'Opname ditolak.');
        }

        DB::transaction(function () use ($opname) {
            $opname->load('items.variant');
            foreach ($opname->items as $item) {
                if ($item->qty_difference !== null && $item->qty_difference !== 0) {
                    StockService::mutate(
                        $item->variant,
                        $opname->location_type,
                        $opname->location_id,
                        $item->qty_difference,
                        'opname',
                        "Penyesuaian opname {$opname->opname_no}",
                        StockOpname::class,
                        $opname->id
                    );
                }
            }

            $opname->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);
        });

        AuditLogService::log('approve', 'StockOpname', "Setujui opname {$opname->opname_no}", null, null, StockOpname::class, $opname->id);
        return back()->with('success', 'Opname disetujui. Stok sudah disesuaikan.');
    }
    public function destroy(StockOpname $opname)
    {
        $this->authorize('delete stock opname');

        // Proteksi: Pastikan hanya draft yang bisa dihapus
        if (!$opname->isDraft()) {
            return back()->with('error', 'Gagal: Hanya Stock Opname dengan status Draft yang dapat dihapus.');
        }

        DB::transaction(function () use ($opname) {
            // Hapus semua baris item varian di dalamnya terlebih dahulu
            $opname->items()->delete();
            
            // Catat di Audit Log siapa yang menghapus
            AuditLogService::log('delete', 'StockOpname', "Hapus draft opname {$opname->opname_no}", $opname->toArray(), null, StockOpname::class, $opname->id);
            
            // Hapus dokumen utamanya
            $opname->delete();
        });

        return redirect()->route('opname.index')->with('success', 'Draft Stock Opname berhasil dihapus dari sistem.');
    }
}
