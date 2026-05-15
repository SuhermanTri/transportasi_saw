<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permintaan', function (Blueprint $table) {
            $table->id('id_permintaan');
            $table->unsignedBigInteger('id_user');
            $table->string('tujuan', 150);
            $table->date('tanggal_berangkat');
            $table->date('tanggal_kembali')->nullable();
            $table->integer('jumlah_penumpang');
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->integer('jarak');
            $table->string('keterangan', 255)->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'selesai'])->default('menunggu');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan');
    }
};
