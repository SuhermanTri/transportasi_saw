<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hasil_saw', function (Blueprint $table) {
            $table->id('id_hasil');
            $table->unsignedBigInteger('id_kendaraan');
            $table->float('nilai_preferensi');
            $table->integer('ranking');
            $table->foreign('id_kendaraan')->references('id_kendaraan')->on('kendaraan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_saw');
    }
};
