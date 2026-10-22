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
        // Perjalanan multi-hari baru kadaluarsa setelah tanggal_kembali lewat
        $expired = Jadwal::with('permintaan')
            ->where('status', 'aktif')
            ->whereDate('tanggal', '<', today())
            ->get()
            ->filter(fn($j) => ($j->permintaan?->tanggal_kembali ?? $j->tanggal)->lt(today()));

        foreach ($expired as $jadwal) {
            Kendaraan::where('id_kendaraan', $jadwal->id_kendaraan)
                ->where('status', 'dipakai')
                ->update(['status' => 'tersedia']);

            $jadwal->update([
                'status'            => 'selesai',
                'status_perjalanan' => 'selesai_final',
            ]);

            $jadwal->permintaan?->update(['status' => 'selesai']);
        }

        $this->info("Reset {$expired->count()} jadwal kadaluarsa.");
    }
}
