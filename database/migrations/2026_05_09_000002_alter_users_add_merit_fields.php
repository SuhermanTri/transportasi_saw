<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE users ADD COLUMN merit_point INT UNSIGNED NOT NULL DEFAULT 100 AFTER jumlah_perjalanan");
        DB::statement("ALTER TABLE users ADD COLUMN last_trip_at TIMESTAMP NULL DEFAULT NULL AFTER merit_point");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users DROP COLUMN last_trip_at");
        DB::statement("ALTER TABLE users DROP COLUMN merit_point");
    }
};
