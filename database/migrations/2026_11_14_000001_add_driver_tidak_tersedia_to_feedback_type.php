<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah jenis feedback otomatis 'driver_tidak_tersedia'.
     * Hanya menambah nilai enum — data lama tidak berubah.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE permintaan MODIFY COLUMN feedback_type
            ENUM('ditolak','dijadwalkan_ulang','dioverride','driver_tidak_tersedia') NULL");
    }

    public function down(): void
    {
        // Pertahankan keterangannya dengan memetakan ke jenis lama sebelum enum dikembalikan
        DB::table('permintaan')
            ->where('feedback_type', 'driver_tidak_tersedia')
            ->update(['feedback_type' => 'dijadwalkan_ulang']);

        DB::statement("ALTER TABLE permintaan MODIFY COLUMN feedback_type
            ENUM('ditolak','dijadwalkan_ulang','dioverride') NULL");
    }
};
