<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE permintaan MODIFY COLUMN status ENUM('menunggu','menunggu_konfirmasi','disetujui','terjadwal','berjalan','selesai','ditolak') NOT NULL DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE permintaan MODIFY COLUMN status ENUM('menunggu','disetujui','terjadwal','berjalan','selesai','ditolak') NOT NULL DEFAULT 'menunggu'");
    }
};
