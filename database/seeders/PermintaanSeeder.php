<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermintaanSeeder extends Seeder
{
    public function run(): void
    {
        // urgensi_id mengacu ke tabel master_urgensi yang sudah di-seed
        DB::table('permintaan')->insert([
            [
                'id_user'           => 2,
                'urgensi_id'        => 3,
                'tujuan'            => 'Meeting PT A',
                'tanggal_berangkat' => today()->format('Y-m-d'),
                'tanggal_kembali'   => today()->format('Y-m-d'),
                'jam_berangkat'     => '09:00',
                'jam_kembali'       => '12:00',
                'jumlah_penumpang'  => 4,
                'keterangan'        => null,
                'status'            => 'menunggu',
                'created_at'        => now(),
            ],
            [
                'id_user'           => 3,
                'urgensi_id'        => 2,
                'tujuan'            => 'Kunjungan Client',
                'tanggal_berangkat' => today()->addDay()->format('Y-m-d'),
                'tanggal_kembali'   => today()->addDay()->format('Y-m-d'),
                'jam_berangkat'     => '13:00',
                'jam_kembali'       => '17:00',
                'jumlah_penumpang'  => 5,
                'keterangan'        => null,
                'status'            => 'menunggu',
                'created_at'        => now(),
            ],
            [
                'id_user'           => 2,
                'urgensi_id'        => 4,
                'tujuan'            => 'Meeting PT XY',
                'tanggal_berangkat' => today()->format('Y-m-d'),
                'tanggal_kembali'   => today()->format('Y-m-d'),
                'jam_berangkat'     => '10:00',
                'jam_kembali'       => '15:00',
                'jumlah_penumpang'  => 3,
                'keterangan'        => 'Urgent meeting',
                'status'            => 'menunggu',
                'created_at'        => now(),
            ],
        ]);
    }
}
