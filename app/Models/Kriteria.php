<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kriteria extends Model
{
    protected $table = 'kriteria';
    protected $primaryKey = 'id_kriteria';
    public $timestamps = false;

    protected $fillable = ['nama_kriteria', 'jenis', 'bobot'];

    public function perbandinganSebagai1()
    {
        return $this->hasMany(PerbandinganKriteria::class, 'kriteria1', 'id_kriteria');
    }

    public function perbandinganSebagai2()
    {
        return $this->hasMany(PerbandinganKriteria::class, 'kriteria2', 'id_kriteria');
    }

    public function alternatifPenilaian()
    {
        return $this->hasMany(AlternatifPenilaian::class, 'id_kriteria', 'id_kriteria');
    }
}
