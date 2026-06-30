<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index()
    {
        $this->authorize('view expenses');
        $user = Auth::user();
        $query = \App\Models\Expense::with(['store', 'warehouse', 'creator']);

        if ($user->hasAnyRole(['superadmin', 'owner'])) {
            // Bisa melihat semua
        } 
        elseif ($user->hasRole('kepala toko')) {
            // FIX BUG: Ambil array ID Toko dari relasi stores()
            $storeIds = $user->stores()->pluck('stores.id');
            $query->whereIn('store_id', $storeIds);
        } 
        elseif ($user->hasRole('admin gudang')) {
            $warehouseIds = $user->warehouses()->pluck('warehouses.id');
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        $expenses = $query->latest('expense_date')->paginate(10);
        return view('expenses.index', compact('expenses'));
    }

    public function create()
    {
        $this->authorize('create expenses');
        $user = Auth::user();
        $stores = [];
        $warehouses = [];

        if ($user->hasAnyRole(['superadmin', 'owner'])) {
            $stores = Store::all();
            $warehouses = Warehouse::all();
        }

        return view('expenses.create', compact('stores', 'warehouses'));
    }

    public function store(Request $request)
    {
        $this->authorize('create expenses');
        $user = Auth::user();

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expense_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'receipt' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

        // Hanya superadmin/owner yang memilih sumber lewat form; kepala toko & admin gudang
        // sumbernya ditentukan otomatis dari penugasan, jadi tidak wajib diisi.
        $picksSource = ! $user->hasRole('kepala toko') && ! $user->hasRole('admin gudang');
        if ($picksSource) {
            $rules['source_type'] = 'required|in:store,warehouse';
            $rules['source_id'] = [
                'required', 'integer',
                \Illuminate\Validation\Rule::exists(
                    $request->source_type === 'warehouse' ? 'warehouses' : 'stores', 'id'
                ),
            ];
        }

        $request->validate($rules);
        $data = $request->only(['title', 'description', 'expense_type', 'amount', 'expense_date']);
        $data['created_by'] = $user->id;

        // --- Logika Penentuan Source (Toko / Gudang) ---
        if ($user->hasRole('kepala toko')) {
            // FIX BUG: Ambil toko pertama yang ditugaskan ke Kepala Toko ini
            $assignedStore = $user->stores()->first();
            
            if (!$assignedStore) {
                return redirect()->back()->with('error', 'Gagal! Anda belum ditugaskan ke toko manapun. Hubungi Superadmin.');
            }
            $data['store_id'] = $assignedStore->id; 
            
        } elseif ($user->hasRole('admin gudang')) {
            $assignedWarehouse = $user->warehouses()->first();
            if (!$assignedWarehouse) {
                return redirect()->back()->with('error', 'Gagal! Anda belum ditugaskan ke gudang manapun.');
            }
            $data['warehouse_id'] = $assignedWarehouse->id; 
        } else {
            if ($request->source_type === 'store') {
                $data['store_id'] = $request->source_id;
            } else {
                $data['warehouse_id'] = $request->source_id;
            }
        }

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('expenses', 'public');
        }

        Expense::create($data);
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dicatat!');
    }

    // --- TAMBAHAN BARU: FUNGSI EDIT & DELETE --- //

    public function edit(Expense $expense)
    {
        $this->authorize('update expenses');
        $this->authorizeExpenseScope($expense);

        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['superadmin', 'owner']);
        $stores = $isAdmin ? Store::all() : $user->stores()->get();
        $warehouses = $isAdmin ? Warehouse::all() : $user->warehouses()->get();
        return view('expenses.edit', compact('expense', 'stores', 'warehouses'));
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update expenses');
        $this->authorizeExpenseScope($expense);
        $user = Auth::user();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expense_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'receipt' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['title', 'description', 'expense_type', 'amount', 'expense_date']);

        // Hanya superadmin/owner yang boleh memindahkan sumber (toko/gudang).
        // Kepala toko & admin gudang: sumber dipertahankan apa adanya.
        if ($user->hasAnyRole(['superadmin', 'owner'])) {
            $validated = $request->validate([
                'source_type' => 'required|in:store,warehouse',
                'source_id' => [
                    'required', 'integer',
                    \Illuminate\Validation\Rule::exists(
                        $request->source_type === 'warehouse' ? 'warehouses' : 'stores', 'id'
                    ),
                ],
            ]);
            $data['store_id'] = $validated['source_type'] === 'store' ? $validated['source_id'] : null;
            $data['warehouse_id'] = $validated['source_type'] === 'warehouse' ? $validated['source_id'] : null;
        }

        if ($request->hasFile('receipt')) {
            // Hapus struk lama jika ada
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $data['receipt_path'] = $request->file('receipt')->store('expenses', 'public');
        }

        $expense->update($data);
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil diperbarui!');
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete expenses');
        $this->authorizeExpenseScope($expense);

        // Hapus file gambar dari storage
        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }
        
        $expense->delete();
        return redirect()->back()->with('success', 'Pengeluaran berhasil dihapus.');
    }

    /**
     * Pastikan kepala toko / admin gudang hanya bisa mengelola pengeluaran
     * dari toko / gudang yang ditugaskan padanya. Superadmin & owner bebas.
     */
    private function authorizeExpenseScope(Expense $expense): void
    {
        $user = Auth::user();
        if ($user->hasAnyRole(['superadmin', 'owner'])) {
            return;
        }
        if ($user->hasRole('kepala toko')) {
            if (! $expense->store_id || ! $user->stores()->where('stores.id', $expense->store_id)->exists()) {
                abort(403, 'Anda tidak berhak mengelola pengeluaran toko lain.');
            }
        } elseif ($user->hasRole('admin gudang')) {
            if (! $expense->warehouse_id || ! $user->warehouses()->where('warehouses.id', $expense->warehouse_id)->exists()) {
                abort(403, 'Anda tidak berhak mengelola pengeluaran gudang lain.');
            }
        }
    }
}