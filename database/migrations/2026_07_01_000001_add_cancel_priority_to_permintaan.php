<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Kolom baru di permintaan
        Schema::table('permintaan', function (Blueprint $table) {
            $table->enum('priority', ['normal', 'high', 'urgent'])
                  ->default('normal')
                  ->after('status');

            $table->text('alasan_batal')->nullable()->after('catatan_dispatcher');
            $table->timestamp('dibatalkan_pada')->nullable()->after('alasan_batal');
        });

        // 2. Extend status enum — tambah 'dibatalkan_user'
        DB::statement("ALTER TABLE permintaan MODIFY COLUMN status ENUM(
            'menunggu',
            'menunggu_konfirmasi',
            'disetujui',
            'terjadwal',
            'berjalan',
            'selesai',
            'ditolak',
            'dibatalkan_user'
        ) NOT NULL DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        // Kembalikan enum ke versi sebelumnya
        DB::statement("ALTER TABLE permintaan MODIFY COLUMN status ENUM(
            'menunggu',
            'menunggu_konfirmasi',
            'disetujui',
            'terjadwal',
            'berjalan',
            'selesai',
            'ditolak'
        ) NOT NULL DEFAULT 'menunggu'");

        Schema::table('permintaan', function (Blueprint $table) {
            $table->dropColumn(['priority', 'alasan_batal', 'dibatalkan_pada']);
        });
    }
};
