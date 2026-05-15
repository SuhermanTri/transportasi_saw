<?php

namespace App\Console\Commands;

use App\Models\Jadwal;
use App\Models\Kendaraan;
use Illuminate\Console\Command;

class ResetStatusJadwal extends Command
{
    protected $signature   = 'jadwal:reset-status';
    protected $description = 'Bebaskan kendaraan dan selesaikan jadwal yang sudah lewat tanggalnya';

    public function handle(): void
    {
        $expired = Jadwal::where('status', 'aktif')
            ->whereDate('tanggal', '<', today())
            ->get();

        foreach ($expired as $jadwal) {
            Kendaraan::where('id_kendaraan', $jadwal->id_kendaraan)
                ->where('status', 'dipakai')
                ->update(['status' => 'tersedia']);

            $newPerjalanan = $jadwal->status_perjalanan === 'selesai_driver'
                ? 'selesai_final'
                : 'selesai_final';

            $jadwal->update([
                'status'            => 'selesai',
                'status_perjalanan' => $newPerjalanan,
            ]);

            $jadwal->permintaan?->update(['status' => 'selesai']);
        }

        $this->info("Reset {$expired->count()} jadwal kadaluarsa.");
    }
}
