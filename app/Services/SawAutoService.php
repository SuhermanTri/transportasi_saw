<?php

namespace App\Services;

use App\Models\Kendaraan;
use App\Models\Kriteria;
use App\Models\Permintaan;

class SawAutoService
{
    private function mapPrioritas(string $prioritas): float
    {
        return match ($prioritas) {
            'tinggi' => 5,
            'sedang' => 3,
            default  => 1,
        };
    }

    private function mapJabatan(?string $jabatan): float
    {
        if (!$jabatan) return 2;
        $j = strtolower($jabatan);
        if (str_contains($j, 'manager') || str_contains($j, 'manajer')) return 5;
        if (str_contains($j, 'supervisor')) return 3;
        return 2;
    }

    private function mapPenumpang(int $jumlah): float
    {
        if ($jumlah > 6)  return 5;
        if ($jumlah >= 5) return 4;
        if ($jumlah >= 3) return 3;
        return 2;
    }

    private function kapasitasScore(int $kapasitas, int $jumlahDiminta, float $maxMapped): float
    {
        if ($kapasitas >= $jumlahDiminta) return $maxMapped;
        return max(1, round($kapasitas / max($jumlahDiminta, 1) * $maxMapped, 4));
    }

    private function tahunScore(int $tahun): float
    {
        $usia = (int) date('Y') - $tahun;
        if ($usia <= 3)  return 5;
        if ($usia <= 6)  return 4;
        if ($usia <= 9)  return 3;
        if ($usia <= 13) return 2;
        return 1;
    }

    private function resolveBobot(): array
    {
        $bobot = [
            'prioritas' => 0.20,
            'jabatan'   => 0.20,
            'penumpang' => 0.20,
            'jarak'     => 0.20,
            'rating'    => 0.10,
            'beban'     => 0.10,
        ];

        $kriteria = Kriteria::whereNotNull('bobot')->where('bobot', '>', 0)->get();
        if ($kriteria->isEmpty()) return $bobot;

        foreach ($kriteria as $k) {
            $nama = strtolower($k->nama_kriteria);
            if (str_contains($nama, 'prioritas'))  $bobot['prioritas'] = (float) $k->bobot;
            elseif (str_contains($nama, 'jabatan'))    $bobot['jabatan']   = (float) $k->bobot;
            elseif (str_contains($nama, 'penumpang'))  $bobot['penumpang'] = (float) $k->bobot;
            elseif (str_contains($nama, 'jarak'))      $bobot['jarak']     = (float) $k->bobot;
            elseif (str_contains($nama, 'rating'))     $bobot['rating']    = (float) $k->bobot;
            elseif (str_contains($nama, 'beban'))      $bobot['beban']     = (float) $k->bobot;
        }

        return $bobot;
    }

    public function rekomendasiUntukPermintaan(Permintaan $permintaan): ?int
    {
        $permintaan->loadMissing('user');

        // Hanya kendaraan dengan driver yang berstatus tersedia
        $kendaraan = Kendaraan::with('driverUser.penilaianSebagaiDriver')
            ->where('status', 'tersedia')
            ->whereHas('driverUser', fn($q) => $q->where('status_driver', 'tersedia'))
            ->get();

        // Fallback: semua kendaraan tersedia jika tidak ada yang memenuhi filter driver
        if ($kendaraan->isEmpty()) {
            $kendaraan = Kendaraan::with('driverUser.penilaianSebagaiDriver')
                ->where('status', 'tersedia')
                ->get();
        }

        if ($kendaraan->isEmpty()) return null;

        $bobot = $this->resolveBobot();

        $mappedPrioritas = $this->mapPrioritas($permintaan->prioritas);
        $mappedJabatan   = $this->mapJabatan($permintaan->user?->jabatan);
        $mappedPenumpang = $this->mapPenumpang($permintaan->jumlah_penumpang);

        $matrix = [];
        foreach ($kendaraan as $k) {
            $driver = $k->driverUser;
            $rating = $driver ? ($driver->penilaianSebagaiDriver->avg('nilai') ?: 3) : 3;
            // Beban: cost criterion → semakin sedikit perjalanan, semakin baik
            // Convert to benefit: inversikan (max - val), tapi simpan nilai asli untuk normalisasi cost
            $bebanRaw = $driver ? $driver->jumlah_perjalanan : 0;

            $matrix[$k->id_kendaraan] = [
                'prioritas' => $mappedPrioritas,
                'jabatan'   => $mappedJabatan,
                'penumpang' => $this->kapasitasScore($k->kapasitas, $permintaan->jumlah_penumpang, $mappedPenumpang),
                'jarak'     => $this->tahunScore($k->tahun),
                'rating'    => (float) $rating,   // benefit: max terbaik
                'beban'     => (float) $bebanRaw,  // cost: min terbaik
            ];
        }

        // Normalisasi benefit: R = val / max
        $maxP = max(array_column($matrix, 'prioritas')) ?: 1;
        $maxJ = max(array_column($matrix, 'jabatan'))   ?: 1;
        $maxK = max(array_column($matrix, 'penumpang')) ?: 1;
        $maxD = max(array_column($matrix, 'jarak'))     ?: 1;
        $maxR = max(array_column($matrix, 'rating'))    ?: 1;
        // Normalisasi cost: R = min / val
        $minB = min(array_column($matrix, 'beban'));

        $preferensi = [];
        foreach ($matrix as $kid => $s) {
            $normBeban = $s['beban'] > 0 ? $minB / $s['beban'] : 1;
            $preferensi[$kid] =
                $bobot['prioritas'] * ($s['prioritas'] / $maxP) +
                $bobot['jabatan']   * ($s['jabatan']   / $maxJ) +
                $bobot['penumpang'] * ($s['penumpang'] / $maxK) +
                $bobot['jarak']     * ($s['jarak']     / $maxD) +
                $bobot['rating']    * ($s['rating']    / $maxR) +
                $bobot['beban']     * $normBeban;
        }

        arsort($preferensi);
        return (int) array_key_first($preferensi);
    }
}
