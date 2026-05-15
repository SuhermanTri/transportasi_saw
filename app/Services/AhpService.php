<?php

namespace App\Services;

use App\Models\Kriteria;
use App\Models\PerbandinganKriteria;

class AhpService
{
    // Random Index values (Saaty)
    private array $ri = [0, 0, 0.58, 0.90, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49];

    public function hitung(): array
    {
        $kriteria   = Kriteria::orderBy('id_kriteria')->get();
        $n          = $kriteria->count();
        $ids        = $kriteria->pluck('id_kriteria')->toArray();

        // Build pairwise matrix
        $matrix = $this->buildMatrix($ids, $n);

        // Column sums
        $colSum = array_fill(0, $n, 0);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $colSum[$j] += $matrix[$i][$j];
            }
        }

        // Normalize matrix
        $normalized = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $normalized[$i][$j] = $colSum[$j] > 0
                    ? $matrix[$i][$j] / $colSum[$j]
                    : 0;
            }
        }

        // Priority vector (eigen vector)
        $priority = [];
        for ($i = 0; $i < $n; $i++) {
            $rowSum = array_sum($normalized[$i]);
            $priority[$i] = $rowSum / $n;
        }

        // Lambda max
        $lambdaMax = 0;
        for ($j = 0; $j < $n; $j++) {
            $colTotal = 0;
            for ($i = 0; $i < $n; $i++) {
                $colTotal += $matrix[$i][$j];
            }
            $lambdaMax += $colTotal * $priority[$j];
        }

        // Consistency Index & Ratio
        $ci = ($lambdaMax - $n) / max($n - 1, 1);
        $ri = $this->ri[$n] ?? 1;
        $cr = $ri > 0 ? $ci / $ri : 0;

        // Save weights to kriteria table
        foreach ($ids as $idx => $id) {
            Kriteria::where('id_kriteria', $id)->update(['bobot' => $priority[$idx]]);
        }

        return [
            'kriteria'   => $kriteria,
            'n'          => $n,
            'matrix'     => $matrix,
            'colSum'     => $colSum,
            'normalized' => $normalized,
            'priority'   => $priority,
            'lambdaMax'  => $lambdaMax,
            'ci'         => $ci,
            'ri'         => $ri,
            'cr'         => $cr,
            'consistent' => $cr <= 0.1,
            'ids'        => $ids,
        ];
    }

    private function buildMatrix(array $ids, int $n): array
    {
        $perbandingan = PerbandinganKriteria::all();

        // Init matrix as identity
        $matrix = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $matrix[$i][$j] = ($i === $j) ? 1 : 0;
            }
        }

        foreach ($perbandingan as $p) {
            $r = array_search($p->kriteria1, $ids);
            $c = array_search($p->kriteria2, $ids);
            if ($r !== false && $c !== false) {
                $matrix[$r][$c] = $p->nilai;
                $matrix[$c][$r] = 1 / $p->nilai;
            }
        }

        return $matrix;
    }

    public function getMatrix(): array
    {
        $kriteria = Kriteria::orderBy('id_kriteria')->get();
        $ids      = $kriteria->pluck('id_kriteria')->toArray();
        $n        = count($ids);
        return $this->buildMatrix($ids, $n);
    }
}
