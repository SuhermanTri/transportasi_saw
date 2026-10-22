<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanLog extends Model
{
    protected $table    = 'permintaan_logs';
    public $timestamps  = false;

    protected $fillable = [
        'id_permintaan', 'user_id', 'tipe_log',
        'keterangan', 'data_lama', 'data_baru', 'created_at',
    ];

    protected $casts = [
        'data_lama'  => 'array',
        'data_baru'  => 'array',
        'created_at' => 'datetime',
    ];

    public static array $tipeMeta = [
        'batal_user'          => ['color' => 'danger',    'icon' => 'bi-slash-circle',      'label' => 'Dibatalkan Pemohon'],
        'reschedule_user'     => ['color' => 'warning',   'icon' => 'bi-arrow-repeat',      'label' => 'Reschedule Pemohon'],
        'reschedule_admin'    => ['color' => 'info',      'icon' => 'bi-arrow-repeat',      'label' => 'Reschedule Dispatcher'],
        'prioritas_diubah'    => ['color' => 'primary',   'icon' => 'bi-flag-fill',         'label' => 'Prioritas Diubah'],
        'kendaraan_dialihkan' => ['color' => 'warning',   'icon' => 'bi-arrow-left-right',  'label' => 'Kendaraan Dialihkan'],
        'approval'            => ['color' => 'success',   'icon' => 'bi-check-circle-fill', 'label' => 'Disetujui'],
        'tolak'               => ['color' => 'danger',    'icon' => 'bi-x-circle-fill',     'label' => 'Ditolak'],
    ];

    /* ── Relations ───────────────────────────────────────────── */
    public function permintaan()
    {
        return $this->belongsTo(Permintaan::class, 'id_permintaan', 'id_permintaan');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    /* ── Static helper ───────────────────────────────────────── */
    public static function catat(
        int    $idPermintaan,
        int    $userId,
        string $tipe,
        string $keterangan = '',
        ?array $dataLama   = null,
        ?array $dataBaru   = null
    ): self {
        return static::create([
            'id_permintaan' => $idPermintaan,
            'user_id'       => $userId,
            'tipe_log'      => $tipe,
            'keterangan'    => $keterangan,
            'data_lama'     => $dataLama,
            'data_baru'     => $dataBaru,
            'created_at'    => now(),
        ]);
    }
}
