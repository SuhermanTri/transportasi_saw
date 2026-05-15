<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kendaraan extends Model
{
    protected $table      = 'kendaraan';
    protected $primaryKey = 'id_kendaraan';
    public $timestamps    = false;

    protected $fillable = [
        'nama_kendaraan', 'no_polisi', 'nama_driver', 'no_hp_driver',
        'kapasitas', 'status', 'tahun', 'warna', 'driver_user_id',
        'tipe_gage', 'jam_mulai_operasional', 'jam_selesai_operasional',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $k) {
            $k->tipe_gage = self::gageFromNomorPolisi($k->no_polisi ?? '');
        });

        static::updating(function (self $k) {
            if ($k->isDirty('no_polisi')) {
                $k->tipe_gage = self::gageFromNomorPolisi($k->no_polisi);
            }
        });
    }

    /** Deteksi tipe gage dari digit angka terakhir nomor polisi. */
    public static function gageFromNomorPolisi(string $noPol): string
    {
        preg_match_all('/\d+/', $noPol, $m);
        if (empty($m[0])) {
            return 'bebas';
        }
        $lastDigit = (int) substr(implode('', $m[0]), -1);
        return ($lastDigit % 2 !== 0) ? 'ganjil' : 'genap';
    }

    public function driverUser()
    {
        return $this->belongsTo(User::class, 'driver_user_id', 'id_user');
    }

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class, 'id_kendaraan', 'id_kendaraan');
    }

    public function penjadwalan()
    {
        return $this->hasMany(Jadwal::class, 'id_kendaraan', 'id_kendaraan');
    }

    /** Apakah kendaraan sesuai aturan gage untuk tanggal tertentu? */
    public function isSesuaiGage(\DateTimeInterface|string $tanggal): bool
    {
        if ($this->tipe_gage === 'bebas') return true;

        $hari = (int) (is_string($tanggal) ? date('j', strtotime($tanggal)) : $tanggal->format('j'));
        $ganjilHari = ($hari % 2 !== 0);

        return $ganjilHari
            ? $this->tipe_gage === 'ganjil'
            : $this->tipe_gage === 'genap';
    }

    /**
     * Skor gage untuk SAW.
     * Jika gage tidak aktif (di luar jam gage) → 1.0 untuk semua kendaraan.
     * Jika gage aktif → 1.0 sesuai, 0.5 bebas, 0.0 tidak sesuai.
     */
    public function skorGage(\DateTimeInterface|string $tanggal, bool $gageAktif = true): float
    {
        if (! $gageAktif) return 1.0;
        if ($this->tipe_gage === 'bebas') return 0.5;
        return $this->isSesuaiGage($tanggal) ? 1.0 : 0.0;
    }
}
