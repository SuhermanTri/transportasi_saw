<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permintaan', function (Blueprint $table) {
            $table->time('jam_berangkat')->nullable()->after('tanggal_berangkat');
            $table->time('jam_kembali')->nullable()->after('tanggal_kembali');
        });
    }

    public function down(): void
    {
        Schema::table('permintaan', function (Blueprint $table) {
            $table->dropColumn(['jam_berangkat', 'jam_kembali']);
        });
    }
};
