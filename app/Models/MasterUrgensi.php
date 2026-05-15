<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterUrgensi extends Model
{
    public $timestamps = false;
    protected $table   = 'master_urgensi';

    protected $fillable = ['nama_urgensi', 'nilai_saw', 'keterangan'];

    protected $casts = ['nilai_saw' => 'integer'];

    public function permintaan()
    {
        return $this->hasMany(Permintaan::class, 'urgensi_id', 'id');
    }
}
