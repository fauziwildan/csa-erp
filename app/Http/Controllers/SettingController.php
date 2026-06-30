<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Form setelan kredit GLOBAL.
     */
    public function credit()
    {
        $this->authorize('manage settings');

        $creditMode  = Setting::get('credit_mode', 'warning');
        $creditLimit = (float) Setting::get('credit_limit', 0);
        $loyaltyDivisor = (int) Setting::get('loyalty_earn_divisor', 10000);
        $loyaltyValue   = (int) Setting::get('loyalty_point_value', 1000);

        return view('settings.credit', compact('creditMode', 'creditLimit', 'loyaltyDivisor', 'loyaltyValue'));
    }

    public function updateCredit(Request $request)
    {
        $this->authorize('manage settings');

        $validated = $request->validate([
            'credit_mode'  => ['required', 'in:warning,block,approval'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'loyalty_earn_divisor' => ['required', 'integer', 'min:0'],
            'loyalty_point_value'  => ['required', 'integer', 'min:0'],
        ]);

        Setting::set('credit_mode', $validated['credit_mode']);
        Setting::set('credit_limit', $validated['credit_limit']);
        Setting::set('loyalty_earn_divisor', $validated['loyalty_earn_divisor']);
        Setting::set('loyalty_point_value', $validated['loyalty_point_value']);

        AuditLogService::log('update', 'settings', "Setelan diperbarui: kredit mode={$validated['credit_mode']}, limit={$validated['credit_limit']}, loyalty /{$validated['loyalty_earn_divisor']}");

        return redirect()->route('settings.credit')->with('success', 'Setelan berhasil disimpan.');
    }
}
