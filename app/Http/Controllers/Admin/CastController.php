<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CastController extends Controller
{
    // Fungsi dipanggil saat Superadmin menekan angka '1'
    public function triggerCast(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['superadmin', 'owner'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $castData = [
            'status' => 'cast',
            'url' => route('warehouse.monitor', ['kiosk' => 1]), 
            'timestamp' => time() 
        ];

        \Illuminate\Support\Facades\Cache::put('global_monitor_cast', $castData, now()->addMinutes(1));

        return response()->json(['success' => true, 'message' => 'Layar berhasil dilempar!']);
    }

    // Fungsi dipanggil otomatis oleh browser Admin Gudang setiap 3 detik
    public function checkCast()
    {
        // Ambil data perintah dari Cache
        $castData = Cache::get('global_monitor_cast');

        return response()->json($castData ?? ['status' => 'idle']);
    }
}