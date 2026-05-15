<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Notifikasi;
use App\Models\PenilaianDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenilaianController extends Controller
{
    public function create($jadwalId)
    {
        $jadwal = Jadwal::with(['permintaan', 'kendaraan.driverUser', 'penilaian'])
            ->whereHas('permintaan', fn($q) => $q->where('id_user', Auth::id()))
            ->findOrFail($jadwalId);

        if ($jadwal->status_perjalanan !== 'selesai_driver') {
            return redirect()->back()->with('error', 'Perjalanan belum selesai dikonfirmasi driver.');
        }

        if ($jadwal->penilaian) {
            return redirect()->back()->with('error', 'Anda sudah memberikan penilaian.');
        }

        return view('user.penilaian.create', compact('jadwal'));
    }

    public function store(Request $request, $jadwalId)
    {
        $request->validate([
            'nilai'    => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:500',
        ]);

        $jadwal = Jadwal::with(['permintaan', 'kendaraan.driverUser', 'penilaian'])
            ->whereHas('permintaan', fn($q) => $q->where('id_user', Auth::id()))
            ->findOrFail($jadwalId);

        if ($jadwal->status_perjalanan !== 'selesai_driver') {
            return redirect()->back()->with('error', 'Perjalanan belum selesai.');
        }

        if ($jadwal->penilaian) {
            return redirect()->back()->with('error', 'Penilaian sudah diberikan.');
        }

        $driver = $jadwal->kendaraan?->driverUser;

        PenilaianDriver::create([
            'driver_id'  => $driver?->id_user,
            'user_id'    => Auth::id(),
            'id_jadwal'  => $jadwal->id_jadwal,
            'nilai'      => $request->nilai,
            'komentar'   => $request->komentar,
        ]);

        // Tandai perjalanan selesai final
        $jadwal->update(['status_perjalanan' => 'selesai_final', 'status' => 'selesai']);

        // Bebaskan kendaraan
        $jadwal->kendaraan?->update(['status' => 'tersedia']);

        // Update status permintaan
        $jadwal->permintaan?->update(['status' => 'selesai']);

        // Notifikasi ke driver
        if ($driver) {
            Notifikasi::kirim(
                $driver->id_user,
                "Perjalanan Anda telah dikonfirmasi selesai. Rating: {$request->nilai}/5.",
                route('driver.jadwal.show', $jadwal->id_jadwal)
            );
        }

        return redirect()->route('user.permintaan.index')
            ->with('success', 'Konfirmasi dan penilaian berhasil dikirim. Terima kasih!');
    }
}
