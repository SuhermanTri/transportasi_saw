<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    public function index()
    {
        $jadwal = Jadwal::with(['kendaraan', 'permintaan'])
            ->whereHas('permintaan', fn($q) => $q->where('id_user', Auth::id()))
            ->orderByDesc('tanggal')
            ->get();

        return view('user.jadwal.index', compact('jadwal'));
    }
}
