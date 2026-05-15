<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Permintaan;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Permintaan yang jadwalnya sudah selesai oleh driver tapi belum dikonfirmasi user
        $pendingKonfirmasi = Jadwal::whereHas('permintaan', fn($q) => $q->where('id_user', $userId))
            ->where('status_perjalanan', 'selesai_driver')
            ->with('permintaan')
            ->get();

        // Permintaan dengan feedback dari dispatcher (ditolak / dijadwalkan ulang / dialihkan)
        $permintaanFeedback = Permintaan::where('id_user', $userId)
            ->whereNotNull('catatan_dispatcher')
            ->orderByDesc('created_at')
            ->get();

        $data = [
            'totalPermintaan'    => Permintaan::where('id_user', $userId)->count(),
            'menunggu'           => Permintaan::where('id_user', $userId)->where('status', 'menunggu')->count(),
            'disetujui'          => Permintaan::where('id_user', $userId)->where('status', 'disetujui')->count(),
            'jadwalHariIni'      => Jadwal::whereHas('permintaan', fn($q) => $q->where('id_user', $userId))
                                        ->whereDate('tanggal', today())->count(),
            'pendingKonfirmasi'  => $pendingKonfirmasi,
            'permintaanFeedback' => $permintaanFeedback,

            'permintaanTerbaru'  => Permintaan::with('urgensi')
                                        ->where('id_user', $userId)
                                        ->orderByDesc('created_at')
                                        ->limit(5)
                                        ->get(),

            'jadwalSaya'         => Jadwal::with(['kendaraan', 'permintaan'])
                                        ->whereHas('permintaan', fn($q) => $q->where('id_user', $userId))
                                        ->whereDate('tanggal', today())
                                        ->get(),
        ];

        return view('user.dashboard', $data);
    }
}
