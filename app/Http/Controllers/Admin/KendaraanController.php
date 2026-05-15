<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Http\Request;

class KendaraanController extends Controller
{
    public function index()
    {
        $kendaraan = Kendaraan::with('driverUser')->orderBy('id_kendaraan')->get();
        return view('admin.kendaraan.index', compact('kendaraan'));
    }

    public function create()
    {
        $drivers = User::where('id_role', 3)->orderBy('nama')->get();
        return view('admin.kendaraan.create', compact('drivers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kendaraan'          => 'required|string|max:50',
            'no_polisi'               => 'required|string|max:15',
            'driver_user_id'          => 'nullable|exists:users,id_user',
            'no_hp_driver'            => 'nullable|string|max:15',
            'kapasitas'               => 'required|integer|min:1',
            'status'                  => 'required|in:tersedia,dipakai',
            'tahun'                   => 'required|integer|min:1990|max:2099',
            'warna'                   => 'nullable|string|max:20',
            'jam_mulai_operasional'   => 'nullable|date_format:H:i',
            'jam_selesai_operasional' => 'nullable|date_format:H:i',
        ]);

        $data = $request->except(['nama_driver', 'tipe_gage']);

        // Sinkronkan nama_driver dari akun user driver yang dipilih
        if ($request->filled('driver_user_id')) {
            $driverUser = User::find($request->driver_user_id);
            $data['nama_driver'] = $driverUser?->nama;
        } else {
            $data['nama_driver'] = null;
        }

        Kendaraan::create($data);

        return redirect()->route('admin.kendaraan.index')
            ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $kendaraan = Kendaraan::findOrFail($id);
        $drivers   = User::where('id_role', 3)->orderBy('nama')->get();
        return view('admin.kendaraan.edit', compact('kendaraan', 'drivers'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kendaraan'          => 'required|string|max:50',
            'no_polisi'               => 'required|string|max:15',
            'driver_user_id'          => 'nullable|exists:users,id_user',
            'no_hp_driver'            => 'nullable|string|max:15',
            'kapasitas'               => 'required|integer|min:1',
            'status'                  => 'required|in:tersedia,dipakai',
            'tahun'                   => 'required|integer|min:1990|max:2099',
            'warna'                   => 'nullable|string|max:20',
            'jam_mulai_operasional'   => 'nullable|date_format:H:i',
            'jam_selesai_operasional' => 'nullable|date_format:H:i',
        ]);

        $data = $request->except(['nama_driver', 'tipe_gage']);

        // Sinkronkan nama_driver dari akun user driver yang dipilih
        if ($request->filled('driver_user_id')) {
            $driverUser = User::find($request->driver_user_id);
            $data['nama_driver'] = $driverUser?->nama;
        } else {
            $data['nama_driver'] = null;
        }

        $kendaraan = Kendaraan::findOrFail($id);
        $kendaraan->update($data);

        return redirect()->route('admin.kendaraan.index')
            ->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Kendaraan::findOrFail($id)->delete();
        return redirect()->route('admin.kendaraan.index')
            ->with('success', 'Kendaraan berhasil dihapus.');
    }
}
