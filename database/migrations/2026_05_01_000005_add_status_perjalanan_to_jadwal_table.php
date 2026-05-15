<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->enum('status_perjalanan', ['aktif', 'selesai_driver', 'selesai_final'])
                  ->default('aktif')
                  ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->dropColumn('status_perjalanan');
        });
    }
};
