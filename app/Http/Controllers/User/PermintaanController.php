<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MasterUrgensi;
use App\Models\Notifikasi;
use App\Models\Permintaan;
use App\Services\SawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermintaanController extends Controller
{
    public function __construct(private SawService $saw) {}

    public function index()
    {
        $permintaan = Permintaan::with(['urgensi', 'jadwal'])
            ->where('id_user', Auth::id())
            ->orderByDesc('created_at')
            ->get();
        return view('user.permintaan.index', compact('permintaan'));
    }

    public function create()
    {
        $urgensiList = MasterUrgensi::orderByDesc('nilai_saw')->get();
        return view('user.permintaan.create', compact('urgensiList'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tujuan'            => 'required|string|max:150',
            'urgensi_id'        => 'required|exists:master_urgensi,id',
            'tanggal_berangkat' => [
                'required', 'date',
                'after_or_equal:today',
                'before_or_equal:' . today()->addDay()->format('Y-m-d'),
            ],
            'jam_berangkat'     => 'required|date_format:H:i',
            'tanggal_kembali'   => 'nullable|date|after_or_equal:tanggal_berangkat',
            'jam_kembali'       => 'nullable|date_format:H:i',
            'jumlah_penumpang'  => 'required|integer|min:1|max:50',
            'keterangan'        => 'nullable|string|max:255',
        ], [
            'tanggal_berangkat.after_or_equal'  => 'Permintaan kendaraan hanya dapat diajukan untuk hari ini atau besok.',
            'tanggal_berangkat.before_or_equal' => 'Permintaan kendaraan hanya dapat diajukan untuk hari ini atau besok.',
        ]);

        $permintaan = Permintaan::create([
            ...$data,
            'id_user'    => Auth::id(),
            'status'     => 'menunggu',
            'created_at' => now(),
        ]);

        $this->runSawSilently();

        // Notifikasi ke dispatcher
        $user = Auth::user();
        $tgl  = $permintaan->tanggal_berangkat->format('d M Y');
        Notifikasi::kirimKeDispatcher(
            "Permintaan baru dari {$user->nama}: \"{$permintaan->tujuan}\" ({$tgl} {$permintaan->jam_berangkat})",
            route('admin.permintaan.show', $permintaan->id_permintaan)
        );

        return redirect()->route('user.permintaan.index')
            ->with('success', 'Permintaan kendaraan berhasil diajukan.');
    }

    public function show($id)
    {
        $permintaan = Permintaan::with(['urgensi', 'jadwal.kendaraan.driverUser', 'jadwal.penilaian', 'hasilSaw'])
            ->where('id_user', Auth::id())
            ->findOrFail($id);
        return view('user.permintaan.show', compact('permintaan'));
    }

    public function edit($id)
    {
        $permintaan  = Permintaan::where('id_user', Auth::id())
            ->where('status', 'menunggu')
            ->findOrFail($id);
        $urgensiList = MasterUrgensi::orderByDesc('nilai_saw')->get();
        return view('user.permintaan.edit', compact('permintaan', 'urgensiList'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'tujuan'            => 'required|string|max:150',
            'urgensi_id'        => 'required|exists:master_urgensi,id',
            'tanggal_berangkat' => [
                'required', 'date',
                'after_or_equal:today',
                'before_or_equal:' . today()->addDay()->format('Y-m-d'),
            ],
            'jam_berangkat'     => 'required|date_format:H:i',
            'tanggal_kembali'   => 'nullable|date|after_or_equal:tanggal_berangkat',
            'jam_kembali'       => 'nullable|date_format:H:i',
            'jumlah_penumpang'  => 'required|integer|min:1|max:50',
            'keterangan'        => 'nullable|string|max:255',
        ], [
            'tanggal_berangkat.after_or_equal'  => 'Permintaan kendaraan hanya dapat diajukan untuk hari ini atau besok.',
            'tanggal_berangkat.before_or_equal' => 'Permintaan kendaraan hanya dapat diajukan untuk hari ini atau besok.',
        ]);

        $permintaan = Permintaan::where('id_user', Auth::id())
            ->where('status', 'menunggu')
            ->findOrFail($id);

        $permintaan->update($data);
        $this->runSawSilently();

        // Notifikasi ke dispatcher (permintaan berubah)
        $user = Auth::user();
        $tgl  = $permintaan->fresh()->tanggal_berangkat->format('d M Y');
        Notifikasi::kirimKeDispatcher(
            "Permintaan diperbarui oleh {$user->nama}: \"{$permintaan->tujuan}\" ({$tgl})",
            route('admin.permintaan.show', $permintaan->id_permintaan)
        );

        return redirect()->route('user.permintaan.index')
            ->with('success', 'Permintaan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $permintaan = Permintaan::where('id_user', Auth::id())
            ->where('status', 'menunggu')
            ->findOrFail($id);

        $tujuan = $permintaan->tujuan;
        $tgl    = $permintaan->tanggal_berangkat->format('d M Y');
        $permintaan->delete();

        // Notifikasi ke dispatcher (permintaan dibatalkan user)
        Notifikasi::kirimKeDispatcher(
            Auth::user()->nama . " membatalkan permintaan: \"{$tujuan}\" ({$tgl})"
        );

        return redirect()->route('user.permintaan.index')
            ->with('success', 'Permintaan berhasil dibatalkan.');
    }

    private function runSawSilently(): void
    {
        try {
            $this->saw->hitung();
        } catch (\Throwable) {
            // Kegagalan SAW tidak memblokir proses permintaan
        }
    }
}
