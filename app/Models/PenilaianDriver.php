<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenilaianDriver extends Model
{
    protected $table = 'penilaian_driver';
    public $timestamps = false;

    protected $fillable = ['driver_id', 'user_id', 'id_jadwal', 'nilai', 'komentar'];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id', 'id_user');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'id_jadwal', 'id_jadwal');
    }
}
