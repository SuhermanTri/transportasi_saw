<?php

namespace App\Services;

use App\Models\HasilSaw;
use App\Models\Kriteria;
use App\Models\MasterJabatan;
use App\Models\MasterUrgensi;
use App\Models\Permintaan;

class SawService
{
    public function __construct(private GageService $gage) {}

    /**
     * Hitung SAW untuk semua permintaan aktif:
     * 'menunggu', 'disetujui', 'menunggu_konfirmasi' (antrean penjadwalan),
     * serta 'terjadwal' dan 'berjalan' agar skornya tetap tampil setelah dijadwalkan.
     * Tiga kriteria: Jabatan, Urgensi, Gage Kendaraan (semua benefit).
     * Nilai akhir sama → urutan pengajuan (id_permintaan) sebagai pemecah.
     */
    public function hitung(): array
    {
        $permintaanList = Permintaan::with(['user', 'urgensi'])
            ->whereIn('status', ['menunggu', 'disetujui', 'menunggu_konfirmasi', 'terjadwal', 'berjalan'])
            ->orderBy('id_permintaan')
            ->get();

        if ($permintaanList->isEmpty()) {
            return ['permintaan' => collect(), 'scores' => [], 'bobot' => []];
        }

        [$bobotJabatan, $bobotUrgensi, $bobotGage] = $this->ambilBobot();

        $maxJabatan = MasterJabatan::max('nilai_saw') ?: 5;
        $maxUrgensi = MasterUrgensi::max('nilai_saw') ?: 5;

        $scores = [];
        foreach ($permintaanList as $p) {
            $nilaiJabatan = MasterJabatan::nilaiUntuk((string) ($p->user?->jabatan ?? ''));
            $nilaiUrgensi = $p->urgensi?->nilai_saw ?? 1;
            $nilaiGage    = $this->gage->skorGagePermintaan(
                                $p->tanggal_berangkat,
                                $p->jam_berangkat ?? '08:00',
                                $p->jam_kembali   ?? '17:00'
                            );

            $normJabatan = $nilaiJabatan / $maxJabatan;
            $normUrgensi = $nilaiUrgensi / $maxUrgensi;

            $skorJabatan = round($bobotJabatan * $normJabatan, 4);
            $skorUrgensi = round($bobotUrgensi * $normUrgensi, 4);
            $skorGage    = round($bobotGage    * $nilaiGage,   4);
            // Jumlahkan dari nilai utuh agar pembulatan per komponen tidak menggeser ranking
            $nilaiAkhir  = round(
                $bobotJabatan * $normJabatan + $bobotUrgensi * $normUrgensi + $bobotGage * $nilaiGage,
                4
            );

            $scores[$p->id_permintaan] = compact(
                'skorJabatan', 'skorUrgensi', 'skorGage', 'nilaiAkhir'
            );
        }

        // Ranking (terbesar = rank 1)
        uasort($scores, fn($a, $b) => $b['nilaiAkhir'] <=> $a['nilaiAkhir']);
        $rank = 1;
        foreach ($scores as &$s) {
            $s['ranking'] = $rank++;
        }
        unset($s);

        // Simpan ke hasil_saw
        HasilSaw::truncate();
        $now = now();
        foreach ($permintaanList as $p) {
            $s = $scores[$p->id_permintaan];
            HasilSaw::create([
                'id_permintaan' => $p->id_permintaan,
                'skor_jabatan'  => $s['skorJabatan'],
                'skor_urgensi'  => $s['skorUrgensi'],
                'skor_gage'     => $s['skorGage'],
                'nilai_akhir'   => $s['nilaiAkhir'],
                'ranking'       => $s['ranking'],
                'created_at'    => $now,
            ]);
        }

        return [
            'permintaan' => $permintaanList,
            'scores'     => $scores,
            'bobot'      => compact('bobotJabatan', 'bobotUrgensi', 'bobotGage'),
        ];
    }

    /** Ambil bobot AHP dari tabel kriteria. Fallback ke 1/3 jika belum dihitung. */
    private function ambilBobot(): array
    {
        $kriteria = Kriteria::orderBy('id_kriteria')->get();

        $bobotJabatan = $kriteria->firstWhere(fn($k) => str_contains(strtolower($k->nama_kriteria), 'jabatan'))?->bobot;
        $bobotUrgensi = $kriteria->firstWhere(fn($k) => str_contains(strtolower($k->nama_kriteria), 'urgensi'))?->bobot;
        $bobotGage    = $kriteria->firstWhere(fn($k) => str_contains(strtolower($k->nama_kriteria), 'gage'))?->bobot;

        $default = 1 / 3;
        return [
            $bobotJabatan ?? $default,
            $bobotUrgensi ?? $default,
            $bobotGage    ?? $default,
        ];
    }
}
