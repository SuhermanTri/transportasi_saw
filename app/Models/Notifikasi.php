<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Notifikasi extends Model
{
    protected $table      = 'notifikasi';
    public    $timestamps = false;

    protected $fillable = ['user_id', 'role', 'pesan', 'url', 'status'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    /* ── Write helpers ─────────────────────────────────────── */

    /** Kirim notifikasi ke satu user. Gagal-diam agar tidak abort operasi utama. */
    public static function kirim(int $userId, string $pesan, ?string $url = null): void
    {
        try {
            static::create([
                'user_id' => $userId,
                'pesan'   => $pesan,
                'url'     => $url,
                'status'  => 'unread',
            ]);
        } catch (\Throwable $e) {
            Log::warning("Notifikasi gagal dikirim ke user {$userId}: " . $e->getMessage());
        }
    }

    /** Kirim notifikasi ke semua user dengan role tertentu. */
    public static function kirimKeRole(string $roleName, string $pesan, ?string $url = null): void
    {
        User::whereHas('role', fn($q) => $q->where('nama_role', $roleName))
            ->each(fn($u) => static::kirim($u->id_user, $pesan, $url));
    }

    /** Shortcut ke dispatcher (role = 'dispatcher'). */
    public static function kirimKeDispatcher(string $pesan, ?string $url = null): void
    {
        static::kirimKeRole('dispatcher', $pesan, $url);
    }

    /* ── Read helpers ──────────────────────────────────────── */

    public static function unreadCount(int $userId): int
    {
        return static::where('user_id', $userId)->where('status', 'unread')->count();
    }
}
