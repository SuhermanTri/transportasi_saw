<?php

namespace App\Services;

use App\Models\Permintaan;
use App\Models\Notifikasi;

class ApprovalService
{
    public function __construct(
        private SawService $saw,
        private VehicleScheduleService $schedule,
        private GageService $gage,
    ) {}

    /**
     * Setujui permintaan.
     * Jika ada kendaraan tersedia → status 'disetujui' + hitung SAW.
     * Jika tidak ada kendaraan → status 'menunggu_konfirmasi' (dispatcher assign manual).
     */
    public function setujui(Permintaan $permintaan): void
    {
        $tanggal    = $permintaan->tanggal_berangkat->format('Y-m-d');
        $jamMulai   = $permintaan->jam_berangkat ?? '08:00';
        $jamSelesai = $permintaan->jam_kembali   ?? '17:00';

        $gageAktif = $this->gage->isGageAktif($jamMulai, $jamSelesai);
        $tipeGage  = $gageAktif ? $this->gage->tipeUntukTanggal($permintaan->tanggal_berangkat) : null;

        $ada = $this->schedule->kendaraanTersedia($tanggal, $jamMulai, $jamSelesai, $tipeGage)->isNotEmpty();

        if (! $ada) {
            $permintaan->update(['status' => 'menunggu_konfirmasi']);
            $this->kirimNotifikasi($permintaan, 'menunggu_konfirmasi');
            return;
        }

        $permintaan->update(['status' => 'disetujui']);
        $this->saw->hitung();
        $this->kirimNotifikasi($permintaan, 'disetujui');
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
            'menunggu_konfirmasi' => "Permintaan perjalanan Anda ke {$permintaan->tujuan} disetujui namun belum ada kendaraan tersedia. Dispatcher akan menghubungi Anda.",
            'ditolak'             => "Permintaan perjalanan Anda ke {$permintaan->tujuan} ditolak." . ($pesan ? " Alasan: {$pesan}" : ''),
            'dijadwalkan_ulang'   => "Jadwal perjalanan Anda ke {$permintaan->tujuan} diubah oleh Dispatcher." . ($pesan ? " Keterangan: {$pesan}" : ''),
            'terjadwal'           => "Permintaan perjalanan Anda ke {$permintaan->tujuan} telah dijadwalkan.",
            default               => "Status permintaan Anda diperbarui menjadi {$status}.",
        };

        Notifikasi::kirim($permintaan->id_user, $teks);
    }
}
