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
     */
    public function kendaraanTersedia(
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?string $tipeGage = null,
        ?string $tanggalSelesai = null,
        array $reservasi = []
    ) {
        [$mulai, $selesai] = $this->rentang($tanggal, $jamMulai, $jamSelesai, $tanggalSelesai);

        $terpakai = $this->jadwalBentrok($mulai, $selesai)
            ->pluck('id_kendaraan')
            ->merge(
                collect($reservasi)
                    ->filter(fn($r) => $r['mulai']->lt($selesai) && $r['selesai']->gt($mulai))
                    ->pluck('id_kendaraan')
            )
            ->unique()
            ->values()
            ->all();

        return Kendaraan::query()
            ->where(fn($q) => $q->where('status', 'tersedia')
                ->orWhereHas('jadwal', fn($j) => $j->where('status', 'aktif')))
            ->when(
                $tipeGage !== null,
                fn($q) => $q->where(fn($inner) =>
                    $inner->where('tipe_gage', 'bebas')->orWhere('tipe_gage', $tipeGage)
                )
            )
            ->whereNotIn('id_kendaraan', $terpakai)
            ->orderBy('id_kendaraan')
            ->get();
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
