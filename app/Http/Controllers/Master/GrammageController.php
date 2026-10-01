<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Grammage;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class GrammageController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view master');
        $grammages = Grammage::when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(30)->withQueryString();

        return view('master.grammages.index', compact('grammages'));
    }

    public function create()
    {
        $this->authorize('create master');
        return view('master.grammages.form');
    }

    public function store(Request $request)
    {
        $this->authorize('create master');
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'value'      => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        if (!isset($validated['sort_order']) || is_null($validated['sort_order'])) {
            $validated['sort_order'] = (Grammage::max('sort_order') ?? 0) + 1;
        }

        $grammage = Grammage::create($validated);
        AuditLogService::log('create', 'grammages', "Gramasi '{$grammage->name}' dibuat");

        return redirect()->route('master.grammages.index')->with('success', "Gramasi '{$grammage->name}' berhasil ditambahkan.");
    }

    public function edit(Grammage $grammage)
    {
        $this->authorize('update master');
        return view('master.grammages.form', compact('grammage'));
    }

    public function update(Request $request, Grammage $grammage)
    {
        $this->authorize('update master');
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'value'      => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $old = $grammage->toArray();
        $grammage->update($validated);
        AuditLogService::log('update', 'grammages', "Gramasi '{$grammage->name}' diperbarui", $old, $validated, Grammage::class, $grammage->id);

        return redirect()->route('master.grammages.index')->with('success', "Gramasi '{$grammage->name}' berhasil diperbarui.");
    }

    public function destroy(Grammage $grammage)
    {
        $this->authorize('delete master');

        if ($grammage->products()->exists()) {
            return back()->with('error', "Gramasi '{$grammage->name}' tidak bisa dihapus karena masih digunakan oleh produk. Anda dapat menonaktifkannya.");
        }

        $name = $grammage->name;
        $grammage->delete();
        AuditLogService::log('delete', 'grammages', "Gramasi '{$name}' dihapus");

        return redirect()->route('master.grammages.index')->with('success', "Gramasi '{$name}' berhasil dihapus.");
    }

    public function toggle(Grammage $grammage)
    {
        $this->authorize('update master');
        $grammage->update(['is_active' => !$grammage->is_active]);

        $status = $grammage->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master.grammages.index')->with('success', "Gramasi '{$grammage->name}' berhasil {$status}.");
    }

    public function reorder(Request $request)
    {
        $this->authorize('update master');
        $request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'integer|exists:grammages,id',
        ]);

        foreach ($request->ordered_ids as $index => $id) {
            Grammage::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
