<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kendaraan', function (Blueprint $table) {
            $table->id('id_kendaraan');
            $table->string('nama_kendaraan', 50);
            $table->string('no_polisi', 15);
            $table->string('nama_driver', 100)->nullable();
            $table->string('no_hp_driver', 15)->nullable();
            $table->integer('kapasitas');
            $table->enum('status', ['tersedia', 'dipakai'])->default('tersedia');
            $table->smallInteger('tahun');
            $table->string('warna', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kendaraan');
    }
};
