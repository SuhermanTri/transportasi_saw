<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermintaanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('permintaan')->insert([
            [
                'id_user'           => 2,
                'tujuan'            => 'Meeting PT A',
                'tanggal_berangkat' => '2026-06-10',
                'tanggal_kembali'   => '2026-06-10',
                'jumlah_penumpang'  => 4,
                'prioritas'         => 'tinggi',
                'jarak'             => 12,
                'keterangan'        => null,
                'status'            => 'menunggu',
                'created_at'        => now(),
            ],
            [
                'id_user'           => 3,
                'tujuan'            => 'Kunjungan Client',
                'tanggal_berangkat' => '2026-06-11',
                'tanggal_kembali'   => '2026-06-11',
                'jumlah_penumpang'  => 5,
                'prioritas'         => 'sedang',
                'jarak'             => 25,
                'keterangan'        => null,
                'status'            => 'menunggu',
                'created_at'        => now(),
            ],
            [
                'id_user'           => 2,
                'tujuan'            => 'Meeting PT XY',
                'tanggal_berangkat' => '2026-06-12',
                'tanggal_kembali'   => '2026-06-12',
                'jumlah_penumpang'  => 3,
                'prioritas'         => 'tinggi',
                'jarak'             => 30,
                'keterangan'        => 'Urgent meeting',
                'status'            => 'disetujui',
                'created_at'        => now(),
            ],
        ]);
    }
}
