<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Kendaraan;
use App\Models\Notifikasi;
use App\Models\Permintaan;
use App\Services\GageService;
use App\Services\VehicleScheduleService;
use Illuminate\Http\Request;

class PenjadwalanController extends Controller
{
    public function __construct(
        private GageService $gage,
        private VehicleScheduleService $schedule,
    ) {}

    public function index()
    {
        $jadwal = Jadwal::with(['permintaan.user', 'kendaraan'])
            ->orderByDesc('tanggal')
            ->get();

        $permintaanSiap = Permintaan::with(['user', 'urgensi', 'hasilSaw'])
            ->whereIn('status', ['disetujui', 'menunggu_konfirmasi'])
            ->whereDoesntHave('jadwal')
            ->orderByRaw('(SELECT nilai_akhir FROM hasil_saw WHERE hasil_saw.id_permintaan = permintaan.id_permintaan) DESC')
            ->get();

        return view('admin.penjadwalan.index', compact('jadwal', 'permintaanSiap'));
    }

    public function create()
    {
        $permintaan = Permintaan::with(['user', 'urgensi', 'hasilSaw'])
            ->whereIn('status', ['disetujui', 'menunggu_konfirmasi'])
            ->whereDoesntHave('jadwal')
            ->orderByRaw('(SELECT nilai_akhir FROM hasil_saw WHERE hasil_saw.id_permintaan = permintaan.id_permintaan) DESC')
            ->get();

        return view('admin.penjadwalan.create', compact('permintaan'));
    }

    /**
     * Ambil kendaraan tersedia untuk permintaan tertentu (untuk dropdown/AJAX).
     */
    public function kendaraanTersedia(Request $request)
    {
        $request->validate(['id_permintaan' => 'required|exists:permintaan,id_permintaan']);

        $p = Permintaan::findOrFail($request->id_permintaan);

        $tanggal    = $p->tanggal_berangkat->format('Y-m-d');
        $jamMulai   = $p->jam_berangkat ?? '08:00';
        $jamSelesai = $p->jam_kembali   ?? '17:00';

        $gageAktif = $this->gage->isGageAktif($jamMulai, $jamSelesai);
        $tipeGage  = $gageAktif ? $this->gage->tipeUntukTanggal($p->tanggal_berangkat) : null;

        $kendaraan = $this->schedule->kendaraanTersedia($tanggal, $jamMulai, $jamSelesai, $tipeGage);

        return response()->json([
            'gage_aktif'   => $gageAktif,
            'tipe_hari'    => $gageAktif ? $tipeGage : null,
            'kendaraan'    => $kendaraan,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_permintaan' => 'required|exists:permintaan,id_permintaan',
            'id_kendaraan'  => 'required|exists:kendaraan,id_kendaraan',
        ]);

        $permintaan = Permintaan::findOrFail($request->id_permintaan);
        $kendaraan  = Kendaraan::with('driverUser')->findOrFail($request->id_kendaraan);

        $tanggal    = $permintaan->tanggal_berangkat->format('Y-m-d');
        $jamMulai   = $permintaan->jam_berangkat  ?? '08:00';
        $jamSelesai = $permintaan->jam_kembali    ?? '17:00';

        // Validasi gage — hanya jika jam perjalanan masuk jendela gage
        if (! $this->gage->sesuai($kendaraan, $permintaan->tanggal_berangkat, $jamMulai, $jamSelesai)) {
            return back()->withInput()
                ->with('error', 'Kendaraan ini tidak sesuai aturan gage untuk tanggal dan jam perjalanan tersebut (06:00–10:00 atau 16:00–21:00).');
        }

        // Validasi booking lock
        if (! $this->schedule->tersedia($kendaraan->id_kendaraan, $tanggal, $jamMulai, $jamSelesai)) {
            return back()->withInput()
                ->with('error', 'Kendaraan sudah memiliki jadwal yang bentrok pada waktu tersebut. Pilih kendaraan lain.');
        }

        $jadwal = Jadwal::create([
            'id_permintaan' => $permintaan->id_permintaan,
            'id_kendaraan'  => $kendaraan->id_kendaraan,
            'tanggal'       => $tanggal,
            'jam_mulai'     => $jamMulai,
            'jam_selesai'   => $jamSelesai,
            'status'        => 'aktif',
        ]);

        $permintaan->update([
            'status'             => 'terjadwal',
            'catatan_dispatcher' => null,
            'feedback_type'      => null,
        ]);
        $kendaraan->update(['status' => 'dipakai']);

        if ($kendaraan->driverUser) {
            $driver = $kendaraan->driverUser;
            $driver->increment('jumlah_perjalanan');
            Notifikasi::kirim(
                $driver->id_user,
                "Anda mendapat jadwal tugas baru pada " . \Carbon\Carbon::parse($jadwal->tanggal)->format('d M Y') . ".",
                route('driver.jadwal.show', $jadwal->id_jadwal)
            );
        }

        Notifikasi::kirim(
            $permintaan->id_user,
            "Permintaan perjalanan Anda ke \"{$permintaan->tujuan}\" telah dijadwalkan.",
            route('user.permintaan.show', $permintaan->id_permintaan)
        );

        return redirect()->route('admin.penjadwalan.index')
            ->with('success', 'Jadwal berhasil disimpan.');
    }

