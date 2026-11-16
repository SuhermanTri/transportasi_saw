<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $table = 'jadwal';
    protected $primaryKey = 'id_jadwal';
    public $timestamps = false;

    protected $fillable = [
        'id_permintaan', 'id_kendaraan', 'tanggal',
        'jam_mulai', 'jam_selesai', 'status', 'status_perjalanan',
        'waktu_berangkat',
    ];

    protected $casts = [
        'tanggal'         => 'date',
        'waktu_berangkat' => 'datetime',
    ];

    /** Jam perjalanan diisi dalam WIB. */
    public const ZONA_WAKTU = 'Asia/Jakarta';

    /** Pesan penolakan seragam bila jadwal diubah setelah perjalanan dimulai. */
    public const PESAN_SUDAH_BERANGKAT = 'Perjalanan sudah dimulai, jadwal tidak dapat diubah.';

    /* ── Status perjalanan helpers ───────────────────────────── */

    /** Jadwal berangkat (tanggal + jam_mulai) dalam WIB. */
    public function jadwalBerangkat(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::parse(
            $this->tanggal->format('Y-m-d') . ' ' . $this->jam_mulai, self::ZONA_WAKTU
        );
    }

    /** Belum berangkat — masih boleh dibatalkan / di-reschedule / dialihkan. */
    public function belumBerangkat(): bool
    {
        return $this->status_perjalanan === 'aktif';
    }

    /** Masih 'aktif' (belum berangkat) padahal jam berangkat sudah lewat (WIB). */
    public function terlambatBerangkat(): bool
    {
        return $this->status === 'aktif'
            && $this->status_perjalanan === 'aktif'
            && $this->jadwalBerangkat()->lt(now(self::ZONA_WAKTU));
    }

    /**
     * Label badge status perjalanan untuk monitor dispatcher & user.
     * @return array{label:string, warna:string, ikon:string}
     */
    public function infoPerjalanan(): array
    {
        return match ($this->status_perjalanan) {
            'berjalan'       => [
                'label' => 'Sedang Dalam Perjalanan' . ($this->waktu_berangkat ? ' sejak ' . $this->waktu_berangkat->format('H:i') : ''),
                'warna' => 'primary', 'ikon' => 'bi-car-front-fill',
            ],
            'selesai_driver' => ['label' => 'Menunggu Konfirmasi Pemohon', 'warna' => 'warning text-dark', 'ikon' => 'bi-hourglass-split'],
            'selesai_final'  => ['label' => 'Selesai', 'warna' => 'success', 'ikon' => 'bi-check-circle-fill'],
            default          => ['label' => 'Belum Berangkat', 'warna' => 'secondary', 'ikon' => 'bi-clock'],
        };
    }

    /**
     * Accessor $jadwal->label_status_perjalanan — label & warna badge seragam untuk semua view:
     * Belum Berangkat / Terlambat Berangkat / Sedang Dalam Perjalanan (sejak HH:MM) /
     * Menunggu Konfirmasi Pemohon / Selesai.
     * @return array{label:string, warna:string, ikon:string}
     */
    public function getLabelStatusPerjalananAttribute(): array
    {
        if ($this->terlambatBerangkat()) {
            return [
                'label' => 'Terlambat Berangkat (jadwal ' . substr((string) $this->jam_mulai, 0, 5) . ')',
                'warna' => 'danger', 'ikon' => 'bi-exclamation-triangle-fill',
            ];
        }

        return $this->infoPerjalanan();
    }

    /** Setiap jadwal dibuat / diubah / dihapus → merit semua driver disinkronkan otomatis. */
    protected static function booted(): void
    {
        static::saved(fn() => \App\Services\DriverMeritService::sinkronOtomatis());
        static::deleted(fn() => \App\Services\DriverMeritService::sinkronOtomatis());
    }

    public function permintaan()
    {
        return $this->belongsTo(Permintaan::class, 'id_permintaan', 'id_permintaan');
    }

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan', 'id_kendaraan');
    }

    public function penilaian()
    {
        return $this->hasOne(PenilaianDriver::class, 'id_jadwal', 'id_jadwal');
    }
}
