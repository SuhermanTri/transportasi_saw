<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kriteria;
use App\Models\PerbandinganKriteria;
use App\Services\AhpService;
use Illuminate\Http\Request;

class AhpController extends Controller
{
    public function __construct(private AhpService $ahp) {}

    public function index()
    {
        $kriteria     = Kriteria::orderBy('id_kriteria')->get();
        $perbandingan = PerbandinganKriteria::with('kriteriaA', 'kriteriaB')->get();
        $hasil        = null;

        if ($kriteria->count() >= 2 && $perbandingan->count() >= 1) {
            $hasil = $this->ahp->hitung();
        }

        return view('admin.ahp.index', compact('kriteria', 'perbandingan', 'hasil'));
    }

    public function storePerbandingan(Request $request)
    {
        $request->validate([
            'kriteria1' => 'required|exists:kriteria,id_kriteria',
            'kriteria2' => 'required|exists:kriteria,id_kriteria|different:kriteria1',
            'nilai'     => 'required|numeric|min:0.1|max:9',
        ]);

        PerbandinganKriteria::updateOrCreate(
            ['kriteria1' => $request->kriteria1, 'kriteria2' => $request->kriteria2],
            ['nilai' => $request->nilai]
        );

        return redirect()->route('admin.ahp.index')
            ->with('success', 'Perbandingan kriteria berhasil disimpan.');
    }

    public function storeKriteria(Request $request)
    {
        $request->validate([
            'nama_kriteria' => 'required|string|max:50',
            'jenis'         => 'required|in:benefit,cost',
        ]);

        Kriteria::create($request->only('nama_kriteria', 'jenis'));
        return redirect()->route('admin.ahp.index')
            ->with('success', 'Kriteria berhasil ditambahkan.');
    }

    public function deleteKriteria($id)
    {
        Kriteria::findOrFail($id)->delete();
        return redirect()->route('admin.ahp.index')
            ->with('success', 'Kriteria berhasil dihapus.');
    }

    public function deletePerbandingan($id)
    {
        PerbandinganKriteria::findOrFail($id)->delete();
        return redirect()->route('admin.ahp.index')
            ->with('success', 'Perbandingan berhasil dihapus.');
    }

    public function hitung()
    {
        $this->ahp->hitung();
        return redirect()->route('admin.ahp.index')
            ->with('success', 'Bobot AHP berhasil dihitung dan disimpan.');
    }
}
