<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Kendaraan;
use App\Models\Notifikasi;
use App\Models\Permintaan;
use App\Models\PermintaanLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ApprovalService
{
    /** Jam perjalanan diisi user dalam WIB. */
    public const ZONA_WAKTU = 'Asia/Jakarta';

    public function __construct(
        private SawService $saw,
        private VehicleScheduleService $schedule,
        private GageService $gage,
    ) {}

    /**
     * Setujui permintaan.
     * Permintaan masuk antrean penjadwalan; kendaraan dialokasikan sesuai urutan
     * prioritas SAW (lihat prosesAntrian).
     * Jika mendapat kendaraan → 'terjadwal'.
     * Jika tidak ada kendaraan → 'menunggu_konfirmasi' (dispatcher assign manual / ubah tanggal).
     */
    public function setujui(Permintaan $permintaan): void
    {
        $permintaan->update(['status' => 'disetujui']);

        if ($this->autoJadwalkan($permintaan)) {
            return; // berhasil auto-jadwal, notifikasi sudah dikirim saat jadwal dibuat
        }

        // Tidak mendapat kendaraan → feedback otomatis "driver tidak tersedia":
        // karena driver cuti/sakit/dll, atau semua kendaraan + driver sudah dibooking user lain.
        $kendala = $this->kendalaDriver($permintaan) ?? $this->keteranganKendaraanPenuh($permintaan);
        $this->tandaiDriverTidakTersedia($permintaan, $kendala);
    }

    /** Keterangan saat semua kendaraan (yang sesuai aturan ganjil-genap) sudah dibooking pada jam tersebut. */
    public function keteranganKendaraanPenuh(Permintaan $permintaan): string
    {
        [, , , , $tipeGage] = $this->slotPermintaan($permintaan);

        return $tipeGage
            ? "semua kendaraan plat {$tipeGage} (aturan ganjil-genap) beserta driver-nya sudah dibooking pada jam tersebut"
            : 'semua kendaraan beserta driver-nya sudah dibooking pada jam tersebut';
    }

    /* ════════════════════════════════════════════════════════════
     | Feedback otomatis: driver tidak tersedia
     ═══════════════════════════════════════════════════════════ */

    public const LABEL_STATUS_DRIVER = [
        'tersedia'   => 'Tersedia',
        'cuti'       => 'Cuti',
        'sakit'      => 'Sakit',
        'dinas_luar' => 'Dinas Luar',
        'istirahat'  => 'Istirahat',
    ];

    /**
     * Jika permintaan tidak mendapat kendaraan HANYA karena driver tidak tersedia,
     * kembalikan keterangan driver-nya (mis. "Komeng (Cuti)"). Selain itu null.
     */
    public function kendalaDriver(Permintaan $permintaan): ?string
    {
        [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai, $tipeGage] = $this->slotPermintaan($permintaan);

        $tanpaCekDriver = $this->schedule->kendaraanTersedia(
            $tanggal, $jamMulai, $jamSelesai, $tipeGage, $tanggalSelesai, [], false
        );
        if ($tanpaCekDriver->isEmpty()) {
            return null; // memang tidak ada kendaraan (bentrok jadwal / gage)
        }

        $denganCekDriver = $this->schedule->kendaraanTersedia(
            $tanggal, $jamMulai, $jamSelesai, $tipeGage, $tanggalSelesai
        );
        if ($denganCekDriver->isNotEmpty()) {
            return null; // ada kendaraan + driver, kendala lain (antrean prioritas)
        }

        return $tanpaCekDriver->load('driverUser')
            ->map(fn($k) => ($k->driverUser?->nama ?? $k->nama_driver ?? 'Driver')
                . ' (' . (self::LABEL_STATUS_DRIVER[$k->driverUser?->status_driver] ?? 'tidak tersedia') . ')')
            ->unique()
            ->implode(', ');
    }

    /**
     * Tandai permintaan: driver tidak tersedia.
     * Status → 'menunggu_konfirmasi' (alur yang sudah ada untuk "belum ada kendaraan"),
     * feedback diisi otomatis, user & dispatcher diberi notifikasi, dicatat di log permintaan.
     * Permintaan tetap di antrean dan dijadwalkan otomatis bila driver/kendaraan tersedia.
     */
    public function tandaiDriverTidakTersedia(Permintaan $permintaan, string $keteranganDriver, ?int $aktorId = null): void
    {
        $tgl     = $permintaan->tanggal_berangkat->format('d M Y');
        $jam     = substr((string) ($permintaan->jam_berangkat ?? '08:00'), 0, 5);
        $catatan = "Driver tidak tersedia untuk jadwal {$tgl} pukul {$jam}: {$keteranganDriver}. "
                 . "Permintaan Anda tetap dalam antrean dan akan dijadwalkan otomatis begitu ada driver/kendaraan yang tersedia, "
                 . "atau Anda dapat melakukan reschedule maupun membatalkan permintaan ini.";

        $permintaan->update([
            'status'             => 'menunggu_konfirmasi',
            'feedback_type'      => 'driver_tidak_tersedia',
            'catatan_dispatcher' => $catatan,
        ]);

        Notifikasi::kirim(
            $permintaan->id_user,
            "Driver untuk perjalanan Anda ke \"{$permintaan->tujuan}\" ({$tgl} {$jam}) tidak tersedia. Permintaan tetap dalam antrean — Anda dapat reschedule atau membatalkannya.",
            route('user.permintaan.show', $permintaan->id_permintaan)
        );

        Notifikasi::kirimKeDispatcher(
            "Info otomatis: driver tidak tersedia untuk permintaan #{$permintaan->id_permintaan} ke \"{$permintaan->tujuan}\" ({$tgl} {$jam}) — {$keteranganDriver}. Pemohon sudah diberi tahu.",
            route('admin.permintaan.show', $permintaan->id_permintaan)
        );

        PermintaanLog::catat(
            idPermintaan: $permintaan->id_permintaan,
            userId:       $aktorId ?? Auth::id() ?? $permintaan->id_user,
            tipe:         'driver_tidak_tersedia',
            keterangan:   $keteranganDriver,
        );
    }

    /**
     * Driver mengubah statusnya menjadi tidak tersedia (cuti/sakit/dinas_luar/istirahat).
     * Jadwal driver yang BELUM dimulai dilepas, lalu antrean diproses ulang:
     * - dapat kendaraan lain → dijadwalkan otomatis (user diberi tahu pengalihannya);
     * - tidak ada → feedback otomatis "driver tidak tersedia".
     *
     * @return array{dialihkan:int, menunggu:int}
     */
    public function driverTidakTersedia(User $driver): array
    {
        $statusLabel = self::LABEL_STATUS_DRIVER[$driver->status_driver] ?? $driver->status_driver;
        $sekarang    = now(self::ZONA_WAKTU);

        $jadwalList = Jadwal::with(['permintaan', 'kendaraan'])
            ->where('status', 'aktif')
            ->where('status_perjalanan', 'aktif')
            ->whereDate('tanggal', '>=', $sekarang->toDateString())
            ->whereHas('kendaraan', fn($q) => $q->where('driver_user_id', $driver->id_user))
            ->get()
            // hanya perjalanan yang belum dimulai
            ->filter(fn($j) => Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->jam_mulai, self::ZONA_WAKTU)->gt($sekarang));

        $terdampak = [];
        foreach ($jadwalList as $j) {
            $j->kendaraan?->update(['status' => 'tersedia']);
            if ($driver->jumlah_perjalanan > 0) {
                $driver->decrement('jumlah_perjalanan');
            }
            $j->permintaan?->update(['status' => 'disetujui']);
            if ($j->permintaan) {
                $terdampak[] = $j->permintaan->id_permintaan;
            }
            $j->delete();
        }

        if (empty($terdampak)) {
            return ['dialihkan' => 0, 'menunggu' => 0];
        }

        $this->prosesAntrian();

        $hasil = ['dialihkan' => 0, 'menunggu' => 0];
        foreach (Permintaan::with('jadwal.kendaraan')->whereIn('id_permintaan', $terdampak)->get() as $p) {
            $keterangan = "{$driver->nama} ({$statusLabel})";

            if ($p->jadwal && $p->jadwal->status === 'aktif') {
                $hasil['dialihkan']++;
                Notifikasi::kirim(
                    $p->id_user,
                    "Driver {$driver->nama} tidak tersedia ({$statusLabel}). Perjalanan Anda ke \"{$p->tujuan}\" dialihkan otomatis ke kendaraan {$p->jadwal->kendaraan?->no_polisi}.",
                    route('user.permintaan.show', $p->id_permintaan)
                );
                PermintaanLog::catat(
                    idPermintaan: $p->id_permintaan,
                    userId:       $driver->id_user,
                    tipe:         'driver_tidak_tersedia',
                    keterangan:   "{$keterangan} — dialihkan otomatis ke {$p->jadwal->kendaraan?->no_polisi}",
                );
            } else {
                $hasil['menunggu']++;
                $this->tandaiDriverTidakTersedia($p, $keterangan, $driver->id_user);
            }
        }

        return $hasil;
    }

    /** Driver kembali tersedia → proses antrean agar permintaan yang menunggu dijadwalkan. */
    public function driverTersediaKembali(): void
    {
        $this->prosesAntrian();
    }

    /**
     * Coba jadwalkan permintaan secara otomatis.
     * Dipanggil dari setujui() dan setelah reschedule oleh user.
     * Alokasi dilakukan lewat antrean prioritas SAW, sehingga permintaan dengan
     * nilai preferensi lebih tinggi yang bersaing pada slot yang sama didahulukan.
     * Mengembalikan true jika permintaan ini berhasil dijadwalkan.
     */
    public function autoJadwalkan(Permintaan $permintaan): bool
    {
        // Lewati jika sudah punya jadwal aktif
        if ($permintaan->jadwal()->where('status', 'aktif')->exists()) {
            return false;
        }

        $this->prosesAntrian();

        return $permintaan->jadwal()->where('status', 'aktif')->exists();
    }

    /**
     * Proses antrean penjadwalan berdasarkan ranking SAW.
     *
     * Kandidat: permintaan aktif (menunggu / disetujui / menunggu_konfirmasi) tanpa
     * jadwal aktif, untuk hari ini dan seterusnya, diurutkan menurut ranking SAW
     * (nilai preferensi tertinggi lebih dulu; nilai sama → urutan ranking SAW).
     *
     * - Permintaan yang sudah disetujui dan mendapat kendaraan → dibuatkan jadwal.
     * - Permintaan 'menunggu' (belum disetujui) berprioritas lebih tinggi hanya
     *   MEMESAN SEMENTARA kendaraannya agar tidak diambil permintaan berprioritas
     *   lebih rendah. Jadwalnya baru dibuat setelah dispatcher menyetujui.
     *
     * @return int[] id_permintaan yang berhasil dijadwalkan
     */
    public function prosesAntrian(): array
    {
        // Pastikan ranking SAW terbaru sebelum alokasi
        try { $this->saw->hitung(); } catch (\Throwable) {}

        $kandidat = Permintaan::with('hasilSaw')
            ->whereIn('status', ['menunggu', 'disetujui', 'menunggu_konfirmasi'])
            ->whereDate('tanggal_berangkat', '>=', today())
            ->whereDoesntHave('jadwal', fn($q) => $q->where('status', 'aktif'))
            ->get()
            ->sortBy([
                fn($a, $b) => ($a->hasilSaw?->ranking ?? PHP_INT_MAX) <=> ($b->hasilSaw?->ranking ?? PHP_INT_MAX),
                fn($a, $b) => $a->id_permintaan <=> $b->id_permintaan,
            ]);

        $reservasi   = [];
        $dijadwalkan = [];

        foreach ($kandidat as $p) {
            [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai, $tipeGage] = $this->slotPermintaan($p);

            $kendaraan = $this->schedule
                ->kendaraanTersedia($tanggal, $jamMulai, $jamSelesai, $tipeGage, $tanggalSelesai, $reservasi)
                ->first();

            if (! $kendaraan) {
                continue;
            }

            if ($p->status === 'menunggu') {
                [$mulai, $selesai] = $this->schedule->rentang($tanggal, $jamMulai, $jamSelesai, $tanggalSelesai);
                $reservasi[] = [
                    'id_kendaraan' => $kendaraan->id_kendaraan,
                    'mulai'        => $mulai,
                    'selesai'      => $selesai,
                ];
                continue;
            }

            $this->buatJadwal($p, $kendaraan, $tanggal, $jamMulai, $jamSelesai);
            $dijadwalkan[] = $p->id_permintaan;
        }

        return $dijadwalkan;
    }

    /**
     * Saran tanggal terdekat setelah tanggal permintaan yang masih memiliki
     * kendaraan tersedia pada jam yang sama. Hanya saran untuk dispatcher —
     * perubahan tanggal tetap melalui form "Ubah Tanggal" (alur yang sudah ada).
     */
    public function tanggalTersediaBerikutnya(Permintaan $permintaan, int $maksHari = 7): ?string
    {
        [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai] = $this->slotPermintaan($permintaan);

        $durasiHari = (int) Carbon::parse($tanggal)->diffInDays(Carbon::parse($tanggalSelesai ?? $tanggal));
        $dasar      = Carbon::parse($tanggal)->max(today()->subDay());
        $gageAktif  = $this->gage->isGageAktif($jamMulai, $jamSelesai);

        for ($i = 1; $i <= $maksHari; $i++) {
            $tgl      = $dasar->copy()->addDays($i);
            $tipeGage = $gageAktif ? $this->gage->tipeUntukTanggal($tgl) : null;
            $tglAkhir = $tanggalSelesai ? $tgl->copy()->addDays($durasiHari)->format('Y-m-d') : null;

            if ($this->schedule->kendaraanTersedia($tgl->format('Y-m-d'), $jamMulai, $jamSelesai, $tipeGage, $tglAkhir)->isNotEmpty()) {
                return $tgl->format('Y-m-d');
            }
        }

        return null;
    }

    /** [tanggal, jamMulai, jamSelesai, tanggalSelesai|null, tipeGage|null] untuk permintaan. */
    private function slotPermintaan(Permintaan $p): array
    {
        $tanggal        = $p->tanggal_berangkat->format('Y-m-d');
        $jamMulai       = $p->jam_berangkat ?? '08:00';
        $jamSelesai     = $p->jam_kembali   ?? '17:00';
        $tanggalSelesai = $p->tanggal_kembali?->format('Y-m-d');

        $gageAktif = $this->gage->isGageAktif($jamMulai, $jamSelesai);
        $tipeGage  = $gageAktif ? $this->gage->tipeUntukTanggal($p->tanggal_berangkat) : null;

        return [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai, $tipeGage];
    }

    /** Buat jadwal untuk permintaan + kirim notifikasi ke driver dan pemohon. */
    private function buatJadwal(Permintaan $permintaan, Kendaraan $kendaraan, string $tanggal, string $jamMulai, string $jamSelesai): void
    {
        $kendaraan->load('driverUser');

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
            $kendaraan->driverUser->increment('jumlah_perjalanan');
            Notifikasi::kirim(
                $kendaraan->driverUser->id_user,
                "Anda mendapat jadwal tugas baru pada " . Carbon::parse($jadwal->tanggal)->format('d M Y') . ".",
                route('driver.jadwal.show', $jadwal->id_jadwal)
            );
        }

        Notifikasi::kirim(
            $permintaan->id_user,
            "Permintaan perjalanan Anda ke \"{$permintaan->tujuan}\" telah dijadwalkan otomatis.",
            route('user.permintaan.show', $permintaan->id_permintaan)
        );
    }

    /**
     * Tolak permintaan → status 'ditolak', simpan alasan sebagai catatan_dispatcher.
     */
    public function tolak(Permintaan $permintaan, string $alasan = ''): void
    {
        $permintaan->update([
            'status'              => 'ditolak',
            'catatan_dispatcher'  => $alasan ?: null,
            'feedback_type'       => 'ditolak',
        ]);
        $this->kirimNotifikasi($permintaan, 'ditolak', $alasan);

        // Slot/reservasi yang dilepas dapat diberikan ke antrean berikutnya sesuai prioritas
        $this->prosesAntrian();
    }

    /**
     * Reschedule permintaan oleh dispatcher → ubah tanggal, beri catatan, notifikasi user.
     * Status tetap 'menunggu_konfirmasi' agar dispatcher dapat assign kendaraan manual.
     */
    public function reschedule(Permintaan $permintaan, string $tanggalBaru, string $catatan = ''): void
    {
        $permintaan->update([
            'tanggal_berangkat'  => $tanggalBaru,
            'status'             => 'menunggu_konfirmasi',
            'catatan_dispatcher' => $catatan ?: null,
            'feedback_type'      => 'dijadwalkan_ulang',
        ]);
        $this->kirimNotifikasi($permintaan, 'dijadwalkan_ulang', $catatan);
    }

    /**
     * Jadwalkan permintaan (assign kendaraan+driver) → status 'terjadwal'.
     */
    public function jadwalkan(Permintaan $permintaan): void
    {
        $permintaan->update(['status' => 'terjadwal']);
        $this->kirimNotifikasi($permintaan, 'terjadwal');
    }

    private function kirimNotifikasi(Permintaan $permintaan, string $status, string $pesan = ''): void
    {
        $teks = match ($status) {
            'disetujui'           => "Permintaan perjalanan Anda ke {$permintaan->tujuan} telah disetujui.",
            'menunggu_konfirmasi' => "Permintaan perjalanan Anda ke {$permintaan->tujuan} disetujui namun belum ada kendaraan tersedia. Dispatcher akan menghubungi Anda.",
            'ditolak'             => "Permintaan perjalanan Anda ke {$permintaan->tujuan} ditolak." . ($pesan ? " Alasan: {$pesan}" : ''),
            'dijadwalkan_ulang'   => "Jadwal perjalanan Anda ke {$permintaan->tujuan} diubah oleh Dispatcher." . ($pesan ? " Keterangan: {$pesan}" : ''),
            'terjadwal'           => "Permintaan perjalanan Anda ke {$permintaan->tujuan} telah dijadwalkan.",
            default               => "Status permintaan Anda diperbarui menjadi {$status}.",
        };

        Notifikasi::kirim($permintaan->id_user, $teks);
    }
}
