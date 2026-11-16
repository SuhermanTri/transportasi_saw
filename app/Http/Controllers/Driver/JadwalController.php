<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Kendaraan;
use App\Models\Notifikasi;
use App\Models\PermintaanLog;
use App\Services\ApprovalService;
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
              ->whereIn('status_perjalanan', ['aktif', 'berjalan', 'selesai_driver'])
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

    /**
     * Driver menekan "Mulai Perjalanan".
     * Syarat: jadwal milik kendaraan driver, jadwal aktif, belum berangkat, dan tanggalnya hari ini (WIB).
     */
    public function mulai($id)
    {
        $kid = $this->getKendaraanId();

        $jadwal = Jadwal::with('permintaan.user')
            ->where('id_kendaraan', $kid)
            ->findOrFail($id);

        $sekarang = now(Jadwal::ZONA_WAKTU);

        if ($jadwal->status !== 'aktif' || $jadwal->status_perjalanan !== 'aktif') {
            return redirect()->back()->with('error', 'Perjalanan ini tidak dapat dimulai (sudah berjalan, selesai, atau dibatalkan).');
        }
        if ($jadwal->tanggal->format('Y-m-d') !== $sekarang->format('Y-m-d')) {
            return redirect()->back()->with('error',
                'Perjalanan hanya dapat dimulai pada tanggal jadwalnya (' . $jadwal->tanggal->format('d M Y') . ').');
        }

        $jadwal->update([
            'status_perjalanan' => 'berjalan',
            'waktu_berangkat'   => $sekarang,
        ]);

        $permintaan = $jadwal->permintaan;
        $permintaan?->update(['status' => 'berjalan']);

        $driver  = Auth::user();
        $jam     = $sekarang->format('H:i');
        $tujuan  = $permintaan?->tujuan ?? '-';
        $pemohon = $permintaan?->user?->nama ?? '-';

        if ($permintaan?->id_user) {
            Notifikasi::kirim(
                $permintaan->id_user,
                "Driver {$driver->nama} telah memulai perjalanan ke \"{$tujuan}\" pukul {$jam} WIB.",
                route('user.permintaan.show', $permintaan->id_permintaan)
            );
        }

        Notifikasi::kirimKeDispatcher(
            "Driver {$driver->nama} mulai perjalanan mengantar {$pemohon} ke \"{$tujuan}\" pukul {$jam} WIB.",
            route('admin.penjadwalan.index')
        );

        if ($permintaan) {
            PermintaanLog::catat(
                idPermintaan: $permintaan->id_permintaan,
                userId:       $driver->id_user,
                tipe:         'mulai_perjalanan',
                keterangan:   "Driver {$driver->nama} mulai perjalanan pukul {$jam} WIB",
                dataBaru:     ['status_perjalanan' => 'berjalan', 'waktu_berangkat' => $sekarang->format('Y-m-d H:i:s')],
            );
        }

        return redirect()->back()->with('success', "Perjalanan dimulai pukul {$jam} WIB. Hati-hati di jalan!");
    }

    public function selesai($id)
    {
        $kid = $this->getKendaraanId();

        $jadwal = Jadwal::with('permintaan.user')
            ->where('id_kendaraan', $kid)
            ->findOrFail($id);

        // Selesai hanya bisa setelah perjalanan dimulai
        if ($jadwal->status_perjalanan !== 'berjalan') {
            return redirect()->back()->with('error', 'Perjalanan belum dimulai. Tekan "Mulai Perjalanan" terlebih dahulu.');
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

        // Notifikasi ke dispatcher — driver selesai mengantar
        $driver      = Auth::user();
        $namaDriver  = $driver->nama;
        $tujuan      = $permintaan?->tujuan ?? '-';
        $pemohon     = $permintaan?->user?->nama ?? '-';
        $linkJadwal  = route('admin.penjadwalan.index');

        Notifikasi::kirimKeDispatcher(
            "Driver {$namaDriver} telah selesai mengantar {$pemohon} ke \"{$tujuan}\". Menunggu konfirmasi penumpang.",
            $linkJadwal
        );

        return redirect()->route('driver.jadwal.show', $id)
            ->with('success', 'Perjalanan ditandai selesai. Menunggu konfirmasi pemohon.');
    }

    public function updateStatus(Request $request)
    {
        $request->validate(['status_driver' => 'required|in:tersedia,cuti,sakit,dinas_luar,istirahat']);

        $driver     = Auth::user();
        $statusLama = $driver->status_driver;
        $driver->update(['status_driver' => $request->status_driver]);

        $pesan    = 'Status driver diperbarui.';
        $approval = app(ApprovalService::class);

        // Feedback otomatis ke pemohon saat driver tidak tersedia
        if ($request->status_driver !== 'tersedia' && $statusLama === 'tersedia') {
            $hasil = $approval->driverTidakTersedia($driver->fresh());
            $total = $hasil['dialihkan'] + $hasil['menunggu'];
            if ($total > 0) {
                $pesan .= " {$total} jadwal Anda dilepas: {$hasil['dialihkan']} dialihkan ke kendaraan lain, "
                        . "{$hasil['menunggu']} pemohon diberi informasi otomatis bahwa driver tidak tersedia.";
            }
        }

        // Driver tersedia kembali → antrean yang menunggu bisa dijadwalkan
        if ($request->status_driver === 'tersedia' && $statusLama !== 'tersedia') {
            try { $approval->driverTersediaKembali(); } catch (\Throwable) {}
        }

        return redirect()->back()->with('success', $pesan);
    }
}
