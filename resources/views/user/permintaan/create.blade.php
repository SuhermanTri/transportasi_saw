@extends('layouts.app')
@section('title', 'Ajukan Permintaan')
@section('page-title', 'Ajukan Permintaan Kendaraan')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('user.permintaan.create') }}"><i class="bi bi-plus-circle"></i>Ajukan Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.index') }}"><i class="bi bi-card-list"></i>Riwayat Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-circle me-2"></i>Form Permintaan Kendaraan Operasional</h6>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('user.permintaan.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-medium">Tujuan Perjalanan <span class="text-danger">*</span></label>
                    <input type="text" name="tujuan" class="form-control @error('tujuan') is-invalid @enderror"
                           value="{{ old('tujuan') }}"
                           placeholder="cth: Kantor Pusat PT Maju Bersama, Jakarta Selatan" required>
                    @error('tujuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">Tingkat Urgensi <span class="text-danger">*</span></label>
                    <select name="urgensi_id" class="form-select @error('urgensi_id') is-invalid @enderror" required>
                        <option value="">-- Pilih tingkat urgensi --</option>
                        @foreach($urgensiList as $u)
                            <option value="{{ $u->id }}" {{ old('urgensi_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->nama_urgensi }}
                                @if($u->keterangan) — {{ $u->keterangan }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('urgensi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Tanggal Berangkat <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_berangkat" class="form-control @error('tanggal_berangkat') is-invalid @enderror"
                           value="{{ old('tanggal_berangkat') }}"
                           min="{{ today()->format('Y-m-d') }}"
                           max="{{ today()->addDay()->format('Y-m-d') }}" required>
                    @error('tanggal_berangkat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text"><i class="bi bi-info-circle me-1"></i>Hanya dapat diajukan untuk hari ini atau besok.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Jam Berangkat <span class="text-danger">*</span></label>
                    <input type="time" name="jam_berangkat" class="form-control @error('jam_berangkat') is-invalid @enderror"
                           value="{{ old('jam_berangkat') }}" required>
                    @error('jam_berangkat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Tanggal Kembali</label>
                    <input type="date" name="tanggal_kembali" class="form-control @error('tanggal_kembali') is-invalid @enderror"
                           value="{{ old('tanggal_kembali') }}">
                    @error('tanggal_kembali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Jam Kembali (perkiraan)</label>
                    <input type="time" name="jam_kembali" class="form-control @error('jam_kembali') is-invalid @enderror"
                           value="{{ old('jam_kembali') }}">
                    @error('jam_kembali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-medium">Jumlah Penumpang <span class="text-danger">*</span></label>
                    <input type="number" name="jumlah_penumpang" class="form-control @error('jumlah_penumpang') is-invalid @enderror"
                           value="{{ old('jumlah_penumpang', 1) }}" min="1" max="50" required>
                    @error('jumlah_penumpang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">Keterangan (Opsional)</label>
                    <textarea name="keterangan" class="form-control" rows="3"
                              placeholder="Tambahkan keterangan jika perlu...">{{ old('keterangan') }}</textarea>
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Kirim Permintaan</button>
                <a href="{{ route('user.permintaan.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