    public function edit($id)
    {
        $jadwal = Jadwal::with(['permintaan.user', 'kendaraan'])->findOrFail($id);
        return view('admin.penjadwalan.edit', compact('jadwal'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:aktif,selesai,batal',
        ]);

        $jadwal = Jadwal::with('permintaan')->findOrFail($id);
        $jadwal->update(['status' => $request->status]);

        if (in_array($request->status, ['selesai', 'batal'])) {
            Kendaraan::where('id_kendaraan', $jadwal->id_kendaraan)->update(['status' => 'tersedia']);
            $newPermintaanStatus = $request->status === 'selesai' ? 'selesai' : 'disetujui';

            $permintaan = $jadwal->permintaan;
            $catatan    = trim((string) $request->input('catatan_dispatcher'));

            $updateData = ['status' => $newPermintaanStatus];
            if ($request->status === 'batal' && $catatan) {
                $updateData['catatan_dispatcher'] = $catatan;
                $updateData['feedback_type']      = 'dijadwalkan_ulang';

                if ($permintaan) {
                    Notifikasi::kirim(
                        $permintaan->id_user,
                        "Jadwal perjalanan Anda ke \"{$permintaan->tujuan}\" dibatalkan oleh Dispatcher. Keterangan: {$catatan}",
                        route('user.permintaan.show', $permintaan->id_permintaan)
                    );
                }
            }

            Permintaan::where('id_permintaan', $jadwal->id_permintaan)->update($updateData);
        }

        return redirect()->route('admin.penjadwalan.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $jadwal     = Jadwal::with('permintaan')->findOrFail($id);
        $permintaan = $jadwal->permintaan;

        Kendaraan::where('id_kendaraan', $jadwal->id_kendaraan)->update(['status' => 'tersedia']);

        $catatan      = trim((string) $request->input('catatan_dispatcher'));
        $feedbackType = $request->input('feedback_type', 'dijadwalkan_ulang');

        $updateData = ['status' => 'disetujui'];
        if ($catatan) {
            $updateData['catatan_dispatcher'] = $catatan;
            $updateData['feedback_type']      = $feedbackType;
        }

        Permintaan::where('id_permintaan', $jadwal->id_permintaan)->update($updateData);

        if ($catatan && $permintaan) {
            $teksNotif = $feedbackType === 'dioverride'
                ? "Jadwal perjalanan Anda ke \"{$permintaan->tujuan}\" dialihkan ke permintaan prioritas lebih tinggi. Keterangan: {$catatan}"
                : "Jadwal perjalanan Anda ke \"{$permintaan->tujuan}\" dijadwalkan ulang oleh Dispatcher. Keterangan: {$catatan}";

            Notifikasi::kirim(
                $permintaan->id_user,
                $teksNotif,
                route('user.permintaan.show', $permintaan->id_permintaan)
            );
        }

        $jadwal->delete();

        return redirect()->route('admin.penjadwalan.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }
}
