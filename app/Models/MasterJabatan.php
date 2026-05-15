<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterJabatan extends Model
{
    public $timestamps = false;
    protected $table   = 'master_jabatan';

    protected $fillable = ['nama_jabatan', 'nilai_saw', 'keterangan'];

    protected $casts = ['nilai_saw' => 'integer'];

    /** Cocokkan string jabatan bebas ke nilai SAW. Fallback ke 1 jika tidak ditemukan. */
    public static function nilaiUntuk(string $jabatan): int
    {
        if (blank($jabatan)) return 1;

        return static::where('nama_jabatan', 'LIKE', "%{$jabatan}%")
            ->orderByDesc('nilai_saw')
            ->value('nilai_saw') ?? 1;
    }
}
