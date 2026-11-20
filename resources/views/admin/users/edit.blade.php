@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Edit User: <span class="text-primary">{{ $user->nama }}</span></h6>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user->id_user) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                           value="{{ old('nama', $user->nama) }}" required>
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                           value="{{ old('username', $user->username) }}" required>
                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="alert alert-info py-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Kosongkan kolom password jika tidak ingin mengubah password.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Password Baru</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           placeholder="Kosongkan jika tidak diubah">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" class="form-control"
                           placeholder="Ulangi password baru">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Role <span class="text-danger">*</span></label>
                    <select name="id_role" class="form-select @error('id_role') is-invalid @enderror" required>
                        <option value="">-- Pilih Role --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id_role }}"
                                    {{ old('id_role', $user->id_role) == $r->id_role ? 'selected' : '' }}>
                                {{ ucfirst($r->nama_role) }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Jabatan</label>
                    <input type="text" name="jabatan" class="form-control @error('jabatan') is-invalid @enderror"
                           value="{{ old('jabatan', $user->jabatan) }}"
                           placeholder="Contoh: Staff, Supervisor, Manager">
                    @error('jabatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Foto & kontak --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">Foto Profil</label>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        @include('partials.avatar', ['user' => $user, 'size' => 48])
                        <span class="text-muted small">{{ $user->foto_url ? 'Foto saat ini' : 'Belum ada foto' }}</span>
                    </div>
                    <input type="file" name="foto" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                           class="form-control @error('foto') is-invalid @enderror">
                    @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">JPG/JPEG/PNG, maksimal 2 MB. Kosongkan jika tidak diganti.</div>
                    @if($user->foto_url)
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapusFoto">
                        <label class="form-check-label" for="hapusFoto">Hapus foto</label>
                    </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">No. HP</label>
                    <input type="text" name="no_hp" maxlength="20" class="form-control @error('no_hp') is-invalid @enderror"
                           value="{{ old('no_hp', $user->no_hp) }}" placeholder="cth: 0812-3456-7890">
                    @error('no_hp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Untuk driver, nomor ini juga dipakai di data kendaraan.</div>
                </div>

                {{-- Info kendaraan jika driver --}}
                @if($user->role?->nama_role === 'driver')
                <div class="col-12">
                    <div class="alert alert-secondary py-2 mb-0">
                        <i class="bi bi-truck me-1"></i>
                        <strong>Kendaraan:</strong>
                        {{ $user->kendaraan?->nama_kendaraan ?? 'Belum ditugaskan ke kendaraan apapun.' }}
                        @if(!$user->kendaraan)
                            — Tugaskan melalui menu
                            <a href="{{ route('admin.kendaraan.index') }}">Data Kendaraan</a>.
                        @endif
                    </div>
                </div>
                @endif

            </div>
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Simpan Perubahan
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
