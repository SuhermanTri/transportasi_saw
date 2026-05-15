<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan', function (Blueprint $table) {
            if (! Schema::hasColumn('permintaan', 'catatan_dispatcher')) {
                $table->text('catatan_dispatcher')->nullable()->after('keterangan');
            }
            if (! Schema::hasColumn('permintaan', 'feedback_type')) {
                $table->enum('feedback_type', ['ditolak', 'dijadwalkan_ulang', 'dioverride'])
                      ->nullable()->after('catatan_dispatcher');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permintaan', function (Blueprint $table) {
            $table->dropColumn(['catatan_dispatcher', 'feedback_type']);
        });
    }
};
