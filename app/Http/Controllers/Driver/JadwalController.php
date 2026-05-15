<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Kendaraan;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    private function getKendaraan(): ?Kendaraan
    {
        $user = Auth::user();
        return Kendaraan::where('driver_user_id', $user->id_user)
            ->orWhere(function ($q) use ($user) {
                $q->whereNull('driver_user_id')
                  ->where('nama_driver', $user->nama);
            })
            ->first();
    }

    private function getKendaraanId(): ?int
    {
        return $this->getKendaraan()?->id_kendaraan;
    }

    public function jadwalSaya()
    {
        $kid = $this->getKendaraanId();

        $jadwal = $kid
            ? Jadwal::with([
                'permintaan.user',   // jadwal → permintaan → users (nama pemesan)
                'kendaraan',
              ])
              ->where('id_kendaraan', $kid)
              ->where('status', 'aktif')
              ->whereIn('status_perjalanan', ['aktif', 'selesai_driver'])
              ->whereDate('tanggal', '>=', today())
              ->orderBy('tanggal')
              ->get()
            : collect();

        return view('driver.jadwal.index', compact('jadwal'));
    }

    public function riwayat()
    {
        $kid = $this->getKendaraanId();
        $riwayat = $kid
            ? Jadwal::with(['permintaan.user', 'kendaraan', 'penilaian'])
                ->where('id_kendaraan', $kid)
                ->orderByDesc('tanggal')
                ->get()
            : collect();

        return view('driver.riwayat.index', compact('riwayat'));
    }

    public function show($id)
    {
        $kid = $this->getKendaraanId();
        $jadwal = Jadwal::with(['permintaan.user', 'permintaan.urgensi', 'kendaraan', 'penilaian'])
            ->where('id_kendaraan', $kid)
            ->findOrFail($id);

        return view('driver.jadwal.show', compact('jadwal'));
    }

    public function selesai($id)
    {
        $kid = $this->getKendaraanId();

        $jadwal = Jadwal::with('permintaan')
            ->where('id_kendaraan', $kid)
            ->findOrFail($id);

        if ($jadwal->status_perjalanan !== 'aktif') {
            return redirect()->back()->with('error', 'Status tidak dapat diubah.');
        }

        $jadwal->update(['status_perjalanan' => 'selesai_driver']);

        // Notifikasi ke user pemohon
        $permintaan = $jadwal->permintaan;
        if ($permintaan?->id_user) {
            Notifikasi::kirim(
                $permintaan->id_user,
                "Driver telah menyelesaikan perjalanan ke \"{$permintaan->tujuan}\". Mohon konfirmasi.",
                route('user.permintaan.show', $permintaan->id_permintaan)
            );
        }

        return redirect()->route('driver.jadwal.show', $id)
            ->with('success', 'Perjalanan ditandai selesai. Menunggu konfirmasi pemohon.');
    }

    public function updateStatus(Request $request)
    {
        $request->validate(['status_driver' => 'required|in:tersedia,cuti,sakit,dinas_luar,istirahat']);
        Auth::user()->update(['status_driver' => $request->status_driver]);
        return redirect()->back()->with('success', 'Status driver diperbarui.');
    }
}
