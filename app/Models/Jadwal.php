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
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

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
