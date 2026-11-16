<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur "Mulai Perjalanan":
     * - status_perjalanan: 'aktif' = belum berangkat, 'berjalan' = sedang dalam perjalanan.
     * - waktu_berangkat: jam driver menekan "Mulai Perjalanan" (WIB).
     * Hanya menambah nilai enum & kolom — data lama tidak berubah.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE jadwal MODIFY COLUMN status_perjalanan
            ENUM('aktif','berjalan','selesai_driver','selesai_final') NOT NULL DEFAULT 'aktif'");

        if (! Schema::hasColumn('jadwal', 'waktu_berangkat')) {
            DB::statement("ALTER TABLE jadwal ADD COLUMN waktu_berangkat TIMESTAMP NULL DEFAULT NULL AFTER status_perjalanan");
        }
    }

    public function down(): void
    {
        // Petakan 'berjalan' → 'aktif' dulu agar enum lama bisa dipasang tanpa error/data hilang
        DB::table('jadwal')->where('status_perjalanan', 'berjalan')->update(['status_perjalanan' => 'aktif']);

        DB::statement("ALTER TABLE jadwal MODIFY COLUMN status_perjalanan
            ENUM('aktif','selesai_driver','selesai_final') NOT NULL DEFAULT 'aktif'");

        if (Schema::hasColumn('jadwal', 'waktu_berangkat')) {
            DB::statement("ALTER TABLE jadwal DROP COLUMN waktu_berangkat");
        }
    }
};
