<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view customers');

        $customers = Customer::query()
            ->select('customers.*')
            // Hitung utang berjalan & jumlah transaksi via subquery (hindari N+1).
            ->selectRaw("(SELECT COALESCE(SUM(GREATEST(total_amount - amount_paid, 0)), 0)
                          FROM sales WHERE sales.customer_id = customers.id AND sales.payment_status <> 'lunas') AS debt")
            ->selectRaw("(SELECT COUNT(*) FROM sales WHERE sales.customer_id = customers.id) AS tx_count")
            ->when($request->search, function ($q) use ($request) {
                $term = trim($request->search);
                $q->where(fn ($qq) => $qq->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
            })
            ->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('is_active', $request->status))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $globalLimit = (float) Setting::get('credit_limit', 0);

        return view('customers.index', compact('customers', 'globalLimit'));
    }

    public function create()
    {
        $this->authorize('manage customers');
        return view('customers.form');
    }

    public function store(Request $request)
    {
        $this->authorize('manage customers');
        $data = $this->validateData($request, null);
        $customer = Customer::create($data);

        AuditLogService::log('create', 'customers', "Pelanggan '{$customer->name}' dibuat");

        return redirect()->route('customers.show', $customer)->with('success', "Pelanggan '{$customer->name}' berhasil ditambahkan.");
    }

    public function show(Customer $customer)
    {
        $this->authorize('view customers');

        $sales = $customer->sales()
            ->with(['store', 'paymentMethod'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $outstandingDebt = $customer->outstanding_debt;
        $effectiveLimit  = $customer->effectiveCreditLimit();
        $globalLimit     = (float) Setting::get('credit_limit', 0);
        $ledgers         = $customer->loyaltyLedgers()->with('creator')->limit(15)->get();

        return view('customers.show', compact('customer', 'sales', 'outstandingDebt', 'effectiveLimit', 'globalLimit', 'ledgers'));
    }

    public function edit(Customer $customer)
    {
        $this->authorize('manage customers');
        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize('manage customers');
        $data = $this->validateData($request, $customer);
        $customer->update($data);

        AuditLogService::log('update', 'customers', "Pelanggan '{$customer->name}' diubah");

        return redirect()->route('customers.show', $customer)->with('success', "Pelanggan '{$customer->name}' berhasil diperbarui.");
    }

    /** Kelola poin loyalty: tukar (redeem) atau penyesuaian (adjust). */
    public function loyalty(Request $request, Customer $customer)
    {
        $this->authorize('manage customers');

        $validated = $request->validate([
            'action' => ['required', 'in:redeem,adjust'],
            'points' => ['required', 'integer'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['action'] === 'redeem') {
            $pts = abs($validated['points']);
            if (! \App\Services\LoyaltyService::redeem($customer, $pts, $validated['note'] ?? null)) {
                return back()->with('error', 'Poin tidak cukup atau jumlah tidak valid.');
            }
            $msg = "{$pts} poin berhasil ditukar.";
        } else {
            \App\Services\LoyaltyService::adjust($customer, $validated['points'], $validated['note'] ?? null);
            $msg = 'Poin berhasil disesuaikan.';
        }

        return redirect()->route('customers.show', $customer)->with('success', $msg);
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('manage customers');
        $name = $customer->name;
        $customer->delete();

        AuditLogService::log('delete', 'customers', "Pelanggan '{$name}' dihapus");

        return redirect()->route('customers.index')->with('success', "Pelanggan '{$name}' berhasil dihapus.");
    }

    /**
     * Validasi + susun data. Override credit_limit hanya boleh diubah oleh pemilik izin 'manage settings'.
     */
    private function validateData(Request $request, ?Customer $customer): array
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:150'],
            'phone'   => ['nullable', 'string', 'max:30', Rule::unique('customers', 'phone')->ignore($customer?->id)->whereNull('deleted_at')],
            'address' => ['nullable', 'string'],
            'city'    => ['nullable', 'string', 'max:100'],
            'notes'   => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data = [
            'name'      => $validated['name'],
            'phone'     => $validated['phone'] ?: null,
            'address'   => $validated['address'] ?? null,
            'city'      => $validated['city'] ?? null,
            'notes'     => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Hanya yang berhak atur kebijakan kredit yang boleh set override limit.
        if (Auth::user()->can('manage settings')) {
            // Kosong = ikut limit global (null); diisi = override.
            $data['credit_limit'] = $request->input('credit_limit') === null || $request->input('credit_limit') === ''
                ? null
                : $validated['credit_limit'];
        }

        return $data;
    }
}
