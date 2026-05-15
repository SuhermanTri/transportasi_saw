<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_user_id')->nullable()->after('nama_driver');
            $table->foreign('driver_user_id')
                  ->references('id_user')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->dropForeign(['driver_user_id']);
            $table->dropColumn('driver_user_id');
        });
    }
};
