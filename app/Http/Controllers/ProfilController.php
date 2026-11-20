<?php

namespace App\Http\Controllers;

use App\Services\ProfilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Profil Saya — dapat diakses semua peran (user, driver, dispatcher).
 * Nama, username, peran, dan jabatan hanya ditampilkan; jabatan memengaruhi
 * prioritas SAW sehingga hanya Dispatcher yang boleh mengubahnya (Data User).
 */
class ProfilController extends Controller
{
    public function __construct(private ProfilService $profil) {}

    public function index()
    {
        $user = Auth::user()->load(['role', 'kendaraan']);
        return view('profil.index', compact('user'));
    }

    /** Foto profil & nomor HP. */
    public function update(Request $request)
    {
        $request->validate(ProfilService::ATURAN, ProfilService::PESAN);

        $user = Auth::user();

        $user->update([
            'foto_profil' => $this->profil->prosesFoto(
                $user->foto_profil, $request->file('foto'), $request->boolean('hapus_foto')
            ),
            'no_hp' => $request->filled('no_hp') ? trim($request->no_hp) : null,
        ]);

        // Driver: nomor HP ikut ke data kendaraan
        $this->profil->sinkronNoHpDriver($user);

        return redirect()->route('profil.index')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password_lama' => 'required|current_password',
            'password'      => 'required|string|min:6|confirmed',
        ], [
            'password_lama.required'         => 'Password lama wajib diisi.',
            'password_lama.current_password' => 'Password lama tidak sesuai.',
            'password.required'              => 'Password baru wajib diisi.',
            'password.min'                   => 'Password baru minimal 6 karakter.',
            'password.confirmed'             => 'Konfirmasi password baru tidak cocok.',
        ]);

        Auth::user()->update(['password' => Hash::make($request->password)]);

        return redirect()->route('profil.index')->with('success', 'Password berhasil diperbarui.');
    }
}
