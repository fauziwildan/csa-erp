<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view master');
        $paymentMethods = PaymentMethod::when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')->paginate(20)->withQueryString();
        return view('master.payment-methods.index', compact('paymentMethods'));
    }

    public function create()
    {
        $this->authorize('create master');
        return view('master.payment-methods.form');
    }

    public function store(Request $request)
    {
        $this->authorize('create master');
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:20', Rule::unique('payment_methods')],
            'type'       => ['required', Rule::in(['cash', 'transfer', 'qris', 'card', 'other'])],
            'sort_order' => ['integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        PaymentMethod::create($validated);
        return redirect()->route('master.payment-methods.index')->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        $this->authorize('update master');
        return view('master.payment-methods.form', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $this->authorize('update master');
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:20', Rule::unique('payment_methods', 'code')->ignore($paymentMethod->id)],
            'type'       => ['required', Rule::in(['cash', 'transfer', 'qris', 'card', 'other'])],
            'sort_order' => ['integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $paymentMethod->update($validated);
        return redirect()->route('master.payment-methods.index')->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $this->authorize('delete master');

        // Tidak boleh dihapus jika sudah dipakai pada transaksi (akan melanggar foreign key).
        $usageCount = \App\Models\Sale::where('payment_method_id', $paymentMethod->id)->count()
            + \App\Models\SalePayment::where('payment_method_id', $paymentMethod->id)->count();

        if ($usageCount > 0) {
            return redirect()->route('master.payment-methods.index')
                ->with('error', "Metode \"{$paymentMethod->name}\" tidak dapat dihapus karena sudah dipakai pada {$usageCount} transaksi. Buka Edit lalu hilangkan centang \"Aktif\" agar tidak muncul di kasir tanpa menghapus riwayat.");
        }

        try {
            $paymentMethod->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('master.payment-methods.index')
                ->with('error', "Metode \"{$paymentMethod->name}\" tidak dapat dihapus karena masih terkait data lain. Nonaktifkan saja.");
        }

        return redirect()->route('master.payment-methods.index')->with('success', 'Metode pembayaran berhasil dihapus.');
    }
}
