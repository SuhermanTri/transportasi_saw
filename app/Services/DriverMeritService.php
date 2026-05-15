<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class DriverMeritService
{
    public const POIN_SELESAI     = 5;
    public const POIN_TERLAMBAT   = -3;
    public const POIN_DIBATALKAN  = -2;
    public const POIN_MIN         = 0;
    public const POIN_MAX         = 200;

    public function tambahPoinSelesai(User $driver): void
    {
        $this->ubahPoin($driver, self::POIN_SELESAI);
        $driver->last_trip_at = Carbon::now();
        $driver->save();
    }

    public function kurangiPoinTerlambat(User $driver): void
    {
        $this->ubahPoin($driver, self::POIN_TERLAMBAT);
    }

    public function kurangiPoinDibatalkan(User $driver): void
    {
        $this->ubahPoin($driver, self::POIN_DIBATALKAN);
    }

    private function ubahPoin(User $driver, int $delta): void
    {
        $baru = max(self::POIN_MIN, min(self::POIN_MAX, ($driver->merit_point ?? 100) + $delta));
        $driver->merit_point = $baru;
        $driver->save();
    }
}
