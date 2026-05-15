<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permintaan extends Model
{
    protected $table      = 'permintaan';
    protected $primaryKey = 'id_permintaan';
    public $timestamps    = false;

    protected $fillable = [
        'id_user', 'urgensi_id',
        'tujuan',
        'tanggal_berangkat', 'jam_berangkat',
        'tanggal_kembali',   'jam_kembali',
        'jumlah_penumpang',  'keterangan',
        'status', 'created_at',
        'catatan_dispatcher', 'feedback_type',
    ];

    protected $casts = [
        'tanggal_berangkat' => 'date',
        'tanggal_kembali'   => 'date',
        'created_at'        => 'datetime',
    ];

    /* ── Dispatcher feedback helpers ─────────────────────────── */
    public static array $feedbackMeta = [
        'ditolak'           => ['color' => 'danger',  'icon' => 'bi-x-circle-fill',  'label' => 'Ditolak'],
        'dijadwalkan_ulang' => ['color' => 'warning', 'icon' => 'bi-arrow-repeat',   'label' => 'Dijadwalkan Ulang'],
        'dioverride'        => ['color' => 'warning', 'icon' => 'bi-arrow-left-right','label' => 'Dialihkan'],
    ];

    public function getFeedbackLabelAttribute(): string
    {
        $meta = self::$feedbackMeta[$this->feedback_type] ?? ['color' => 'secondary', 'icon' => 'bi-info-circle', 'label' => 'Feedback'];
        return "<span class=\"badge bg-{$meta['color']}\"><i class=\"bi {$meta['icon']} me-1\"></i>{$meta['label']}</span>";
    }

    /* ── Status label helpers ─────────────────────────────────── */
    public static array $statusLabels = [
        'menunggu'             => ['badge' => 'bg-warning text-dark', 'icon' => 'bi-hourglass-split',  'text' => 'Menunggu'],
        'menunggu_konfirmasi'  => ['badge' => 'bg-secondary',         'icon' => 'bi-clock-history',    'text' => 'Menunggu Kendaraan'],
        'disetujui'            => ['badge' => 'bg-success',           'icon' => 'bi-check-circle-fill','text' => 'Disetujui'],
        'terjadwal'            => ['badge' => 'bg-info text-dark',    'icon' => 'bi-calendar-check',   'text' => 'Terjadwal'],
        'berjalan'             => ['badge' => 'bg-primary',           'icon' => 'bi-car-front-fill',   'text' => 'Berjalan'],
        'selesai'              => ['badge' => 'bg-secondary',         'icon' => 'bi-flag-fill',        'text' => 'Selesai'],
        'ditolak'              => ['badge' => 'bg-danger',            'icon' => 'bi-x-circle-fill',    'text' => 'Ditolak'],
    ];

    public function getStatusLabelAttribute(): string
    {
        $s = self::$statusLabels[$this->status] ?? ['badge' => 'bg-secondary', 'icon' => 'bi-question', 'text' => ucfirst($this->status)];
        return "<span class=\"badge {$s['badge']}\"><i class=\"bi {$s['icon']} me-1\"></i>{$s['text']}</span>";
    }

    /* ── Relations ────────────────────────────────────────────── */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function urgensi()
    {
        return $this->belongsTo(MasterUrgensi::class, 'urgensi_id', 'id');
    }

    public function jadwal()
    {
        return $this->hasOne(Jadwal::class, 'id_permintaan', 'id_permintaan');
    }

    public function hasilSaw()
    {
        return $this->hasOne(HasilSaw::class, 'id_permintaan', 'id_permintaan');
    }
}
