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
        'foto_profil', 'no_hp',
    ];

    /* ── Profil helpers ──────────────────────────────────────── */

    /**
     * URL foto profil (disk 'public') atau null bila belum ada.
     * Dibuat relatif ("/storage/...") agar tetap benar walau APP_URL berbeda
     * dengan domain yang dipakai (mis. transportasi_saw.test).
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (! $this->foto_profil || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($this->foto_profil)) {
            return null;
        }

        return parse_url(\Illuminate\Support\Facades\Storage::disk('public')->url($this->foto_profil), PHP_URL_PATH);
    }

    /** Inisial nama, maksimal 2 huruf (mis. "Budi Santoso" → "BS"). */
    public function getInisialAttribute(): string
    {
        $kata = preg_split('/\s+/', trim((string) $this->nama), -1, PREG_SPLIT_NO_EMPTY);
        $inisial = collect($kata)->take(2)->map(fn($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');

        return $inisial !== '' ? $inisial : '?';
    }

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
