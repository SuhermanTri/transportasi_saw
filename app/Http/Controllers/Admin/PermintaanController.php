<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HasilSaw;
use App\Models\Permintaan;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

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
        $permintaan = Permintaan::with(['user', 'urgensi', 'jadwal.kendaraan', 'hasilSaw'])
            ->findOrFail($id);
        return view('admin.permintaan.show', compact('permintaan'));
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
        $this->approval->reschedule($permintaan, $request->tanggal_berangkat, (string) $request->catatan);

        return redirect()->route('admin.permintaan.show', $id)
            ->with('success', 'Permintaan berhasil dijadwalkan ulang.');
    }
}
