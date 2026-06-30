<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\Store;
use App\Services\AuditLogService;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettlementController extends Controller
{
    public function index()
    {
        $this->authorize('view settlement');
        $user = Auth::user();

        $stores = $user->hasAnyRole(['owner', 'superadmin', 'finance'])
            ? Store::where('is_active', true)->orderBy('name')->get()
            : $user->stores()->where('is_active', true)->orderBy('name')->get();

        $rows = $stores->map(fn ($store) => array_merge(['store' => $store], SettlementService::summary($store)));

        $totals = [
            'obligation'  => $rows->sum('obligation'),
            'settled'     => $rows->sum('settled'),
            'outstanding' => $rows->sum('outstanding'),
        ];

        return view('settlements.index', compact('rows', 'totals'));
    }

    public function show(Store $store)
    {
        $this->authorize('view settlement');
        $this->ensureAccess($store);

        $summary = SettlementService::summary($store);
        $settlements = Settlement::where('store_id', $store->id)
            ->with('recorder')->orderByDesc('paid_at')->orderByDesc('id')->paginate(20);

        return view('settlements.show', compact('store', 'summary', 'settlements'));
    }

    public function store(Request $request, Store $store)
    {
        $this->authorize('manage settlement');

        $validated = $request->validate([
            'amount'  => ['required', 'numeric', 'min:1'],
            'paid_at' => ['required', 'date'],
            'method'  => ['required', 'in:cash,transfer'],
            'note'    => ['nullable', 'string', 'max:255'],
            'proof'   => ['nullable', 'image', 'max:4096'],
        ], [], ['amount' => 'jumlah setoran']);

        $proofPath = $request->hasFile('proof') ? $request->file('proof')->store('settlement_proofs', 'public') : null;

        Settlement::create([
            'store_id'    => $store->id,
            'amount'      => $validated['amount'],
            'paid_at'     => $validated['paid_at'],
            'method'      => $validated['method'],
            'proof_path'  => $proofPath,
            'note'        => $validated['note'] ?? null,
            'recorded_by' => Auth::id(),
        ]);

        AuditLogService::log('create', 'Settlement', "Setoran toko {$store->name} Rp " . number_format($validated['amount'], 0, ',', '.'));

        return redirect()->route('settlements.show', $store)->with('success', 'Setoran berhasil dicatat.');
    }

    private function ensureAccess(Store $store): void
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['owner', 'superadmin', 'finance']) && ! $user->stores->pluck('id')->contains($store->id)) {
            abort(403, 'Anda tidak berhak melihat settlement toko ini.');
        }
    }
}
