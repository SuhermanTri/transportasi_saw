@extends('layouts.app')
@section('title', 'Batalkan Booking')
@section('page-title', 'Batalkan Booking')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.create') }}"><i class="bi bi-plus-circle"></i>Ajukan Permintaan</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('user.permintaan.index') }}"><i class="bi bi-card-list"></i>Riwayat Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="row justify-content-center"><div class="col-lg-7">

    {{-- Ringkasan Booking --}}
    <div class="card border-danger mb-4">
        <div class="card-header bg-danger text-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-slash-circle me-2"></i>Batalkan Booking #{{ $permintaan->id_permintaan }}</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-warning d-flex gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <div class="fw-semibold">Perhatian!</div>
                    <div class="mt-1 small">
                        Membatalkan booking akan <strong>membebaskan kendaraan</strong> yang sudah dialokasikan.
                        Anda dapat mengajukan permintaan baru setelah pembatalan.
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="text-muted small">Tujuan</div>
                    <div class="fw-semibold">{{ $permintaan->tujuan }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Status</div>
                    <div>{!! $permintaan->statusLabel !!}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">Tanggal Berangkat</div>
                    <div class="fw-semibold">
                        {{ $permintaan->tanggal_berangkat->format('d M Y') }}
                        <span class="text-muted fw-normal">pukul {{ $permintaan->jam_berangkat }}</span>
                    </div>
                </div>
                @if($permintaan->jadwal)
                <div class="col-md-6">
                    <div class="text-muted small">Kendaraan Dialokasikan</div>
                    <div class="fw-semibold">{{ $permintaan->jadwal->kendaraan->nama_kendaraan ?? '-' }}</div>
                    <div class="text-muted small">{{ $permintaan->jadwal->kendaraan->no_polisi ?? '' }}</div>
                </div>
                @endif
            </div>

            {{-- Form Pembatalan --}}
            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('user.permintaan.cancel.confirm', $permintaan->id_permintaan) }}"
                  onsubmit="return confirm('Yakin ingin membatalkan booking ini? Kendaraan akan dibebaskan kembali.')">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Alasan Pembatalan <span class="text-danger">*</span></label>
                    <textarea name="alasan_batal" class="form-control @error('alasan_batal') is-invalid @enderror"
                              rows="4" maxlength="500" required
                              placeholder="Contoh: Rapat dibatalkan, kondisi tidak memungkinkan, dll.">{{ old('alasan_batal') }}</textarea>
                    @error('alasan_batal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Alasan ini akan disampaikan ke Dispatcher.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-slash-circle me-1"></i>Konfirmasi Batalkan Booking
                    </button>
                    <a href="{{ route('user.permintaan.show', $permintaan->id_permintaan) }}" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>

</div></div>
@endsection
