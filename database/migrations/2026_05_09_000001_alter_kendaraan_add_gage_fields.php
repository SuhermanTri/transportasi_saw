<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE kendaraan ADD COLUMN tipe_gage ENUM('ganjil','genap','bebas') NOT NULL DEFAULT 'bebas' AFTER warna");
        DB::statement("ALTER TABLE kendaraan ADD COLUMN jam_mulai_operasional TIME NULL DEFAULT NULL AFTER tipe_gage");
        DB::statement("ALTER TABLE kendaraan ADD COLUMN jam_selesai_operasional TIME NULL DEFAULT NULL AFTER jam_mulai_operasional");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE kendaraan DROP COLUMN jam_selesai_operasional");
        DB::statement("ALTER TABLE kendaraan DROP COLUMN jam_mulai_operasional");
        DB::statement("ALTER TABLE kendaraan DROP COLUMN tipe_gage");
    }
};
