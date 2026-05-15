<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilSaw extends Model
{
    protected $table      = 'hasil_saw';
    protected $primaryKey = 'id_hasil';
    public $timestamps    = false;

    protected $fillable = [
        'id_permintaan', 'skor_jabatan', 'skor_urgensi',
        'skor_gage', 'nilai_akhir', 'ranking', 'created_at',
    ];

    protected $casts = [
        'skor_jabatan' => 'float',
        'skor_urgensi' => 'float',
        'skor_gage'    => 'float',
        'nilai_akhir'  => 'float',
        'created_at'   => 'datetime',
    ];

    public function permintaan()
    {
        return $this->belongsTo(Permintaan::class, 'id_permintaan', 'id_permintaan');
    }
}
