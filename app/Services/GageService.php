<?php

namespace App\Services;

use App\Models\Kendaraan;
use Illuminate\Support\Carbon;

class GageService
{
    /**
     * Aturan ganjil-genap hanya berlaku pada jam-jam ini.
     * Di luar jendela ini semua kendaraan boleh digunakan.
     */
    private const JAM_GAGE = [
        ['mulai' => '06:00', 'selesai' => '10:00'],
        ['mulai' => '16:00', 'selesai' => '21:00'],
    ];

    /** Tipe gage yang sesuai untuk tanggal tertentu (ganjil / genap). */
    public function tipeUntukTanggal(Carbon|string $tanggal): string
    {
        $hari = (int) Carbon::parse($tanggal)->format('j');
        return ($hari % 2 !== 0) ? 'ganjil' : 'genap';
    }

    /**
     * Apakah aturan gage AKTIF untuk rentang jam perjalanan ini?
     *
     * Gage aktif jika trip OVERLAP dengan salah satu jendela jam:
     *   overlap ↔ jam_mulai < window_selesai  AND  jam_selesai > window_mulai
     */
    public function isGageAktif(string $jamMulai, string $jamSelesai): bool
    {
        foreach (self::JAM_GAGE as $w) {
            if ($jamMulai < $w['selesai'] && $jamSelesai > $w['mulai']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Apakah kendaraan sesuai aturan gage untuk tanggal + jam perjalanan?
     * Di luar jam gage → semua kendaraan dianggap sesuai (return true).
     */
    public function sesuai(
        Kendaraan $kendaraan,
        Carbon|string $tanggal,
        string $jamMulai  = '08:00',
        string $jamSelesai = '17:00'
    ): bool {
        if (! $this->isGageAktif($jamMulai, $jamSelesai)) {
            return true;
        }
        return $kendaraan->isSesuaiGage($tanggal);
    }

    /**
     * Skor gage untuk SAW (dipakai oleh SawService).
     * Di luar jam gage → 1.0 (semua kendaraan valid → skor maksimal).
     * Di dalam jam gage → rasio kendaraan sesuai / total kendaraan.
     */
    public function skorGagePermintaan(
        Carbon|string $tanggal,
        string $jamMulai  = '08:00',
        string $jamSelesai = '17:00'
    ): float {
        if (! $this->isGageAktif($jamMulai, $jamSelesai)) {
            return 1.0;
        }

        $total = Kendaraan::count();
        if ($total === 0) {
            return 0.0;
        }

        $tipe   = $this->tipeUntukTanggal($tanggal);
        $sesuai = Kendaraan::where(fn($q) => $q->where('tipe_gage', 'bebas')
                                               ->orWhere('tipe_gage', $tipe))->count();

        return round($sesuai / $total, 4);
    }

    /**
     * Ambil kendaraan yang sesuai aturan gage untuk tanggal + jam.
     * Di luar jam gage → semua kendaraan dikembalikan.
     */
    public function kendaraanSesuai(
        Carbon|string $tanggal,
        string $jamMulai  = '08:00',
        string $jamSelesai = '17:00'
    ) {
        if (! $this->isGageAktif($jamMulai, $jamSelesai)) {
            return Kendaraan::all();
        }

        $tipe = $this->tipeUntukTanggal($tanggal);
        return Kendaraan::where(fn($q) => $q->where('tipe_gage', 'bebas')
                                            ->orWhere('tipe_gage', $tipe))->get();
    }
}
