<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanJadwalExport;
use App\Exports\LaporanPermintaanExport;
use App\Http\Controllers\Controller;
use App\Models\HasilSaw;
use App\Models\Jadwal;
use App\Models\Permintaan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        [$tanggal, $dateFrom, $dateTo] = $this->resolveFilter($request);
        $isDefault = !$request->hasAny(['tanggal', 'date_from', 'date_to']);

        $permintaan = $this->queryPermintaan($tanggal, $dateFrom, $dateTo)
            ->orderByDesc('jam_berangkat')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $jadwalCount = $this->queryJadwal($tanggal, $dateFrom, $dateTo)->count();

        $ranking = HasilSaw::with(['permintaan.user', 'permintaan.urgensi'])
            ->orderBy('ranking')
            ->get();

        $filterLabel = $this->buildFilterLabel($tanggal, $dateFrom, $dateTo);

        return view('admin.laporan.index', compact(
            'permintaan', 'jadwalCount', 'ranking',
            'filterLabel', 'isDefault',
            'tanggal', 'dateFrom', 'dateTo'
        ));
    }

    public function exportPermintaanPdf(Request $request)
    {
        [$tanggal, $dateFrom, $dateTo] = $this->resolveFilter($request, false);

        $permintaan = $this->queryPermintaan($tanggal, $dateFrom, $dateTo)
            ->orderByDesc('jam_berangkat')
            ->orderByDesc('created_at')
            ->get();

        $filterLabel = $this->buildFilterLabel($tanggal, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('admin.laporan.pdf_permintaan', compact('permintaan', 'filterLabel'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-permintaan.pdf');
    }

    public function exportPermintaanExcel(Request $request)
    {
        [$tanggal, $dateFrom, $dateTo] = $this->resolveFilter($request, false);

        return Excel::download(
            new LaporanPermintaanExport($tanggal, $dateFrom, $dateTo),
            'laporan-permintaan.xlsx'
        );
    }

    public function exportJadwalPdf(Request $request)
    {
        [$tanggal, $dateFrom, $dateTo] = $this->resolveFilter($request, false);

        $jadwal = $this->queryJadwal($tanggal, $dateFrom, $dateTo)
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_mulai')
            ->get();

        $filterLabel = $this->buildFilterLabel($tanggal, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('admin.laporan.pdf_jadwal', compact('jadwal', 'filterLabel'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-jadwal.pdf');
    }

    public function exportJadwalExcel(Request $request)
    {
        [$tanggal, $dateFrom, $dateTo] = $this->resolveFilter($request, false);

        return Excel::download(
            new LaporanJadwalExport($tanggal, $dateFrom, $dateTo),
            'laporan-jadwal.xlsx'
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Resolve filter params from request.
     * $defaultToday: if true, default to today when no filter given (for index page).
     * Returns [$tanggal, $dateFrom, $dateTo].
     */
    private function resolveFilter(Request $request, bool $defaultToday = true): array
    {
        $tanggal  = $request->input('tanggal')    ?: null;
        $dateFrom = $request->input('date_from')  ?: null;
        $dateTo   = $request->input('date_to')    ?: null;

        if ($defaultToday && !$tanggal && !$dateFrom && !$dateTo) {
            $tanggal = today()->format('Y-m-d');
        }

        return [$tanggal, $dateFrom, $dateTo];
    }

    private function queryPermintaan(?string $tanggal, ?string $dateFrom, ?string $dateTo)
    {
        return $this->applyDateFilter(
            Permintaan::with(['user', 'urgensi']),
            'tanggal_berangkat',
            $tanggal, $dateFrom, $dateTo
        );
    }

    private function queryJadwal(?string $tanggal, ?string $dateFrom, ?string $dateTo)
    {
        return $this->applyDateFilter(
            Jadwal::with(['permintaan.user', 'kendaraan']),
            'tanggal',
            $tanggal, $dateFrom, $dateTo
        );
    }

    private function applyDateFilter($query, string $column, ?string $tanggal, ?string $dateFrom, ?string $dateTo)
    {
        if ($tanggal) {
            return $query->whereDate($column, $tanggal);
        }
        if ($dateFrom && $dateTo) {
            return $query->whereBetween($column, [$dateFrom, $dateTo]);
        }
        if ($dateFrom) {
            return $query->whereDate($column, '>=', $dateFrom);
        }
        if ($dateTo) {
            return $query->whereDate($column, '<=', $dateTo);
        }
        return $query->whereDate($column, today());
    }

    private function buildFilterLabel(?string $tanggal, ?string $dateFrom, ?string $dateTo): string
    {
        $fmt = fn(string $d) => Carbon::parse($d)->format('d') . ' ' .
            ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
             'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][Carbon::parse($d)->month] .
            ' ' . Carbon::parse($d)->year;

        if ($tanggal)                 return $fmt($tanggal);
        if ($dateFrom && $dateTo)     return $fmt($dateFrom) . ' – ' . $fmt($dateTo);
        if ($dateFrom)                return 'sejak ' . $fmt($dateFrom);
        if ($dateTo)                  return 'sampai ' . $fmt($dateTo);

        return 'hari ini (' . $fmt(today()->format('Y-m-d')) . ')';
    }
}
