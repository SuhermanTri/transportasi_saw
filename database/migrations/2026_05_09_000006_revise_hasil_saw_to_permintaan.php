<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Bersihkan data lama terlebih dahulu
        DB::table('hasil_saw')->truncate();

        // Drop FK id_kendaraan (jika masih ada)
        $fkList = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'hasil_saw'
              AND COLUMN_NAME  = 'id_kendaraan'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        foreach ($fkList as $fk) {
            DB::statement("ALTER TABLE hasil_saw DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Drop kolom lama
        if (Schema::hasColumn('hasil_saw', 'id_kendaraan')) {
            DB::statement("ALTER TABLE hasil_saw DROP COLUMN id_kendaraan");
        }
        if (Schema::hasColumn('hasil_saw', 'nilai_preferensi')) {
            DB::statement("ALTER TABLE hasil_saw DROP COLUMN nilai_preferensi");
        }

        // Tambah kolom baru (skip jika sudah ada dari partial run sebelumnya)
        if (! Schema::hasColumn('hasil_saw', 'id_permintaan')) {
            DB::statement("ALTER TABLE hasil_saw ADD COLUMN id_permintaan BIGINT UNSIGNED NOT NULL AFTER id_hasil");
        } else {
            // Pastikan tipe sudah BIGINT (partial run mungkin buat INT)
            DB::statement("ALTER TABLE hasil_saw MODIFY COLUMN id_permintaan BIGINT UNSIGNED NOT NULL");
        }

        if (! Schema::hasColumn('hasil_saw', 'skor_jabatan')) {
            DB::statement("ALTER TABLE hasil_saw ADD COLUMN skor_jabatan DECIMAL(8,4) NOT NULL DEFAULT 0 AFTER id_permintaan");
        }
        if (! Schema::hasColumn('hasil_saw', 'skor_urgensi')) {
            DB::statement("ALTER TABLE hasil_saw ADD COLUMN skor_urgensi DECIMAL(8,4) NOT NULL DEFAULT 0 AFTER skor_jabatan");
        }
        if (! Schema::hasColumn('hasil_saw', 'skor_gage')) {
            DB::statement("ALTER TABLE hasil_saw ADD COLUMN skor_gage    DECIMAL(8,4) NOT NULL DEFAULT 0 AFTER skor_urgensi");
        }
        if (! Schema::hasColumn('hasil_saw', 'nilai_akhir')) {
            DB::statement("ALTER TABLE hasil_saw ADD COLUMN nilai_akhir  DECIMAL(8,4) NOT NULL DEFAULT 0 AFTER skor_gage");
        }
        if (! Schema::hasColumn('hasil_saw', 'created_at')) {
            DB::statement("ALTER TABLE hasil_saw ADD COLUMN created_at   TIMESTAMP NULL DEFAULT NULL AFTER ranking");
        }

        // Tambah FK (hanya jika belum ada)
        $fkExists = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'hasil_saw'
              AND COLUMN_NAME  = 'id_permintaan'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if (empty($fkExists)) {
            DB::statement("
                ALTER TABLE hasil_saw
                ADD CONSTRAINT hasil_saw_id_permintaan_foreign
                FOREIGN KEY (id_permintaan) REFERENCES permintaan(id_permintaan)
                ON DELETE CASCADE
            ");
        }
    }

    public function down(): void
    {
        DB::table('hasil_saw')->truncate();

        $fkList = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hasil_saw'
              AND COLUMN_NAME = 'id_permintaan' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        foreach ($fkList as $fk) {
            DB::statement("ALTER TABLE hasil_saw DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        if (Schema::hasColumn('hasil_saw', 'created_at')) {
            DB::statement("ALTER TABLE hasil_saw DROP COLUMN created_at");
        }
        if (Schema::hasColumn('hasil_saw', 'nilai_akhir')) {
            DB::statement("ALTER TABLE hasil_saw DROP COLUMN nilai_akhir");
        }
        if (Schema::hasColumn('hasil_saw', 'skor_gage')) {
            DB::statement("ALTER TABLE hasil_saw DROP COLUMN skor_gage");
        }
        if (Schema::hasColumn('hasil_saw', 'skor_urgensi')) {
            DB::statement("ALTER TABLE hasil_saw DROP COLUMN skor_urgensi");
        }
        if (Schema::hasColumn('hasil_saw', 'skor_jabatan')) {
            DB::statement("ALTER TABLE hasil_saw DROP COLUMN skor_jabatan");
        }
        if (Schema::hasColumn('hasil_saw', 'id_permintaan')) {
            DB::statement("ALTER TABLE hasil_saw DROP COLUMN id_permintaan");
        }

        if (! Schema::hasColumn('hasil_saw', 'id_kendaraan')) {
            DB::statement("ALTER TABLE hasil_saw ADD COLUMN id_kendaraan BIGINT UNSIGNED NOT NULL AFTER id_hasil");
        }
        if (! Schema::hasColumn('hasil_saw', 'nilai_preferensi')) {
            DB::statement("ALTER TABLE hasil_saw ADD COLUMN nilai_preferensi DECIMAL(8,4) DEFAULT 0 AFTER id_kendaraan");
        }
    }
};
