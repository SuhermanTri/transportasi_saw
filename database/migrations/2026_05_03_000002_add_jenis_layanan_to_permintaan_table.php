<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE permintaan ADD COLUMN jenis_layanan ENUM('ditunggu','didrop') NULL DEFAULT NULL AFTER keterangan");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE permintaan DROP COLUMN jenis_layanan");
    }
};
