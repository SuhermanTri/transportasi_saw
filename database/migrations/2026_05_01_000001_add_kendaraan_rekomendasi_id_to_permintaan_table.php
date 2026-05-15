<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan', function (Blueprint $table) {
            $table->unsignedBigInteger('kendaraan_rekomendasi_id')->nullable()->after('status');
            $table->foreign('kendaraan_rekomendasi_id')
                  ->references('id_kendaraan')
                  ->on('kendaraan')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('permintaan', function (Blueprint $table) {
            $table->dropForeign(['kendaraan_rekomendasi_id']);
            $table->dropColumn('kendaraan_rekomendasi_id');
        });
    }
};
