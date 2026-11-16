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

    /**
     * Keterangan saat permintaan tidak mendapat kendaraan:
     * kapasitas tidak cukup, atau semua kendaraan (sesuai aturan ganjil-genap) sudah dibooking.
     */
    public function keteranganKendaraanPenuh(Permintaan $permintaan): string
    {
        [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai, $tipeGage, $penumpang] = $this->slotPermintaan($permintaan);

        if ($penumpang) {
            // Tidak ada satu pun kendaraan berkapasitas cukup
            if (! Kendaraan::where('kapasitas', '>=', $penumpang)->exists()) {
                $maks = (int) Kendaraan::max('kapasitas');
                return "tidak ada kendaraan berkapasitas {$penumpang} orang (kapasitas terbesar {$maks} orang)";
            }
            // Ada kendaraan kosong pada jam tersebut, tetapi kapasitasnya kurang
            $tanpaFilterKapasitas = $this->schedule->kendaraanTersedia($tanggal, $jamMulai, $jamSelesai, $tipeGage, $tanggalSelesai);
            if ($tanpaFilterKapasitas->isNotEmpty()) {
                return "kendaraan yang masih tersedia pada jam tersebut berkapasitas kurang dari {$penumpang} penumpang "
                     . "(kapasitas terbesar {$tanpaFilterKapasitas->max('kapasitas')} orang)";
            }
        }

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
        [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai, $tipeGage, $penumpang] = $this->slotPermintaan($permintaan);

        $tanpaCekDriver = $this->schedule->kendaraanTersedia(
            $tanggal, $jamMulai, $jamSelesai, $tipeGage, $tanggalSelesai, [], false, $penumpang
        );
        if ($tanpaCekDriver->isEmpty()) {
            return null; // memang tidak ada kendaraan (bentrok jadwal / gage)
        }

        $denganCekDriver = $this->schedule->kendaraanTersedia(
            $tanggal, $jamMulai, $jamSelesai, $tipeGage, $tanggalSelesai, [], true, $penumpang
        );
        if ($denganCekDriver->isNotEmpty()) {
            return null; // ada kendaraan + driver, kendala lain (antrean prioritas)
        }

        return $tanpaCekDriver->load('driverUser')
            ->map(fn($k) => $k->driverUser
                ? $k->driverUser->nama . ' (' . (self::LABEL_STATUS_DRIVER[$k->driverUser->status_driver] ?? 'tidak tersedia') . ')'
                : "kendaraan {$k->no_polisi} (belum ada driver)")
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
        $catatan = "Driver/kendaraan tidak tersedia untuk jadwal {$tgl} pukul {$jam}: {$keteranganDriver}. "
                 . "Permintaan Anda tetap dalam antrean dan akan dijadwalkan otomatis begitu ada driver/kendaraan yang tersedia, "
                 . "atau Anda dapat melakukan reschedule maupun membatalkan permintaan ini.";

        $permintaan->update([
            'status'             => 'menunggu_konfirmasi',
            'feedback_type'      => 'driver_tidak_tersedia',
            'catatan_dispatcher' => $catatan,
        ]);

        Notifikasi::kirim(
            $permintaan->id_user,
            "Driver/kendaraan untuk perjalanan Anda ke \"{$permintaan->tujuan}\" ({$tgl} {$jam}) tidak tersedia. Permintaan tetap dalam antrean — Anda dapat reschedule atau membatalkannya.",
            route('user.permintaan.show', $permintaan->id_permintaan)
        );

        Notifikasi::kirimKeDispatcher(
            "Info otomatis: driver/kendaraan tidak tersedia untuk permintaan #{$permintaan->id_permintaan} ke \"{$permintaan->tujuan}\" ({$tgl} {$jam}) — {$keteranganDriver}. Pemohon sudah diberi tahu.",
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
            // status_perjalanan 'aktif' = belum berangkat; yang sudah 'berjalan' tidak dilepas
            ->whereHas('kendaraan', fn($q) => $q->where('driver_user_id', $driver->id_user))
            ->get();

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

    /* ════════════════════════════════════════════════════════════
     | Pengalihan kendaraan oleh Dispatcher (prioritas lebih tinggi)
     ═══════════════════════════════════════════════════════════ */

    /**
     * Alihkan kendaraan dari jadwal $target (prioritas lebih rendah, belum berangkat)
     * ke permintaan $sumber (prioritas lebih tinggi, belum mendapat kendaraan).
     *
     * Jadwal target dilepas → permintaan target kembali 'disetujui' dengan feedback 'dioverride',
     * lalu antrean diproses: $sumber didahulukan karena prioritasnya lebih tinggi,
     * dan booking yang dialihkan ikut kembali ke antrean.
     *
     * @return string|null  pesan error bila tidak memenuhi syarat; null bila berhasil
     */
    public function alihkanKendaraan(Permintaan $sumber, Jadwal $target, int $aktorId): ?string
    {
        $target->loadMissing(['permintaan', 'kendaraan.driverUser']);
        $pTarget   = $target->permintaan;
        $kendaraan = $target->kendaraan;

        if (! in_array($sumber->status, ['menunggu_konfirmasi', 'disetujui'])
            || $sumber->jadwal()->where('status', 'aktif')->exists()) {
            return 'Permintaan ini sudah mendapat kendaraan atau tidak sedang menunggu kendaraan.';
        }
        if (! $pTarget || $target->status !== 'aktif') {
            return 'Jadwal yang dipilih tidak aktif.';
        }
        if (! $target->belumBerangkat()) {
            return Jadwal::PESAN_SUDAH_BERANGKAT;
        }
        if ($sumber->urutanPriority() >= $pTarget->urutanPriority()) {
            return 'Kendaraan hanya dapat dialihkan dari booking berprioritas lebih rendah.';
        }

        // Pastikan kendaraan target memang bisa dipakai permintaan sumber
        [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai, , $penumpang] = $this->slotPermintaan($sumber);
        if (! $kendaraan?->driverUser || $kendaraan->driverUser->status_driver !== 'tersedia') {
            return "Kendaraan {$kendaraan?->no_polisi} tidak memiliki driver yang tersedia.";
        }
        if ($penumpang && $kendaraan->kapasitas < $penumpang) {
            return "Kapasitas kendaraan {$kendaraan->no_polisi} ({$kendaraan->kapasitas} orang) kurang dari jumlah penumpang ({$penumpang} orang).";
        }
        if (! $this->gage->sesuai($kendaraan, $sumber->tanggal_berangkat, $jamMulai, $jamSelesai)) {
            return "Kendaraan {$kendaraan->no_polisi} tidak sesuai aturan ganjil-genap untuk jadwal permintaan ini.";
        }
        if (! $this->schedule->tersedia($kendaraan->id_kendaraan, $tanggal, $jamMulai, $jamSelesai, $target->id_jadwal, $tanggalSelesai)
            || $this->schedule->kendaraanTerkunci()->has($kendaraan->id_kendaraan)) {
            return "Kendaraan {$kendaraan->no_polisi} masih bentrok dengan jadwal lain pada jam tersebut.";
        }

        // ── Lepas jadwal target ─────────────────────────────────────
        $labelSumber = ucfirst($sumber->priority ?? 'normal');
        $catatan     = "Kendaraan {$kendaraan->no_polisi} dialihkan oleh Dispatcher ke permintaan berprioritas lebih tinggi ({$labelSumber}). "
                     . "Permintaan Anda kembali ke antrean dan akan dijadwalkan otomatis bila ada kendaraan lain, "
                     . "atau Anda dapat melakukan reschedule maupun membatalkannya.";

        if ($kendaraan->driverUser->jumlah_perjalanan > 0) {
            $kendaraan->driverUser->decrement('jumlah_perjalanan');
        }
        $kendaraan->update(['status' => 'tersedia']);
        $target->delete();

        $pTarget->update([
            'status'             => 'disetujui',
            'feedback_type'      => 'dioverride',
            'catatan_dispatcher' => $catatan,
        ]);

        Notifikasi::kirim(
            $pTarget->id_user,
            "Kendaraan untuk perjalanan Anda ke \"{$pTarget->tujuan}\" dialihkan ke permintaan berprioritas lebih tinggi. Permintaan Anda kembali ke antrean.",
            route('user.permintaan.show', $pTarget->id_permintaan)
        );

        PermintaanLog::catat(
            idPermintaan: $pTarget->id_permintaan,
            userId:       $aktorId,
            tipe:         'kendaraan_dialihkan',
            keterangan:   "Kendaraan {$kendaraan->no_polisi} dialihkan ke permintaan #{$sumber->id_permintaan} ({$labelSumber})",
            dataLama:     ['id_kendaraan' => $kendaraan->id_kendaraan, 'status' => 'terjadwal'],
            dataBaru:     ['status' => 'disetujui'],
        );
        PermintaanLog::catat(
            idPermintaan: $sumber->id_permintaan,
            userId:       $aktorId,
            tipe:         'kendaraan_dialihkan',
            keterangan:   "Menerima kendaraan {$kendaraan->no_polisi} dari permintaan #{$pTarget->id_permintaan} (" . ucfirst($pTarget->priority ?? 'normal') . ')',
        );

        // Sumber didahulukan (prioritas lebih tinggi); booking yang dialihkan kembali ke antrean
        $sumber->update(['status' => 'disetujui']);
        $this->prosesAntrian();

        return null;
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

        // Permintaan 'terjadwal' / 'berjalan' tidak pernah dialokasikan ulang
        $kandidat = Permintaan::with('hasilSaw')
            ->whereIn('status', ['menunggu', 'disetujui', 'menunggu_konfirmasi'])
            ->whereDate('tanggal_berangkat', '>=', today())
            ->whereDoesntHave('jadwal', fn($q) => $q->where('status', 'aktif'))
            ->get()
            // Urutan: prioritas dispatcher (urgent → high → normal) → ranking SAW → id.
            // Nilai & ranking SAW tidak diubah; prioritas hanya menentukan siapa dilayani lebih dulu.
            ->sortBy([
                fn($a, $b) => $a->urutanPriority() <=> $b->urutanPriority(),
                fn($a, $b) => ($a->hasilSaw?->ranking ?? PHP_INT_MAX) <=> ($b->hasilSaw?->ranking ?? PHP_INT_MAX),
                fn($a, $b) => $a->id_permintaan <=> $b->id_permintaan,
            ]);

        $reservasi   = [];
        $dijadwalkan = [];

        foreach ($kandidat as $p) {
            [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai, $tipeGage, $penumpang] = $this->slotPermintaan($p);

            $kendaraan = $this->schedule
                ->kendaraanTersedia($tanggal, $jamMulai, $jamSelesai, $tipeGage, $tanggalSelesai, $reservasi, true, $penumpang)
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
        [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai, , $penumpang] = $this->slotPermintaan($permintaan);

        $durasiHari = (int) Carbon::parse($tanggal)->diffInDays(Carbon::parse($tanggalSelesai ?? $tanggal));
        $dasar      = Carbon::parse($tanggal)->max(today()->subDay());
        $gageAktif  = $this->gage->isGageAktif($jamMulai, $jamSelesai);

        for ($i = 1; $i <= $maksHari; $i++) {
            $tgl      = $dasar->copy()->addDays($i);
            $tipeGage = $gageAktif ? $this->gage->tipeUntukTanggal($tgl) : null;
            $tglAkhir = $tanggalSelesai ? $tgl->copy()->addDays($durasiHari)->format('Y-m-d') : null;

            if ($this->schedule->kendaraanTersedia($tgl->format('Y-m-d'), $jamMulai, $jamSelesai, $tipeGage, $tglAkhir, [], true, $penumpang)->isNotEmpty()) {
                return $tgl->format('Y-m-d');
            }
        }

        return null;
    }

    /* ════════════════════════════════════════════════════════════
     | Saran jadwal alternatif saat kendaraan penuh
     ═══════════════════════════════════════════════════════════ */

    /** Jam kerja kendaraan untuk saran jam alternatif. */
    public const JAM_KERJA_MULAI   = '06:00';
    public const JAM_KERJA_SELESAI = '21:00';

    /**
     * Apakah masih ada kendaraan (ber-driver, kapasitas cukup, sesuai gage, tidak bentrok/terkunci)
     * untuk slot ini? Aturannya sama persis dengan alokasi otomatis.
     */
    public function adaKendaraan(string $tanggal, string $jamMulai, ?string $jamKembali, ?int $penumpang): bool
    {
        $jamSelesai = $jamKembali ?: '17:00';
        $tipeGage   = $this->gage->isGageAktif($jamMulai, $jamSelesai) ? $this->gage->tipeUntukTanggal($tanggal) : null;

        return $this->schedule
            ->kendaraanTersedia($tanggal, $jamMulai, $jamSelesai, $tipeGage, null, [], true, $penumpang)
            ->isNotEmpty();
    }

    /**
     * Saran jadwal yang masih ada kendaraannya.
     * Untuk setiap tanggal kandidat (urut): jam yang sama dulu, lalu jam digeser per 1 jam
     * (+1, −1, +2, −2, …) selama masih dalam jam kerja 06:00–21:00. Jam yang sudah lewat (WIB) dilewati.
     *
     * @param  string[] $tanggalKandidat  'Y-m-d', sudah sesuai jendela pengajuan pemanggil
     * @param  bool     $lewatiSlotAsal   true = slot asal (tanggal & jam sama) tidak disarankan (diketahui penuh)
     * @return array<int, array{tanggal:string, jam_berangkat:string, jam_kembali:?string, label:string}>
     */
    public function saranJadwal(
        string $tanggalAsal,
        string $jamMulai,
        ?string $jamKembali,
        ?int $penumpang,
        array $tanggalKandidat,
        bool $lewatiSlotAsal = true,
        int $maks = 4
    ): array {
        $jamMulai   = substr($jamMulai, 0, 5);
        $jamKembali = $jamKembali ? substr($jamKembali, 0, 5) : null;
        $sekarang   = now(self::ZONA_WAKTU);

        $mulaiMenit  = $this->keMenit($jamMulai);
        $durasiMenit = $jamKembali && $this->keMenit($jamKembali) > $mulaiMenit ? $this->keMenit($jamKembali) - $mulaiMenit : 60;
        $batasAwal   = $this->keMenit(self::JAM_KERJA_MULAI);
        $batasAkhir  = $this->keMenit(self::JAM_KERJA_SELESAI);

        // Urutan geser: 0, +1, −1, +2, −2, … jam
        $geser = [0];
        for ($i = 1; $i <= 15; $i++) { $geser[] = $i * 60; $geser[] = -$i * 60; }

        // Kuota per tanggal: tanggal pertama mendapat sisa kuota, tanggal lain masing-masing 1
        // (agar selalu ada saran "jam lain hari yang sama" sekaligus "tanggal terdekat").
        $tanggalKandidat = array_values(array_unique($tanggalKandidat));
        $kuotaLain       = max(0, count($tanggalKandidat) - 1);

        $saran = [];
        foreach ($tanggalKandidat as $idx => $tanggal) {
            $kuota = $idx === 0 ? max(1, $maks - $kuotaLain) : 1;
            $dapat = 0;
            foreach ($geser as $g) {
                if ($dapat >= $kuota || count($saran) >= $maks) {
                    break;
                }
                $m = $mulaiMenit + $g;
                if ($m < $batasAwal || $m + $durasiMenit > $batasAkhir) {
                    continue;
                }
                if ($lewatiSlotAsal && $g === 0 && $tanggal === $tanggalAsal) {
                    continue;
                }

                $jb = $this->dariMenit($m);
                $jk = $jamKembali ? $this->dariMenit($m + $durasiMenit) : null;

                // Lewati jam yang sudah lewat untuk hari ini
                if (Carbon::parse("{$tanggal} {$jb}", self::ZONA_WAKTU)->lte($sekarang)) {
                    continue;
                }
                if (! $this->adaKendaraan($tanggal, $jb, $jk, $penumpang)) {
                    continue;
                }

                $saran[] = [
                    'tanggal'       => $tanggal,
                    'jam_berangkat' => $jb,
                    'jam_kembali'   => $jk,
                    'label'         => $this->labelSlot($tanggal, $jb, $jk),
                ];
                $dapat++;
            }
        }

        // Tampilkan urut tanggal lalu jam
        usort($saran, fn($a, $b) => [$a['tanggal'], $a['jam_berangkat']] <=> [$b['tanggal'], $b['jam_berangkat']]);
        return $saran;
    }

    /** Saran untuk dispatcher (alasan "Kendaraan penuh"): jam lain di tanggal yang sama + tanggal terdekat. */
    public function saranUntukDispatcher(Permintaan $p): array
    {
        $tanggal   = $p->tanggal_berangkat->format('Y-m-d');
        $kandidat  = [$tanggal];
        $berikut   = $this->tanggalTersediaBerikutnya($p);
        if ($berikut) {
            $kandidat[] = $berikut;
        }

        $saran = $this->saranJadwal(
            $tanggal, (string) ($p->jam_berangkat ?? '08:00'), $p->jam_kembali,
            $p->jumlah_penumpang ? (int) $p->jumlah_penumpang : null, $kandidat
        );

        $teks = $saran
            ? 'Saran jadwal yang masih tersedia: ' . implode('; ', array_column($saran, 'label')) . '.'
            : 'Belum ada jadwal alternatif yang tersedia dalam 7 hari ke depan.';

        return ['saran' => $saran, 'teks' => $teks, 'tanggal_berikutnya' => $berikut];
    }

    /** "Hari ini 13:00–14:00" / "17 Nov 2026 13:00–14:00". */
    public function labelSlot(string $tanggal, string $jamBerangkat, ?string $jamKembali): string
    {
        $hariIni = now(self::ZONA_WAKTU)->format('Y-m-d');
        $besok   = now(self::ZONA_WAKTU)->addDay()->format('Y-m-d');
        $tgl     = match ($tanggal) {
            $hariIni => 'Hari ini',
            $besok   => 'Besok (' . Carbon::parse($tanggal)->format('d M') . ')',
            default  => Carbon::parse($tanggal)->format('d M Y'),
        };

        return $tgl . ' ' . $jamBerangkat . ($jamKembali ? '–' . $jamKembali : '');
    }

    private function keMenit(string $jam): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($jam, 0, 5)));
        return $h * 60 + $m;
    }

    private function dariMenit(int $menit): string
    {
        return sprintf('%02d:%02d', intdiv($menit, 60), $menit % 60);
    }

    /** [tanggal, jamMulai, jamSelesai, tanggalSelesai|null, tipeGage|null, jumlahPenumpang|null] untuk permintaan. */
    private function slotPermintaan(Permintaan $p): array
    {
        $tanggal        = $p->tanggal_berangkat->format('Y-m-d');
        $jamMulai       = $p->jam_berangkat ?? '08:00';
        $jamSelesai     = $p->jam_kembali   ?? '17:00';
        $tanggalSelesai = $p->tanggal_kembali?->format('Y-m-d');

        $gageAktif = $this->gage->isGageAktif($jamMulai, $jamSelesai);
        $tipeGage  = $gageAktif ? $this->gage->tipeUntukTanggal($p->tanggal_berangkat) : null;

        $jumlahPenumpang = $p->jumlah_penumpang ? (int) $p->jumlah_penumpang : null;

        return [$tanggal, $jamMulai, $jamSelesai, $tanggalSelesai, $tipeGage, $jumlahPenumpang];
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
            'menunggu_konfirmasi' => "Permintaan perjalanan Anda ke {$permintaan->tujuan} disetujui namun belum ada kendaraan tersedia. Lihat saran jadwal alternatif di detail permintaan.",
            'ditolak'             => "Permintaan perjalanan Anda ke {$permintaan->tujuan} ditolak." . ($pesan ? " Alasan: {$pesan}" : ''),
            'dijadwalkan_ulang'   => "Jadwal perjalanan Anda ke {$permintaan->tujuan} diubah oleh Dispatcher." . ($pesan ? " Keterangan: {$pesan}" : ''),
            'terjadwal'           => "Permintaan perjalanan Anda ke {$permintaan->tujuan} telah dijadwalkan.",
            default               => "Status permintaan Anda diperbarui menjadi {$status}.",
        };

        // Semua notifikasi status dapat diklik ke detail permintaan
        Notifikasi::kirim($permintaan->id_user, $teks, route('user.permintaan.show', $permintaan->id_permintaan));
    }
}
