<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Kendaraan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VehicleScheduleService
{
    /**
     * Apakah kendaraan tersedia (tidak bentrok) untuk tanggal + jam yang diminta?
     * Jadwal schema: tanggal DATE, jam_mulai TIME, jam_selesai TIME.
     * Perjalanan multi-hari memakai tanggal_kembali milik permintaan.
     *
     * @param  int          $kendaraanId
     * @param  string       $tanggal         'Y-m-d' (tanggal berangkat)
     * @param  string       $jamMulai        'H:i'
     * @param  string       $jamSelesai      'H:i'
     * @param  int|null     $kecualiJadwalId
     * @param  string|null  $tanggalSelesai  'Y-m-d' (tanggal kembali, null = hari yang sama)
     */
    public function tersedia(
        int $kendaraanId,
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?int $kecualiJadwalId = null,
        ?string $tanggalSelesai = null
    ): bool {
        [$mulai, $selesai] = $this->rentang($tanggal, $jamMulai, $jamSelesai, $tanggalSelesai);

        return $this->jadwalBentrok($mulai, $selesai, $kendaraanId, $kecualiJadwalId)->isEmpty();
    }

    /**
     * Daftar kendaraan yang tersedia (gage + booking) untuk tanggal dan jam tertentu.
     *
     * Ketersediaan ditentukan oleh jadwal aktif yang bentrok waktunya, bukan oleh
     * status 'dipakai' saja — status tersebut di-set setiap kali kendaraan mendapat
     * jadwal (termasuk jadwal hari lain). Kendaraan berstatus 'dipakai' TANPA jadwal
     * aktif dianggap ditandai manual oleh admin sebagai tidak dapat dipakai.
     *
     * @param  string|null $tipeGage   'ganjil'|'genap' jika gage aktif, null jika di luar jam gage
     *                                 (null = tidak ada filter gage, semua kendaraan boleh)
     * @param  array       $reservasi  Slot yang sedang dipesan sementara (lihat ApprovalService::prosesAntrian)
     *                                 format: [['id_kendaraan' => int, 'mulai' => Carbon, 'selesai' => Carbon], ...]
     * @param  bool        $cekDriver  true = kendaraan yang driver-nya berstatus selain 'tersedia'
     *                                 (cuti/sakit/dinas_luar/istirahat) tidak dianggap tersedia
     */
    public function kendaraanTersedia(
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?string $tipeGage = null,
        ?string $tanggalSelesai = null,
        array $reservasi = [],
        bool $cekDriver = true
    ) {
        [$mulai, $selesai] = $this->rentang($tanggal, $jamMulai, $jamSelesai, $tanggalSelesai);

        $terpakai = $this->jadwalBentrok($mulai, $selesai)
            ->pluck('id_kendaraan')
            // Terkunci: perjalanan sudah dimulai tetapi belum dikonfirmasi selesai
            ->merge($this->kendaraanTerkunci()->keys())
            ->merge(
                collect($reservasi)
                    ->filter(fn($r) => $r['mulai']->lt($selesai) && $r['selesai']->gt($mulai))
                    ->pluck('id_kendaraan')
            )
            ->unique()
            ->values()
            ->all();

        $hasil = Kendaraan::query()
            ->where(fn($q) => $q->where('status', 'tersedia')
                ->orWhereHas('jadwal', fn($j) => $j->where('status', 'aktif')))
            ->when(
                $tipeGage !== null,
                fn($q) => $q->where(fn($inner) =>
                    $inner->where('tipe_gage', 'bebas')->orWhere('tipe_gage', $tipeGage)
                )
            )
            ->when($cekDriver, fn($q) => $q->where(fn($d) =>
                $d->whereNull('driver_user_id')
                  ->orWhereHas('driverUser', fn($u) => $u->where('status_driver', 'tersedia'))
            ))
            ->whereNotIn('id_kendaraan', $terpakai)
            ->orderBy('id_kendaraan')
            ->get();

        return $this->urutkanKendaraanSiap($hasil, $mulai);
    }

    /**
     * Urutan pemilihan kendaraan yang tersedia (yang pertama dipakai):
     * 1. Kendaraan yang tidak baru kembali dari perjalanan lain di hari yang sama
     *    (jam pulang di lapangan belum tentu tepat waktu).
     * 2. Merit driver tertinggi = paling jarang keluar 7 hari terakhir (rotasi/pemerataan).
     * 3. Perjalanan terakhirnya paling lama, lalu id kendaraan.
     * Setiap kendaraan diberi atribut merit_driver & perjalanan_7_hari untuk ditampilkan.
     */
    private function urutkanKendaraanSiap($kendaraan, Carbon $mulai)
    {
        if ($kendaraan->isEmpty()) {
            return $kendaraan;
        }

        $merit = app(DriverMeritService::class)
            ->untukKendaraan($kendaraan->pluck('id_kendaraan')->all(), $mulai);
        foreach ($kendaraan as $k) {
            // Hanya untuk tampilan (ikut di JSON), tidak boleh ikut tersimpan saat model di-update
            $k->setAttribute('merit_driver', $merit[$k->id_kendaraan]['merit']);
            $k->setAttribute('perjalanan_7_hari', $merit[$k->id_kendaraan]['perjalanan']);
            $k->syncOriginalAttributes(['merit_driver', 'perjalanan_7_hari']);
        }

        $selesaiTerakhir = Jadwal::with('permintaan')
            ->where('status', 'aktif')
            ->whereIn('id_kendaraan', $kendaraan->pluck('id_kendaraan'))
            ->whereDate('tanggal', '<=', $mulai->format('Y-m-d'))
            ->get()
            ->map(fn(Jadwal $j) => ['id' => $j->id_kendaraan, 'selesai' => $this->rentangJadwal($j)[1]])
            ->filter(fn($x) => $x['selesai']->lte($mulai) && $x['selesai']->isSameDay($mulai))
            ->groupBy('id')
            ->map(fn($g) => $g->max('selesai')->format('H:i:s'));

        return $kendaraan
            ->sortBy(fn($k) => [
                isset($selesaiTerakhir[$k->id_kendaraan]) ? 1 : 0,   // baru kembali hari ini → belakang
                -$merit[$k->id_kendaraan]['merit'],                  // merit tinggi (jarang keluar) → depan
                $merit[$k->id_kendaraan]['terakhir'] ?? '',          // terakhir keluar paling lama → depan
                $k->id_kendaraan,
            ])
            ->values();
    }

    /**
     * Kendaraan TERKUNCI = punya perjalanan yang sudah dimulai (jam berangkat ≤ sekarang, WIB)
     * tetapi belum dikonfirmasi selesai (status_perjalanan 'aktif' atau 'selesai_driver').
     * Kendaraan terkunci tidak bisa dibooking / direkomendasikan untuk tanggal & jam mana pun
     * sampai perjalanannya selesai final — mencegah miskomunikasi bila driver terlambat pulang
     * atau belum ada konfirmasi dari pemohon.
     *
     * Kunci terbuka bila: pemohon mengonfirmasi selesai, dispatcher mengubah jadwal menjadi
     * selesai/batal, atau jadwal kedaluwarsa ditutup otomatis oleh `jadwal:reset-status`.
     *
     * @return Collection<int, Jadwal>  key = id_kendaraan
     */
    public function kendaraanTerkunci(): Collection
    {
        $sekarang = now('Asia/Jakarta');

        return Jadwal::with('permintaan.user')
            ->where('status', 'aktif')
            ->whereIn('status_perjalanan', ['aktif', 'selesai_driver'])
            ->whereDate('tanggal', '<=', $sekarang->format('Y-m-d'))
            ->get()
            ->filter(fn(Jadwal $j) => Carbon::parse(
                $j->tanggal->format('Y-m-d') . ' ' . $j->jam_mulai, 'Asia/Jakarta'
            )->lte($sekarang))
            ->keyBy('id_kendaraan');
    }

    /**
     * Konflik jadwal untuk kendaraan (untuk ditampilkan ke admin).
     */
    public function konflik(
        int $kendaraanId,
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?int $kecualiJadwalId = null,
        ?string $tanggalSelesai = null
    ) {
        [$mulai, $selesai] = $this->rentang($tanggal, $jamMulai, $jamSelesai, $tanggalSelesai);

        return $this->jadwalBentrok($mulai, $selesai, $kendaraanId, $kecualiJadwalId)
            ->load('permintaan.user');
    }

    /**
     * Rentang waktu pemakaian [mulai, selesai].
     * Jika jam selesai tidak lebih besar dari jam mulai pada hari yang sama,
     * kendaraan dianggap terpakai sampai akhir hari tersebut.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rentang(
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?string $tanggalSelesai = null
    ): array {
        $tanggal        = Carbon::parse($tanggal)->format('Y-m-d');
        $tanggalSelesai = $tanggalSelesai ? Carbon::parse($tanggalSelesai)->format('Y-m-d') : $tanggal;
        if ($tanggalSelesai < $tanggal) {
            $tanggalSelesai = $tanggal;
        }

        $mulai   = Carbon::parse("{$tanggal} {$jamMulai}");
        $selesai = Carbon::parse("{$tanggalSelesai} {$jamSelesai}");

        if ($selesai->lte($mulai)) {
            $selesai = Carbon::parse($tanggalSelesai)->endOfDay();
        }

        return [$mulai, $selesai];
    }

    /** Rentang waktu pemakaian sebuah jadwal (memperhitungkan tanggal_kembali permintaan). */
    public function rentangJadwal(Jadwal $jadwal): array
    {
        return $this->rentang(
            $jadwal->tanggal->format('Y-m-d'),
            (string) $jadwal->jam_mulai,
            (string) $jadwal->jam_selesai,
            $jadwal->permintaan?->tanggal_kembali?->format('Y-m-d')
        );
    }

    /**
     * Jadwal aktif yang overlap dengan rentang [mulai, selesai].
     * Overlap ↔ mulai_jadwal < selesai AND selesai_jadwal > mulai.
     */
    private function jadwalBentrok(
        Carbon $mulai,
        Carbon $selesai,
        ?int $kendaraanId = null,
        ?int $kecualiJadwalId = null
    ): Collection {
        return Jadwal::with('permintaan')
            ->where('status', 'aktif')
            ->whereDate('tanggal', '<=', $selesai->format('Y-m-d'))
            ->when($kendaraanId, fn($q) => $q->where('id_kendaraan', $kendaraanId))
            ->when($kecualiJadwalId, fn($q) => $q->where('id_jadwal', '!=', $kecualiJadwalId))
            ->get()
            ->filter(function (Jadwal $j) use ($mulai, $selesai) {
                [$jMulai, $jSelesai] = $this->rentangJadwal($j);
                return $jMulai->lt($selesai) && $jSelesai->gt($mulai);
            })
            ->values();
    }
}
