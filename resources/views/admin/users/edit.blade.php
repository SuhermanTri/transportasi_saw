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

        <form method="POST" action="{{ route('admin.users.update', $user->id_user) }}">
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
