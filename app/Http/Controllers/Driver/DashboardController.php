<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Kendaraan;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $kendaraan   = $this->getKendaraanDriver();
        $kendaraanId = $kendaraan?->id_kendaraan;

        $data = [
            'jadwalHariIni' => $kendaraanId
                ? Jadwal::where('id_kendaraan', $kendaraanId)->whereDate('tanggal', today())->count()
                : 0,
            'perjalananSelesai' => $kendaraanId
                ? Jadwal::where('id_kendaraan', $kendaraanId)->where('status', 'selesai')->count()
                : 0,
            'perjalananBerjalan' => $kendaraanId
                ? Jadwal::where('id_kendaraan', $kendaraanId)->where('status', 'aktif')->count()
                : 0,
            'totalJadwal' => $kendaraanId
                ? Jadwal::where('id_kendaraan', $kendaraanId)->count()
                : 0,
            'jadwalHariIniList' => $kendaraanId
                ? Jadwal::with(['permintaan.user', 'kendaraan'])
                    ->where('id_kendaraan', $kendaraanId)
                    ->whereDate('tanggal', today())
                    ->get()
                : collect(),
            'riwayatTerbaru' => $kendaraanId
                ? Jadwal::with(['permintaan.user', 'kendaraan'])
                    ->where('id_kendaraan', $kendaraanId)
                    ->orderByDesc('tanggal')
                    ->limit(5)
                    ->get()
                : collect(),
            'kendaraan' => $kendaraan,
        ];

        return view('driver.dashboard', $data);
    }

    /**
     * Temukan kendaraan milik driver yang sedang login.
     * Prioritas: driver_user_id (FK akurat) → fallback nama_driver (data lama).
     */
    private function getKendaraanDriver(): ?Kendaraan
    {
        $user = Auth::user();

        return Kendaraan::where('driver_user_id', $user->id_user)
            ->orWhere(function ($q) use ($user) {
                $q->whereNull('driver_user_id')
                  ->where('nama_driver', $user->nama);
            })
            ->first();
    }
}
