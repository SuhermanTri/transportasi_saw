<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlternatifPenilaian extends Model
{
    protected $table = 'alternatif_penilaian';
    protected $primaryKey = 'id_penilaian';
    public $timestamps = false;

    protected $fillable = ['id_kendaraan', 'id_kriteria', 'nilai'];

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan', 'id_kendaraan');
    }

    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class, 'id_kriteria', 'id_kriteria');
    }
}
