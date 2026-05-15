<?php

namespace App\Services;

use App\Models\User;

class DriverKetersediaanService
{
    /**
     * Cek apakah ada minimal satu driver tersedia pada tanggal dan jam yang diminta.
     *
     * Overlap formula standar: interval [A,B] dan [C,D] overlap jika A < D AND B > C
     *   → jam_mulai_jadwal < jamKembali_request
     *   → jam_selesai_jadwal > jamBerangkat_request
     */
    public function adaDriverTersedia(string $tanggal, string $jamBerangkat, string $jamKembali): bool
    {
        $totalDriver = User::whereHas('role', fn($q) => $q->where('nama_role', 'driver'))
            ->where('status_driver', 'tersedia')
            ->count();

        if ($totalDriver === 0) {
            return false;
        }

        $driverTerpakai = User::whereHas('role', fn($q) => $q->where('nama_role', 'driver'))
            ->where('status_driver', 'tersedia')
            ->whereHas('kendaraan.jadwal', function ($q) use ($tanggal, $jamBerangkat, $jamKembali) {
                $q->whereDate('tanggal', $tanggal)
                  ->where('status', 'aktif')
                  ->where('jam_mulai', '<', $jamKembali)
                  ->where('jam_selesai', '>', $jamBerangkat);
            })
            ->count();

        return $driverTerpakai < $totalDriver;
    }
}
