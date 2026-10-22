<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $table      = 'users';
    protected $primaryKey = 'id_user';
    public $timestamps    = false;

    protected $fillable = [
        'nama', 'username', 'password', 'id_role', 'jabatan',
        'status_driver', 'jumlah_perjalanan',
        'merit_point', 'last_trip_at',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'merit_point' => 'integer',
            'last_trip_at'=> 'datetime',
        ];
    }

    /* ── Relations ──────────────────────────────────────────── */
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function permintaan()
    {
        return $this->hasMany(Permintaan::class, 'id_user', 'id_user');
    }

    public function kendaraan()
    {
        return $this->hasOne(Kendaraan::class, 'driver_user_id', 'id_user');
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class, 'user_id', 'id_user');
    }

    public function penilaianSebagaiDriver()
    {
        return $this->hasMany(PenilaianDriver::class, 'driver_id', 'id_user');
    }

    /* ── Role helpers ────────────────────────────────────────── */
    public function isAdmin(): bool  { return $this->id_role == 2; }
    public function isUser(): bool   { return $this->id_role == 1; }
    public function isDriver(): bool { return $this->id_role == 3; }

    /* ── Merit ───────────────────────────────────────────────── */
    public function nilaiJabatan(): int
    {
        return MasterJabatan::nilaiUntuk((string) $this->jabatan);
    }
}
