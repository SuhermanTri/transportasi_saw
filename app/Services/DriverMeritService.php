<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Merit driver untuk pemerataan tugas (rotasi).
 *
 * Semakin sering driver keluar dalam periode terakhir, semakin kecil meritnya,
 * sehingga driver yang jarang keluar diutamakan untuk penugasan berikutnya.
 *
 *   Merit = 100 − 10 × (jumlah perjalanan dalam 7 hari terakhir s.d. tanggal tugas)
 *   (minimal 0; jadwal yang sudah terjadwal di tanggal tugas ikut dihitung)
 *
 * Merit hanya dipakai untuk MEMILIH driver/kendaraan di antara yang tersedia.
 * Bukan kriteria SAW — urutan prioritas permintaan tetap murni AHP-SAW.
 */
class DriverMeritService
{
    public const PERIODE_HARI        = 7;
    public const POIN_AWAL           = 100;
    public const POIN_PER_PERJALANAN = 10;

    /**
     * Merit setiap kendaraan (= driver-nya) untuk tanggal tugas tertentu.
     *
     * @param  int[]  $idKendaraan
     * @return array<int, array{merit:int, perjalanan:int, terakhir:?string}>  key = id_kendaraan
     */
    public function untukKendaraan(array $idKendaraan, Carbon|string $tanggal): array
    {
        $tgl  = Carbon::parse($tanggal)->startOfDay();
        $awal = $tgl->copy()->subDays(self::PERIODE_HARI - 1);

        $rekap = Jadwal::whereIn('id_kendaraan', $idKendaraan)
            ->whereIn('status', ['aktif', 'selesai'])
            ->whereDate('tanggal', '>=', $awal->format('Y-m-d'))
            ->whereDate('tanggal', '<=', $tgl->format('Y-m-d'))
            ->get(['id_kendaraan', 'tanggal', 'jam_mulai'])
            ->groupBy('id_kendaraan');

        $hasil = [];
        foreach ($idKendaraan as $id) {
            $jadwal     = $rekap->get($id, collect());
            $perjalanan = $jadwal->count();
            $terakhir   = $jadwal->map(fn($j) => $j->tanggal->format('Y-m-d') . ' ' . $j->jam_mulai)->max();

            $hasil[$id] = [
                'merit'      => max(0, self::POIN_AWAL - self::POIN_PER_PERJALANAN * $perjalanan),
                'perjalanan' => $perjalanan,
                'terakhir'   => $terakhir,
            ];
        }

        return $hasil;
    }

    /** Merit seorang driver (lewat kendaraannya) untuk tanggal tertentu — dipakai di dashboard driver. */
    public function untukDriver(User $driver, Carbon|string|null $tanggal = null): ?array
    {
        $kendaraan = Kendaraan::where('driver_user_id', $driver->id_user)->first();
        if (! $kendaraan) {
            return null;
        }

        return $this->untukKendaraan([$kendaraan->id_kendaraan], $tanggal ?? today())[$kendaraan->id_kendaraan];
    }

    /**
     * Simpan merit terkini (basis hari ini) ke kolom users.merit_point & users.last_trip_at.
     * Kolom ini sudah ada di database; nilainya dipakai untuk tampilan/laporan.
     */
    public function sinkron(): void
    {
        // Semua akun driver (role 3), termasuk yang belum punya kendaraan
        $drivers   = User::where('id_role', 3)->get(['id_user', 'merit_point', 'last_trip_at']);
        $kendaraan = Kendaraan::whereIn('driver_user_id', $drivers->pluck('id_user'))
            ->get(['id_kendaraan', 'driver_user_id'])
            ->keyBy('driver_user_id');

        $merit = $kendaraan->isEmpty()
            ? []
            : $this->untukKendaraan($kendaraan->pluck('id_kendaraan')->all(), today());

        foreach ($drivers as $d) {
            $k = $kendaraan->get($d->id_user);

            $terakhirSelesai = $k
                ? Jadwal::where('id_kendaraan', $k->id_kendaraan)
                    ->where('status', 'selesai')
                    ->orderByDesc('tanggal')->orderByDesc('jam_mulai')
                    ->first(['tanggal', 'jam_mulai'])
                : null;

            $baru = [
                // Driver tanpa kendaraan belum punya perjalanan → merit penuh
                'merit_point'  => $k ? $merit[$k->id_kendaraan]['merit'] : self::POIN_AWAL,
                'last_trip_at' => $terakhirSelesai
                    ? Carbon::parse($terakhirSelesai->tanggal->format('Y-m-d') . ' ' . $terakhirSelesai->jam_mulai)
                    : null,
            ];

            // Tulis hanya jika berubah
            $lama = $d->last_trip_at ? Carbon::parse($d->last_trip_at)->format('Y-m-d H:i:s') : null;
            $nilaiBaru = $baru['last_trip_at']?->format('Y-m-d H:i:s');
            if ((int) $d->merit_point !== $baru['merit_point'] || $lama !== $nilaiBaru) {
                User::where('id_user', $d->id_user)->update($baru);
            }
        }
    }

    /**
     * Dipanggil otomatis oleh event model (Jadwal / Kendaraan) agar kolom merit
     * selalu sinkron dengan data jadwal, dari jalur mana pun perubahan terjadi.
     * Kegagalan tidak boleh menggagalkan proses utama.
     */
    public static function sinkronOtomatis(): void
    {
        try {
            app(self::class)->sinkron();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Sinkron merit gagal: ' . $e->getMessage());
        }
    }
}
