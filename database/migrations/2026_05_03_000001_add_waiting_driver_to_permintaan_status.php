<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE permintaan MODIFY COLUMN status ENUM('menunggu','disetujui','ditolak','selesai','waiting_driver') NOT NULL DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE permintaan MODIFY COLUMN status ENUM('menunggu','disetujui','ditolak','selesai') NOT NULL DEFAULT 'menunggu'");
    }
};
