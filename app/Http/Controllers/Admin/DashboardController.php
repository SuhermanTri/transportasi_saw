<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HasilSaw;
use App\Models\Jadwal;
use App\Models\Kendaraan;
use App\Models\Permintaan;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [
            'totalPermintaan'    => Permintaan::count(),
            'permintaanPending'  => Permintaan::where('status', 'menunggu')->count(),
            'permintaanDisetujui'=> Permintaan::where('status', 'disetujui')->count(),
            'kendaraanTersedia'  => Kendaraan::where('status', 'tersedia')->count(),
            'kendaraanDigunakan' => Kendaraan::where('status', 'dipakai')->count(),
            'jadwalHariIni'      => Jadwal::whereDate('tanggal', today())->count(),

            // Top 5 permintaan berdasarkan ranking SAW
            'topPrioritas' => HasilSaw::with('permintaan.user', 'permintaan.urgensi')
                ->orderBy('ranking')
                ->limit(5)
                ->get(),

            // Permintaan terbaru
            'permintaanTerbaru' => Permintaan::with('user', 'urgensi')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),

            'jadwalHariIniList' => Jadwal::with(['kendaraan', 'permintaan.user'])
                ->whereDate('tanggal', today())
                ->get(),
        ];

        return view('admin.dashboard', $data);
    }
}
