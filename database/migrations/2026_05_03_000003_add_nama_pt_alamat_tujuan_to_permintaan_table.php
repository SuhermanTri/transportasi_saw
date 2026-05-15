<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE permintaan ADD COLUMN nama_pt VARCHAR(150) NULL DEFAULT NULL AFTER tujuan");
        DB::statement("ALTER TABLE permintaan ADD COLUMN alamat_tujuan VARCHAR(300) NULL DEFAULT NULL AFTER nama_pt");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE permintaan DROP COLUMN alamat_tujuan");
        DB::statement("ALTER TABLE permintaan DROP COLUMN nama_pt");
    }
};
