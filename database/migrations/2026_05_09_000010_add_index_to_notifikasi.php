<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            // Composite index untuk query unread count yang sering dipakai
            $table->index(['user_id', 'status'], 'notifikasi_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->dropIndex('notifikasi_user_status_idx');
        });
    }
};
