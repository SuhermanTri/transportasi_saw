<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\HasilSaw;
use App\Models\MasterUrgensi;
use App\Models\Notifikasi;
use App\Models\Permintaan;
use App\Models\PermintaanLog;
use App\Services\ApprovalService;
use App\Services\SawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermintaanController extends Controller
{
    public function __construct(
        private SawService      $saw,
        private ApprovalService $approval,
    ) {}

    public function index()
    {
        $permintaan = Permintaan::with(['urgensi', 'jadwal'])
            ->where('id_user', Auth::id())
            ->orderByDesc('created_at')
            ->get();
        return view('user.permintaan.index', compact('permintaan'));
    }

    public function create()
    {
        $urgensiList = MasterUrgensi::orderByDesc('nilai_saw')->get();
        $jendela     = $this->jendelaPengajuan();
        return view('user.permintaan.create', compact('urgensiList', 'jendela'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tujuan'            => 'required|string|max:150',
            'urgensi_id'        => 'required|exists:master_urgensi,id',
            'tanggal_berangkat' => $this->aturanTanggalBerangkat(),
            'jam_berangkat'     => 'required|date_format:H:i',
            'tanggal_kembali'   => 'nullable|date|after_or_equal:tanggal_berangkat',
            'jam_kembali'       => 'nullable|date_format:H:i',
            'jumlah_penumpang'  => 'required|integer|min:1|max:50',
            'keterangan'        => 'nullable|string|max:255',
        ], [
        ]);

        $permintaan = Permintaan::create([
            ...$data,
            'id_user'    => Auth::id(),
            'status'     => 'menunggu',
            'created_at' => now(),
        ]);

        $this->runSawSilently();

        // Notifikasi ke dispatcher
        $user = Auth::user();
        $tgl  = $permintaan->tanggal_berangkat->format('d M Y');
        Notifikasi::kirimKeDispatcher(
            "Permintaan baru dari {$user->nama}: \"{$permintaan->tujuan}\" ({$tgl} {$permintaan->jam_berangkat})",
            route('admin.permintaan.show', $permintaan->id_permintaan)
        );

        return redirect()->route('user.permintaan.index')
            ->with('success', 'Permintaan kendaraan berhasil diajukan.');
    }

    public function show($id)
    {
        $permintaan = Permintaan::with(['urgensi', 'jadwal.kendaraan.driverUser', 'jadwal.penilaian', 'hasilSaw'])
            ->where('id_user', Auth::id())
            ->findOrFail($id);
        return view('user.permintaan.show', compact('permintaan'));
    }

    public function edit($id)
    {
        $permintaan  = Permintaan::where('id_user', Auth::id())
            ->where('status', 'menunggu')
            ->findOrFail($id);
        $urgensiList = MasterUrgensi::orderByDesc('nilai_saw')->get();
        $jendela     = $this->jendelaPengajuan();
        return view('user.permintaan.edit', compact('permintaan', 'urgensiList', 'jendela'));
    }

    public function update(Request $request, $id)
    {
        $tanggalTersimpan = Permintaan::where('id_user', Auth::id())
            ->where('id_permintaan', $id)
            ->value('tanggal_berangkat');

        $data = $request->validate([
            'tujuan'            => 'required|string|max:150',
            'urgensi_id'        => 'required|exists:master_urgensi,id',
            'tanggal_berangkat' => $this->aturanTanggalBerangkat(
                $tanggalTersimpan ? \Carbon\Carbon::parse($tanggalTersimpan)->format('Y-m-d') : null
            ),
            'jam_berangkat'     => 'required|date_format:H:i',
            'tanggal_kembali'   => 'nullable|date|after_or_equal:tanggal_berangkat',
            'jam_kembali'       => 'nullable|date_format:H:i',
            'jumlah_penumpang'  => 'required|integer|min:1|max:50',
            'keterangan'        => 'nullable|string|max:255',
        ], [
        ]);

        $permintaan = Permintaan::where('id_user', Auth::id())
            ->where('status', 'menunggu')
            ->findOrFail($id);

        $permintaan->update($data);
        $this->runSawSilently();

        // Notifikasi ke dispatcher (permintaan berubah)
        $user = Auth::user();
        $tgl  = $permintaan->fresh()->tanggal_berangkat->format('d M Y');
        Notifikasi::kirimKeDispatcher(
            "Permintaan diperbarui oleh {$user->nama}: \"{$permintaan->tujuan}\" ({$tgl})",
            route('admin.permintaan.show', $permintaan->id_permintaan)
        );

        return redirect()->route('user.permintaan.index')
            ->with('success', 'Permintaan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $permintaan = Permintaan::where('id_user', Auth::id())
            ->where('status', 'menunggu')
            ->findOrFail($id);

        $tujuan = $permintaan->tujuan;
        $tgl    = $permintaan->tanggal_berangkat->format('d M Y');
        $permintaan->delete();

        // Reservasi slot permintaan ini dilepas → proses antrean sesuai prioritas
        try { $this->approval->prosesAntrian(); } catch (\Throwable) {}

        // Notifikasi ke dispatcher (permintaan dibatalkan user)
        Notifikasi::kirimKeDispatcher(
            Auth::user()->nama . " membatalkan permintaan: \"{$tujuan}\" ({$tgl})"
        );

        return redirect()->route('user.permintaan.index')
            ->with('success', 'Permintaan berhasil dibatalkan.');
    }

    /** Seluruh pemeriksaan waktu pengajuan memakai WIB. */
    private const ZONA_WAKTU = 'Asia/Jakarta';

    /** Jendela pengajuan request H-1 (untuk penggunaan besok): 08:00 s.d. sebelum 16:00 WIB. */
    private const JAM_BUKA_H1  = '08:00';
    private const JAM_TUTUP_H1 = '16:00';

    /**
     * Kondisi jendela pengajuan saat ini (dipakai backend & form).
     * - Hari ini (hari yang sama): selalu boleh diajukan.
     * - Besok (H-1): hanya pukul 08:00–16:00 WIB, berlaku setiap hari termasuk Sabtu/Minggu.
     */
    private function jendelaPengajuan(): array
    {
        $sekarang = now(self::ZONA_WAKTU);
        $jam      = $sekarang->format('H:i');

        return [
            'sekarang'    => $sekarang,
            'hariIni'     => $sekarang->copy()->startOfDay(),
            'besok'       => $sekarang->copy()->startOfDay()->addDay(),
            'besokDibuka' => $jam >= self::JAM_BUKA_H1 && $jam < self::JAM_TUTUP_H1,
            'jamBuka'     => self::JAM_BUKA_H1,
            'jamTutup'    => self::JAM_TUTUP_H1,
        ];
    }

    /**
     * Aturan tanggal berangkat untuk pengajuan request oleh user.
     * - Tanggal hari ini: selalu diterima (tetap melalui urgensi, AHP-SAW, ketersediaan & persetujuan dispatcher).
     * - Tanggal besok: hanya diterima bila diajukan pukul 08:00–16:00 WIB.
     * - Tanggal lain: ditolak.
     *
     * @param string|null $tanggalTersimpan Saat edit: tanggal yang sudah tersimpan tidak diperiksa ulang
     *                                      jendela waktunya (permintaan yang sudah diajukan tetap sah).
     */
    private function aturanTanggalBerangkat(?string $tanggalTersimpan = null): array
    {
        $j = $this->jendelaPengajuan();

        return [
            'bail', 'required', 'date',
            function (string $attribute, $value, \Closure $fail) use ($j, $tanggalTersimpan) {
                try {
                    $tanggal = \Carbon\Carbon::parse($value, self::ZONA_WAKTU)->startOfDay();
                } catch (\Throwable) {
                    return; // format tanggal ditangani aturan 'date'
                }

                if ($tanggal->equalTo($j['hariIni'])) {
                    return; // request hari yang sama selalu boleh diajukan
                }

                if ($tanggal->equalTo($j['besok'])) {
                    if ($j['besokDibuka'] || $tanggal->format('Y-m-d') === $tanggalTersimpan) {
                        return;
                    }
                    $fail("Pengajuan request H-1 untuk tanggal {$tanggal->translatedFormat('d M Y')} hanya dibuka pukul "
                        . "{$j['jamBuka']}–{$j['jamTutup']} WIB (sekarang {$j['sekarang']->format('H:i')} WIB). "
                        . "Anda tetap dapat mengajukan request untuk penggunaan hari ini.");
                    return;
                }

                $fail("Request kendaraan hanya dapat diajukan untuk hari ini ({$j['hariIni']->translatedFormat('d M Y')}) "
                    . "atau besok ({$j['besok']->translatedFormat('d M Y')}, pukul {$j['jamBuka']}–{$j['jamTutup']} WIB).");
            },
        ];
    }

    private function runSawSilently(): void
    {
        try {
            $this->saw->hitung();
        } catch (\Throwable) {
            // Kegagalan SAW tidak memblokir proses permintaan
        }
    }

    /* ════════════════════════════════════════════════════════════
     | FITUR 1 — Cancel Booking oleh User (status: terjadwal)
     ═══════════════════════════════════════════════════════════ */

    public function cancelForm($id)
    {
        $permintaan = Permintaan::with('jadwal.kendaraan')
            ->where('id_user', Auth::id())
            ->whereIn('status', ['terjadwal', 'menunggu_konfirmasi'])
            ->findOrFail($id);

        return view('user.permintaan.cancel', compact('permintaan'));
    }

    public function cancelStore(Request $request, $id)
    {
        $request->validate([
            'alasan_batal' => 'required|string|max:500',
        ], [
            'alasan_batal.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        $permintaan = Permintaan::with('jadwal.kendaraan.driverUser')
            ->where('id_user', Auth::id())
            ->whereIn('status', ['terjadwal', 'menunggu_konfirmasi'])
            ->findOrFail($id);

        $dataLama = [
            'status'            => $permintaan->status,
            'tanggal_berangkat' => $permintaan->tanggal_berangkat->format('Y-m-d'),
        ];

        // Batalkan jadwal dan bebaskan kendaraan
        if ($permintaan->jadwal) {
            $jadwal = $permintaan->jadwal;
            $jadwal->update(['status' => 'batal']);

            if ($jadwal->kendaraan) {
                $jadwal->kendaraan->update(['status' => 'tersedia']);
            }

            // Kurangi counter perjalanan driver
            if ($jadwal->kendaraan?->driverUser) {
                $driver = $jadwal->kendaraan->driverUser;
                $driver->update([
                    'jumlah_perjalanan' => max(0, $driver->jumlah_perjalanan - 1),
                ]);
            }
        }

        // Update permintaan ke status dibatalkan_user
        $permintaan->update([
            'status'          => 'dibatalkan_user',
            'alasan_batal'    => $request->alasan_batal,
            'dibatalkan_pada' => now(),
        ]);

        // Hapus dari hasil_saw
        HasilSaw::where('id_permintaan', $permintaan->id_permintaan)->delete();

        // Kendaraan dibebaskan → berikan ke antrean yang sudah disetujui sesuai prioritas SAW
        try { $this->approval->prosesAntrian(); } catch (\Throwable) {}

        // Audit trail
        PermintaanLog::catat(
            idPermintaan: $permintaan->id_permintaan,
            userId:       Auth::id(),
            tipe:         'batal_user',
            keterangan:   $request->alasan_batal,
            dataLama:     $dataLama,
            dataBaru:     ['status' => 'dibatalkan_user', 'dibatalkan_pada' => now()->toDateTimeString()],
        );

        // Notifikasi ke dispatcher
        $user = Auth::user();
        Notifikasi::kirimKeDispatcher(
            "{$user->nama} membatalkan booking #{$permintaan->id_permintaan} ke \"{$permintaan->tujuan}\". Alasan: {$request->alasan_batal}",
            route('admin.permintaan.show', $permintaan->id_permintaan)
        );

        return redirect()->route('user.permintaan.index')
            ->with('success', $dataLama['status'] === 'menunggu_konfirmasi'
                ? 'Permintaan berhasil dibatalkan dan dikeluarkan dari antrean.'
                : 'Booking berhasil dibatalkan. Kendaraan telah dibebaskan.');
    }

    /* ════════════════════════════════════════════════════════════
     | FITUR 2 — Reschedule Booking oleh User
     |           (status: disetujui atau terjadwal)
     ═══════════════════════════════════════════════════════════ */

    public function rescheduleForm($id)
    {
        $permintaan = Permintaan::where('id_user', Auth::id())
            ->whereIn('status', ['disetujui', 'terjadwal', 'menunggu_konfirmasi'])
            ->findOrFail($id);

        return view('user.permintaan.reschedule', compact('permintaan'));
    }

    public function rescheduleStore(Request $request, $id)
    {
        $permintaan = Permintaan::with('jadwal.kendaraan')
            ->where('id_user', Auth::id())
            ->whereIn('status', ['disetujui', 'terjadwal', 'menunggu_konfirmasi'])
            ->findOrFail($id);

        $data = $request->validate([
            'tanggal_berangkat'  => ['required', 'date', 'after_or_equal:today'],
            'jam_berangkat'      => 'required|date_format:H:i',
            'tanggal_kembali'    => 'nullable|date|after_or_equal:tanggal_berangkat',
            'jam_kembali'        => 'nullable|date_format:H:i',
            'alasan_reschedule'  => 'required|string|max:500',
        ], [
            'tanggal_berangkat.after_or_equal' => 'Tanggal berangkat tidak boleh di masa lalu.',
            'alasan_reschedule.required'       => 'Alasan reschedule wajib diisi.',
        ]);

        $dataLama = [
            'tanggal_berangkat' => $permintaan->tanggal_berangkat->format('Y-m-d'),
            'jam_berangkat'     => $permintaan->jam_berangkat,
            'status'            => $permintaan->status,
        ];

        // Jika sudah terjadwal: hapus jadwal lama dan bebaskan kendaraan + kurangi counter driver
        if ($permintaan->status === 'terjadwal' && $permintaan->jadwal) {
            $jadwalLama = $permintaan->jadwal;
            $jadwalLama->load('kendaraan.driverUser');

            if ($jadwalLama->kendaraan?->driverUser) {
                $jadwalLama->kendaraan->driverUser->decrement('jumlah_perjalanan');
            }
            $jadwalLama->kendaraan?->update(['status' => 'tersedia']);
            $jadwalLama->delete();
        }

        // Update tanggal dan kembalikan ke antrian
        $permintaan->update([
            'tanggal_berangkat'  => $data['tanggal_berangkat'],
            'jam_berangkat'      => $data['jam_berangkat'],
            'tanggal_kembali'    => $data['tanggal_kembali'] ?? null,
            'jam_kembali'        => $data['jam_kembali'] ?? null,
            'status'             => 'disetujui',
            'catatan_dispatcher' => null,
            'feedback_type'      => null,
        ]);

        // Audit trail
        PermintaanLog::catat(
            idPermintaan: $permintaan->id_permintaan,
            userId:       Auth::id(),
            tipe:         'reschedule_user',
            keterangan:   $data['alasan_reschedule'],
            dataLama:     $dataLama,
            dataBaru:     [
                'tanggal_berangkat' => $data['tanggal_berangkat'],
                'jam_berangkat'     => $data['jam_berangkat'],
            ],
        );

        $this->runSawSilently();

        // Coba auto-jadwalkan untuk tanggal baru
        $berhasilAutoJadwal = $this->approval->autoJadwalkan($permintaan->fresh());

        // Notifikasi ke dispatcher
        $user    = Auth::user();
        $tglBaru = \Carbon\Carbon::parse($data['tanggal_berangkat'])->format('d M Y');
        Notifikasi::kirimKeDispatcher(
            "{$user->nama} mengajukan reschedule booking #{$permintaan->id_permintaan} ke \"{$permintaan->tujuan}\" → tanggal baru: {$tglBaru} {$data['jam_berangkat']}",
            route('admin.permintaan.show', $permintaan->id_permintaan)
        );

        $pesanSukses = $berhasilAutoJadwal
            ? 'Reschedule berhasil. Kendaraan telah dijadwalkan otomatis untuk tanggal baru Anda.'
            : 'Reschedule berhasil. Dispatcher akan menjadwalkan kendaraan untuk tanggal baru Anda.';

        return redirect()->route('user.permintaan.show', $id)
            ->with('success', $pesanSukses);
    }
}
