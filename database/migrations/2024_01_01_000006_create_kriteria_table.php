<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kriteria', function (Blueprint $table) {
            $table->id('id_kriteria');
            $table->string('nama_kriteria', 50);
            $table->enum('jenis', ['benefit', 'cost']);
            $table->float('bobot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kriteria');
    }
};
