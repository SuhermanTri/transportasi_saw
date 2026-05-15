<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('perbandingan_kriteria', function (Blueprint $table) {
            $table->id('id_perbandingan');
            $table->unsignedBigInteger('kriteria1');
            $table->unsignedBigInteger('kriteria2');
            $table->float('nilai');
            $table->foreign('kriteria1')->references('id_kriteria')->on('kriteria')->onDelete('cascade');
            $table->foreign('kriteria2')->references('id_kriteria')->on('kriteria')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perbandingan_kriteria');
    }
};
