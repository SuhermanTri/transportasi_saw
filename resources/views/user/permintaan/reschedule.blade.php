@extends('layouts.app')
@section('title', 'Reschedule Booking')
@section('page-title', 'Reschedule Booking')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.create') }}"><i class="bi bi-plus-circle"></i>Ajukan Permintaan</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('user.permintaan.index') }}"><i class="bi bi-card-list"></i>Riwayat Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-repeat me-2"></i>Reschedule Booking #{{ $permintaan->id_permintaan }}</h6>
        <a href="{{ route('user.permintaan.show', $permintaan->id_permintaan) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
    <div class="card-body">

        {{-- Info penting --}}
        @if($permintaan->status === 'terjadwal')
        <div class="alert alert-warning d-flex gap-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
            <div>
                <div class="fw-semibold">Booking sudah terjadwal</div>
                <div class="small mt-1">
                    Mengubah jadwal akan <strong>membatalkan alokasi kendaraan saat ini</strong>.
                    Dispatcher akan menjadwalkan ulang kendaraan untuk tanggal baru Anda.
                </div>
            </div>
        </div>
        @endif

        {{-- Jadwal saat ini --}}
        <div class="bg-light rounded p-3 mb-4">
            <div class="text-muted small fw-semibold mb-2">JADWAL SAAT INI</div>
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="text-muted small">Tujuan</div>
                    <div class="fw-semibold">{{ $permintaan->tujuan }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">Tanggal Berangkat</div>
                    <div class="fw-semibold">
                        {{ $permintaan->tanggal_berangkat->format('d M Y') }}
                        <span class="text-muted fw-normal">pukul {{ $permintaan->jam_berangkat }}</span>
                    </div>
                </div>
                @if($permintaan->tanggal_kembali)
                <div class="col-md-6">
                    <div class="text-muted small">Tanggal Kembali</div>
                    <div class="fw-semibold">
                        {{ $permintaan->tanggal_kembali->format('d M Y') }}
                        @if($permintaan->jam_kembali)
                            <span class="text-muted fw-normal">pukul {{ $permintaan->jam_kembali }}</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('user.permintaan.reschedule.store', $permintaan->id_permintaan) }}">
            @csrf
            <div class="text-muted small fw-semibold mb-2">JADWAL BARU</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Tanggal Berangkat Baru <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_berangkat"
                           class="form-control @error('tanggal_berangkat') is-invalid @enderror"
                           value="{{ old('tanggal_berangkat', $permintaan->tanggal_berangkat->format('Y-m-d')) }}"
                           min="{{ today()->format('Y-m-d') }}" required>
                    @error('tanggal_berangkat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Jam Berangkat <span class="text-danger">*</span></label>
                    <input type="time" name="jam_berangkat"
                           class="form-control @error('jam_berangkat') is-invalid @enderror"
                           value="{{ old('jam_berangkat', $permintaan->jam_berangkat) }}" required>
                    @error('jam_berangkat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Tanggal Kembali</label>
                    <input type="date" name="tanggal_kembali"
                           class="form-control @error('tanggal_kembali') is-invalid @enderror"
                           value="{{ old('tanggal_kembali', $permintaan->tanggal_kembali?->format('Y-m-d')) }}">
                    @error('tanggal_kembali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Jam Kembali</label>
                    <input type="time" name="jam_kembali"
                           class="form-control @error('jam_kembali') is-invalid @enderror"
                           value="{{ old('jam_kembali', $permintaan->jam_kembali) }}">
                    @error('jam_kembali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">Alasan Reschedule <span class="text-danger">*</span></label>
                    <textarea name="alasan_reschedule"
                              class="form-control @error('alasan_reschedule') is-invalid @enderror"
                              rows="3" maxlength="500" required
                              placeholder="Contoh: Rapat dipindahkan, ada keperluan mendadak, dll.">{{ old('alasan_reschedule') }}</textarea>
                    @error('alasan_reschedule')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Alasan ini akan dicatat dan disampaikan ke Dispatcher.</div>
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning text-dark">
                    <i class="bi bi-arrow-repeat me-1"></i>Ajukan Reschedule
                </button>
                <a href="{{ route('user.permintaan.show', $permintaan->id_permintaan) }}" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
