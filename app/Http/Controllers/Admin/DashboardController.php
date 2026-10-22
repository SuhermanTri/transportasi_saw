<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HasilSaw;
use App\Models\Jadwal;
use App\Models\Kendaraan;
use App\Models\Permintaan;
use App\Models\User;
use App\Services\SawService;

class DashboardController extends Controller
{
    public function __construct(private SawService $saw) {}

    public function index()
    {
        // Hitung ulang SAW agar urutan dashboard = urutan yang dipakai penjadwalan
        try { $this->saw->hitung(); } catch (\Throwable) {}

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

            // Permintaan masuk — diurutkan menurut hasil SAW:
            // nilai preferensi tertinggi dulu, nilai sama → ranking SAW,
            // permintaan tanpa nilai SAW (sudah diproses) di bawah, terbaru dulu.
            'permintaanTerbaru' => Permintaan::with('user', 'urgensi')
                ->leftJoin('hasil_saw', 'hasil_saw.id_permintaan', '=', 'permintaan.id_permintaan')
                ->select('permintaan.*')
                ->orderByRaw('hasil_saw.nilai_akhir IS NULL')
                ->orderByDesc('hasil_saw.nilai_akhir')
                ->orderBy('hasil_saw.ranking')
                ->orderByDesc('permintaan.created_at')
                ->limit(5)
                ->get(),

            'jadwalHariIniList' => Jadwal::with(['kendaraan', 'permintaan.user'])
                ->whereDate('tanggal', today())
                ->get(),
        ];

        return view('admin.dashboard', $data);
    }
}
