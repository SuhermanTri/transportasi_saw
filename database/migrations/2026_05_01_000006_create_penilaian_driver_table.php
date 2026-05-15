<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penilaian_driver', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('id_jadwal');
            $table->tinyInteger('nilai'); // 1–5
            $table->text('komentar')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('driver_id')->references('id_user')->on('users')->cascadeOnDelete();
            $table->foreign('user_id')->references('id_user')->on('users')->cascadeOnDelete();
            $table->foreign('id_jadwal')->references('id_jadwal')->on('jadwal')->cascadeOnDelete();
            $table->unique('id_jadwal'); // satu jadwal satu penilaian
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_driver');
    }
};
