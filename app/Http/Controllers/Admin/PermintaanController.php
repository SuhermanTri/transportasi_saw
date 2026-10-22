<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HasilSaw;
use App\Models\Notifikasi;
use App\Models\Permintaan;
use App\Models\PermintaanLog;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermintaanController extends Controller
{
    public function __construct(private ApprovalService $approval) {}

    public function index()
    {
        $permintaan = Permintaan::with(['user', 'urgensi', 'hasilSaw'])
            ->whereDate('tanggal_berangkat', '>=', today())
            ->whereDate('tanggal_berangkat', '<=', today()->addDay())
            ->get();

        // Urutkan: yang punya SAW ranking didahulukan, sisanya di bawah
        $permintaan = $permintaan->sortBy(fn($p) => $p->hasilSaw?->ranking ?? PHP_INT_MAX)->values();

        return view('admin.permintaan.index', compact('permintaan'));
    }

    public function show($id)
    {
        $permintaan = Permintaan::with(['user', 'urgensi', 'jadwal.kendaraan', 'hasilSaw', 'logs.actor'])
            ->findOrFail($id);

        // Temukan booking konflik (sama tanggal + jam, priority lebih rendah) untuk Fitur 3
        $konflikBooking = collect();
        if (in_array($permintaan->priority, ['high', 'urgent'])
            && in_array($permintaan->status, ['menunggu_konfirmasi', 'disetujui'])
        ) {
            $konflikBooking = Permintaan::with(['user', 'jadwal.kendaraan'])
                ->whereIn('status', ['terjadwal'])
                ->where('id_permintaan', '!=', $permintaan->id_permintaan)
                ->whereDate('tanggal_berangkat', $permintaan->tanggal_berangkat)
                ->where(function ($q) use ($permintaan) {
                    $q->where('jam_berangkat', '<', $permintaan->jam_kembali ?? '23:59')
                      ->where(function ($q2) use ($permintaan) {
                          $q2->whereNull('jam_kembali')
                             ->orWhere('jam_kembali', '>', $permintaan->jam_berangkat);
                      });
                })
                ->where(function ($q) use ($permintaan) {
                    // Hanya tampilkan yang prioritasnya lebih rendah
                    if ($permintaan->priority === 'urgent') {
                        $q->whereIn('priority', ['normal', 'high']);
                    } else {
                        $q->where('priority', 'normal');
                    }
                })
                ->get();
        }

        // Saran tanggal terdekat yang masih ada kendaraan (untuk form "Ubah Tanggal")
        $tanggalSaran = $permintaan->status === 'menunggu_konfirmasi'
            ? $this->approval->tanggalTersediaBerikutnya($permintaan)
            : null;

        return view('admin.permintaan.show', compact('permintaan', 'konflikBooking', 'tanggalSaran'));
    }

    public function approve($id)
    {
        $permintaan = Permintaan::where('status', 'menunggu')->findOrFail($id);
        $this->approval->setujui($permintaan);

        return redirect()->route('admin.permintaan.index')
            ->with('success', 'Permintaan berhasil disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $permintaan = Permintaan::whereIn('status', ['menunggu', 'menunggu_konfirmasi', 'disetujui'])->findOrFail($id);
        $this->approval->tolak($permintaan, (string) $request->alasan);

        return redirect()->route('admin.permintaan.index')
            ->with('success', 'Permintaan berhasil ditolak.');
    }

    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'tanggal_berangkat' => 'required|date',
            'catatan'           => 'nullable|string|max:500',
        ]);

        $permintaan = Permintaan::whereIn('status', ['menunggu_konfirmasi', 'disetujui'])->findOrFail($id);

        $dataLama = ['tanggal_berangkat' => $permintaan->tanggal_berangkat->format('Y-m-d')];

        $this->approval->reschedule($permintaan, $request->tanggal_berangkat, (string) $request->catatan);

        // Audit trail
        PermintaanLog::catat(
            idPermintaan: $permintaan->id_permintaan,
            userId:       Auth::id(),
            tipe:         'reschedule_admin',
            keterangan:   $request->catatan ?: 'Dijadwalkan ulang oleh Dispatcher',
            dataLama:     $dataLama,
            dataBaru:     ['tanggal_berangkat' => $request->tanggal_berangkat],
        );

        return redirect()->route('admin.permintaan.show', $id)
            ->with('success', 'Permintaan berhasil dijadwalkan ulang.');
    }

    /* ════════════════════════════════════════════════════════════
     | FITUR 3 — Ubah Priority (admin/dispatcher only)
     ═══════════════════════════════════════════════════════════ */

    public function updatePriority(Request $request, $id)
    {
        $request->validate([
            'priority' => 'required|in:normal,high,urgent',
        ]);

        $permintaan   = Permintaan::findOrFail($id);
        $prioritasLama = $permintaan->priority ?? 'normal';
        $prioritasBaru = $request->priority;

        if ($prioritasLama === $prioritasBaru) {
            return redirect()->route('admin.permintaan.show', $id)
                ->with('info', 'Prioritas tidak berubah.');
        }

        $permintaan->update(['priority' => $prioritasBaru]);

        // Audit trail
        PermintaanLog::catat(
            idPermintaan: $permintaan->id_permintaan,
            userId:       Auth::id(),
            tipe:         'prioritas_diubah',
            keterangan:   "Prioritas diubah dari {$prioritasLama} menjadi {$prioritasBaru}",
            dataLama:     ['priority' => $prioritasLama],
            dataBaru:     ['priority' => $prioritasBaru],
        );

        // Notifikasi ke pemohon
        $labelMap = ['normal' => 'Normal', 'high' => 'Tinggi', 'urgent' => 'Urgent'];
        Notifikasi::kirim(
            $permintaan->id_user,
            "Prioritas booking Anda ke \"{$permintaan->tujuan}\" diubah menjadi {$labelMap[$prioritasBaru]} oleh Dispatcher.",
            route('user.permintaan.show', $permintaan->id_permintaan)
        );

        return redirect()->route('admin.permintaan.show', $id)
            ->with('success', "Prioritas diubah ke \"{$labelMap[$prioritasBaru]}\".");
    }
}
