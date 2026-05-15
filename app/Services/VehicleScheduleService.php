<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Kendaraan;

class VehicleScheduleService
{
    /**
     * Apakah kendaraan tersedia (tidak bentrok) untuk tanggal + jam yang diminta?
     * Jadwal schema: tanggal DATE, jam_mulai TIME, jam_selesai TIME.
     *
     * @param  int       $kendaraanId
     * @param  string    $tanggal    'Y-m-d'
     * @param  string    $jamMulai   'H:i'
     * @param  string    $jamSelesai 'H:i'
     * @param  int|null  $kecualiJadwalId
     */
    public function tersedia(
        int $kendaraanId,
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?int $kecualiJadwalId = null
    ): bool {
        $query = Jadwal::where('id_kendaraan', $kendaraanId)
            ->where('status', 'aktif')
            ->whereDate('tanggal', $tanggal)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai);

        if ($kecualiJadwalId) {
            $query->where('id_jadwal', '!=', $kecualiJadwalId);
        }

        return $query->doesntExist();
    }

    /**
     * Daftar kendaraan yang tersedia (gage + booking) untuk tanggal dan jam tertentu.
     *
     * @param  string|null $tipeGage  'ganjil'|'genap' jika gage aktif, null jika di luar jam gage
     *                                (null = tidak ada filter gage, semua kendaraan boleh)
     */
    public function kendaraanTersedia(
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?string $tipeGage = null
    ) {
        return Kendaraan::where('status', 'tersedia')
            ->when(
                $tipeGage !== null,
                fn($q) => $q->where(fn($inner) =>
                    $inner->where('tipe_gage', 'bebas')->orWhere('tipe_gage', $tipeGage)
                )
            )
            ->whereDoesntHave('jadwal', function ($q) use ($tanggal, $jamMulai, $jamSelesai) {
                $q->where('status', 'aktif')
                  ->whereDate('tanggal', $tanggal)
                  ->where('jam_mulai', '<', $jamSelesai)
                  ->where('jam_selesai', '>', $jamMulai);
            })
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
        ?int $kecualiJadwalId = null
    ) {
        $query = Jadwal::with('permintaan.user')
            ->where('id_kendaraan', $kendaraanId)
            ->where('status', 'aktif')
            ->whereDate('tanggal', $tanggal)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai);

        if ($kecualiJadwalId) {
            $query->where('id_jadwal', '!=', $kecualiJadwalId);
        }

        return $query->get();
    }
}
