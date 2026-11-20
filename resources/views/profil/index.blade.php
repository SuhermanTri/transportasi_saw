@extends('layouts.app')
@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')

{{-- Menu samping sesuai peran (dispatcher memakai admin.partials.sidebar dari layout) --}}
@section('sidebar-menu')
    @php $role = auth()->user()->role?->nama_role; @endphp
    @if($role === 'user')
        <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.create') }}"><i class="bi bi-plus-circle"></i>Ajukan Permintaan</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.index') }}"><i class="bi bi-card-list"></i>Riwayat Permintaan</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
    @elseif($role === 'driver')
        <li class="nav-item"><a class="nav-link" href="{{ route('driver.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('driver.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('driver.riwayat') }}"><i class="bi bi-clock-history"></i>Riwayat Perjalanan</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
    @endif
@endsection

@section('content')
@php $peran = $user->role?->nama_role; @endphp
<div class="row g-3">

    {{-- ── Data Diri (hanya tampil) ─────────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-person-badge me-2"></i>Data Diri</h6>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">@include('partials.avatar', ['user' => $user, 'size' => 96])</div>
                <div class="fw-semibold fs-5">{{ $user->nama }}</div>
                <div class="text-muted small mb-3"><code>{{ $user->username }}</code></div>
                <table class="table table-sm text-start mb-2">
                    <tr><th class="text-muted fw-normal" style="width:40%">Peran</th><td><span class="badge bg-primary">{{ ucfirst($peran ?? '-') }}</span></td></tr>
                    <tr><th class="text-muted fw-normal">Jabatan</th><td>{{ $user->jabatan ?: '—' }}</td></tr>
                    <tr><th class="text-muted fw-normal">No. HP</th><td>{{ $user->no_hp ?: '—' }}</td></tr>
                    @if($peran === 'driver')
                    <tr><th class="text-muted fw-normal">Kendaraan</th><td>{{ $user->kendaraan ? $user->kendaraan->nama_kendaraan . ' (' . $user->kendaraan->no_polisi . ')' : '—' }}</td></tr>
                    @endif
                </table>
                <div class="form-text text-start">
                    <i class="bi bi-info-circle me-1"></i>Jabatan memengaruhi prioritas SAW dan hanya dapat diubah oleh Dispatcher.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        {{-- ── Foto & Kontak ─────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-image me-2"></i>Foto &amp; Kontak</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-medium">Foto Profil</label>
                            <input type="file" name="foto" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                   class="form-control @error('foto') is-invalid @enderror">
                            @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">JPG/JPEG/PNG, maksimal 2 MB.</div>
                            @if($user->foto_url)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapusFoto">
                                <label class="form-check-label" for="hapusFoto">Hapus foto</label>
                            </div>
                            @endif
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-medium">No. HP</label>
                            <input type="text" name="no_hp" maxlength="20"
                                   class="form-control @error('no_hp') is-invalid @enderror"
                                   value="{{ old('no_hp', $user->no_hp) }}" placeholder="cth: 0812-3456-7890">
                            @error('no_hp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if($peran === 'driver')
                            <div class="form-text">Nomor ini juga dipakai sebagai No. HP driver pada data kendaraan.</div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Profil</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Ganti Password ─────────────────────────────────────────── --}}
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-key me-2"></i>Ganti Password</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profil.password') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Password Lama</label>
                            <input type="password" name="password_lama" autocomplete="current-password"
                                   class="form-control @error('password_lama') is-invalid @enderror" required>
                            @error('password_lama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Password Baru</label>
                            <input type="password" name="password" autocomplete="new-password" minlength="6"
                                   class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" autocomplete="new-password"
                                   class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-warning"><i class="bi bi-shield-lock me-1"></i>Ganti Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
