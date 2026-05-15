<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerbandinganKriteria extends Model
{
    protected $table = 'perbandingan_kriteria';
    protected $primaryKey = 'id_perbandingan';
    public $timestamps = false;

    protected $fillable = ['kriteria1', 'kriteria2', 'nilai'];

    public function kriteriaA()
    {
        return $this->belongsTo(Kriteria::class, 'kriteria1', 'id_kriteria');
    }

    public function kriteriaB()
    {
        return $this->belongsTo(Kriteria::class, 'kriteria2', 'id_kriteria');
    }
}
