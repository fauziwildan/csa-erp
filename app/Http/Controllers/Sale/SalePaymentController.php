<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalePaymentController extends Controller
{
    public function create(Sale $sale)
    {
        $this->authorize('record payment');
        $this->ensureAccess($sale);

        if ($sale->payment_status === 'lunas') {
            return $this->backToCustomer($sale)->with('error', 'Nota ini sudah lunas.');
        }

        $sale->load(['customer', 'store', 'items.variant.product']);
        $methods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        return view('sales.payments.create', compact('sale', 'methods'));
    }

    public function store(Request $request, Sale $sale)
    {
        $this->authorize('record payment');
        $this->ensureAccess($sale);

        if ($sale->payment_status === 'lunas') {
            return back()->with('error', 'Nota ini sudah lunas.');
        }

        $remaining = $sale->remainingDue();

        $validated = $request->validate([
            'amount'            => ['required', 'numeric', 'min:1', 'max:' . $remaining],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'paid_at'           => ['required', 'date'],
            'note'              => ['nullable', 'string', 'max:255'],
            'proof'             => ['nullable', 'image', 'max:4096'],
        ], [], ['amount' => 'jumlah pembayaran']);

        DB::transaction(function () use ($request, $sale, $validated) {
            $proofPath = null;
            if ($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('payment_proofs', 'public');
            }

            SalePayment::create([
                'sale_id'           => $sale->id,
                'amount'            => $validated['amount'],
                'payment_method_id' => $validated['payment_method_id'],
                'paid_at'           => $validated['paid_at'],
                'received_by'       => Auth::id(),
                'proof_path'        => $proofPath,
                'note'              => $validated['note'] ?? null,
            ]);

            $newPaid = (float) $sale->amount_paid + (float) $validated['amount'];
            $update  = ['amount_paid' => $newPaid];

            // Lunas penuh → tandai lunas + catat tanggal pelunasan (basis komisi cash-basis).
            if ($newPaid + 0.001 >= (float) $sale->total_amount) {
                $update['payment_status'] = 'lunas';
                $update['settled_at']     = $validated['paid_at'];
            }

            $sale->update($update);

            // Loyalty: beri poin saat nota menjadi lunas.
            if (($update['payment_status'] ?? null) === 'lunas' && $sale->customer) {
                \App\Services\LoyaltyService::award($sale->customer, $sale);
            }

            AuditLogService::log('payment', 'Sale', "Pembayaran Rp " . number_format($validated['amount'], 0, ',', '.') . " untuk {$sale->sale_no}" . (($update['payment_status'] ?? null) === 'lunas' ? ' (LUNAS)' : ''), null, null, Sale::class, $sale->id);
        });

        $msg = $sale->fresh()->payment_status === 'lunas'
            ? "Pembayaran tercatat. Nota {$sale->sale_no} kini LUNAS."
            : "Pembayaran tercatat. Sisa utang nota: Rp " . number_format($sale->fresh()->remainingDue(), 0, ',', '.') . ".";

        return $this->backToCustomer($sale)->with('success', $msg);
    }

    /** Kembali ke detail pelanggan bila ada, selain itu ke daftar pelanggan. */
    private function backToCustomer(Sale $sale)
    {
        return $sale->customer_id
            ? redirect()->route('customers.show', $sale->customer_id)
            : redirect()->route('customers.index');
    }

    /** Kepala toko / kasir hanya boleh memproses nota toko sendiri. */
    private function ensureAccess(Sale $sale): void
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['owner', 'superadmin', 'finance']) && ! $user->stores->pluck('id')->contains($sale->store_id)) {
            abort(403, 'Anda tidak berhak memproses nota toko ini.');
        }
    }
}
