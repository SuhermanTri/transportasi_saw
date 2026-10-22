<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kendaraan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Validate role param so only known values are accepted
        $role = in_array($request->input('role'), ['user', 'driver', 'dispatcher'])
            ? $request->input('role')
            : null;
        $q = trim($request->input('q', ''));

        // One query for all role counts — fast on initial load
        $roleStats = Role::withCount('users')->get()->keyBy('nama_role');
        $counts = [
            'user'       => $roleStats->get('user')?->users_count       ?? 0,
            'driver'     => $roleStats->get('driver')?->users_count      ?? 0,
            'dispatcher' => $roleStats->get('dispatcher')?->users_count  ?? 0,
        ];

        // Only load table data when a role is explicitly selected
        $users = null;
        if ($role) {
            $eagerLoad = $role === 'driver' ? ['role', 'kendaraan'] : ['role'];

            $users = User::with($eagerLoad)
                ->whereHas('role', fn($r) => $r->where('nama_role', $role))
                ->when($q, fn($query) => $query->where(function ($sub) use ($q) {
                    $sub->where('nama',     'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhere('jabatan',  'like', "%{$q}%");
                }))
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString();
        }

        return view('admin.users.index', compact('counts', 'role', 'users', 'q'));
    }

    public function create()
    {
        $roles = Role::orderBy('id_role')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'     => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'id_role'  => 'required|exists:roles,id_role',
            'jabatan'  => 'nullable|string|max:50',
        ], [
            'username.unique'       => 'Username sudah digunakan.',
            'password.min'          => 'Password minimal 6 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::create([
            'nama'     => $request->nama,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'id_role'  => $request->id_role,
            'jabatan'  => $request->jabatan,
        ]);

        $this->hubungkanKendaraanDriver($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user  = User::findOrFail($id);
        $roles = Role::orderBy('id_role')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nama'     => 'required|string|max:100',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id_user, 'id_user')],
            'password' => 'nullable|string|min:6|confirmed',
            'id_role'  => 'required|exists:roles,id_role',
            'jabatan'  => 'nullable|string|max:50',
        ], [
            'username.unique'    => 'Username sudah digunakan.',
            'password.min'       => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $data = [
            'nama'    => $request->nama,
            'username'=> $request->username,
            'id_role' => $request->id_role,
            'jabatan' => $request->jabatan,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $this->hubungkanKendaraanDriver($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Akun driver → hubungkan ke kendaraan yang nama driver-nya sama
     * tetapi belum terhubung ke akun, agar notifikasi tugas & jadwal
     * driver berjalan sama seperti driver lain.
     */
    private function hubungkanKendaraanDriver(User $user): void
    {
        if ((int) $user->id_role !== 3) {
            return;
        }

        Kendaraan::whereNull('driver_user_id')
            ->where('nama_driver', $user->nama)
            ->update(['driver_user_id' => $user->id_user]);

        // Nama akun diganti → samakan nama driver di kendaraannya
        Kendaraan::where('driver_user_id', $user->id_user)
            ->update(['nama_driver' => $user->nama]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id_user === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Tidak dapat menghapus akun yang sedang digunakan.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
