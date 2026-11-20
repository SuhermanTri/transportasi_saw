<?php

namespace App\Services;

use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Logika bersama foto profil & nomor HP — dipakai oleh halaman Profil Saya
 * dan Data User (Admin) agar perilakunya sama.
 */
class ProfilService
{
    public const DISK   = 'public';
    public const FOLDER = 'foto-profil';

    /** Aturan validasi bersama. */
    public const ATURAN = [
        'foto'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\- ]+$/'],
    ];

    public const PESAN = [
        'foto.image'  => 'File foto harus berupa gambar.',
        'foto.mimes'  => 'Foto harus berformat JPG, JPEG, atau PNG.',
        'foto.max'    => 'Ukuran foto maksimal 2 MB.',
        'foto.uploaded' => 'Foto gagal diunggah (ukuran maksimal 2 MB).',
        'no_hp.regex' => 'Nomor HP hanya boleh berisi angka, spasi, +, atau -.',
        'no_hp.max'   => 'Nomor HP maksimal 20 karakter.',
    ];

    /**
     * Simpan foto baru (nama acak) / hapus foto lama.
     * Mengembalikan path baru untuk kolom users.foto_profil (null = tanpa foto).
     */
    public function prosesFoto(?string $fotoLama, ?UploadedFile $fotoBaru, bool $hapus): ?string
    {
        if ($fotoBaru) {
            $path = $fotoBaru->store(self::FOLDER, self::DISK);   // nama file acak
            $this->hapusFile($fotoLama);
            return $path;
        }

        if ($hapus) {
            $this->hapusFile($fotoLama);
            return null;
        }

        return $fotoLama;
    }

    public function hapusFile(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /** Driver: samakan kendaraan.no_hp_driver dengan nomor HP akun. */
    public function sinkronNoHpDriver(User $user): void
    {
        if ((int) $user->id_role !== 3) {
            return;
        }

        Kendaraan::where('driver_user_id', $user->id_user)->update(['no_hp_driver' => $user->no_hp]);
    }
}
