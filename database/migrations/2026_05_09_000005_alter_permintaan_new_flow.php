<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Tambah kolom urgensi_id (FK ke master_urgensi)
        DB::statement("ALTER TABLE permintaan ADD COLUMN urgensi_id INT UNSIGNED NULL DEFAULT NULL AFTER id_user");

        // 2. Hapus kolom-kolom yang tidak lagi dipakai
        //    Drop FK kendaraan_rekomendasi_id terlebih dahulu (cari nama constraint-nya)
        $fkList = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'permintaan'
              AND COLUMN_NAME  = 'kendaraan_rekomendasi_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        foreach ($fkList as $fk) {
            DB::statement("ALTER TABLE permintaan DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        if (Schema::hasColumn('permintaan', 'kendaraan_rekomendasi_id')) {
            DB::statement("ALTER TABLE permintaan DROP COLUMN kendaraan_rekomendasi_id");
        }
        if (Schema::hasColumn('permintaan', 'jarak')) {
            DB::statement("ALTER TABLE permintaan DROP COLUMN jarak");
        }
        if (Schema::hasColumn('permintaan', 'nama_pt')) {
            DB::statement("ALTER TABLE permintaan DROP COLUMN nama_pt");
        }
        if (Schema::hasColumn('permintaan', 'alamat_tujuan')) {
            DB::statement("ALTER TABLE permintaan DROP COLUMN alamat_tujuan");
        }
        if (Schema::hasColumn('permintaan', 'jenis_layanan')) {
            DB::statement("ALTER TABLE permintaan DROP COLUMN jenis_layanan");
        }
        if (Schema::hasColumn('permintaan', 'prioritas')) {
            DB::statement("ALTER TABLE permintaan DROP COLUMN prioritas");
        }

        // 3. Revisi status enum — flow baru
        DB::statement("
            ALTER TABLE permintaan
            MODIFY COLUMN status ENUM(
                'menunggu',
                'disetujui',
                'terjadwal',
                'berjalan',
                'selesai',
                'ditolak'
            ) NOT NULL DEFAULT 'menunggu'
        ");

        // 4. FK urgensi_id
        DB::statement("
            ALTER TABLE permintaan
            ADD CONSTRAINT permintaan_urgensi_id_foreign
            FOREIGN KEY (urgensi_id) REFERENCES master_urgensi(id)
            ON DELETE SET NULL
        ");
    }

    public function down(): void
    {
        // Reversal: drop new FK dan kolom, kembalikan status enum lama
        $fkList = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permintaan'
              AND COLUMN_NAME = 'urgensi_id' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        foreach ($fkList as $fk) {
            DB::statement("ALTER TABLE permintaan DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        DB::statement("ALTER TABLE permintaan DROP COLUMN urgensi_id");

        DB::statement("
            ALTER TABLE permintaan
            MODIFY COLUMN status ENUM(
                'menunggu','disetujui','ditolak','selesai','waiting_driver'
            ) NOT NULL DEFAULT 'menunggu'
        ");
    }
};
