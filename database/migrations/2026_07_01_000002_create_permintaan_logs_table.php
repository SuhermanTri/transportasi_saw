<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permintaan_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_permintaan');
            $table->unsignedBigInteger('user_id');
            // tipe_log: batal_user | reschedule_user | reschedule_admin |
            //           prioritas_diubah | kendaraan_dialihkan | approval | tolak
            $table->string('tipe_log', 50);
            $table->text('keterangan')->nullable();
            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_permintaan')
                  ->references('id_permintaan')->on('permintaan')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id_user')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_logs');
    }
};
