<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HasilSaw;
use App\Models\Kriteria;
use App\Services\SawService;

class SawController extends Controller
{
    public function __construct(private SawService $saw) {}

    public function index()
    {
        $kriteria = Kriteria::orderBy('id_kriteria')->get();
        $hasil    = HasilSaw::with('permintaan.user', 'permintaan.urgensi')
            ->orderBy('ranking')
            ->get();

        $bobotLengkap = $kriteria->every(fn($k) => $k->bobot !== null && $k->bobot > 0);

        return view('admin.saw.index', compact('kriteria', 'hasil', 'bobotLengkap'));
    }

    public function hitung()
    {
        $bobotKosong = Kriteria::whereNull('bobot')->orWhere('bobot', 0)->count();
        if ($bobotKosong > 0) {
            return redirect()->route('admin.saw.index')
                ->with('error', 'Hitung bobot AHP terlebih dahulu sebelum proses SAW.');
        }

        $this->saw->hitung();

        return redirect()->route('admin.saw.index')
            ->with('success', 'Ranking prioritas permintaan berhasil dihitung.');
    }
}
