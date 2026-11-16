@extends('layouts.app')
@section('title', 'Detail Jadwal')
@section('page-title', 'Detail Tugas Perjalanan')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('driver.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('driver.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('driver.riwayat') }}"><i class="bi bi-clock-history"></i>Riwayat Perjalanan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Detail Tugas Jadwal #{{ $jadwal->id_jadwal }}</h6>
        <a href="{{ route('driver.jadwal') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <div class="fw-semibold fs-5">{{ $jadwal->permintaan->tujuan ?? '-' }}</div>
                    <div class="mt-1">
                        @include('partials.badge_perjalanan', ['jadwal' => $jadwal])
                        <span class="ms-1">{{ \Carbon\Carbon::parse($jadwal->tanggal)->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3"><i class="bi bi-truck me-2"></i>Kendaraan</h6>
                        <div class="fw-bold fs-5">{{ $jadwal->kendaraan->nama_kendaraan ?? '-' }}</div>
                        <div class="text-muted">{{ $jadwal->kendaraan->no_polisi ?? '' }}</div>
                        <div class="mt-2">
                            <span class="badge bg-secondary">{{ $jadwal->kendaraan->kapasitas ?? '-' }} orang</span>
                            <span class="badge bg-secondary">{{ $jadwal->kendaraan->warna ?? '' }}</span>
                            <span class="badge bg-secondary">{{ $jadwal->kendaraan->tahun ?? '' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3"><i class="bi bi-clock me-2"></i>Waktu Perjalanan</h6>
                        <div class="fw-bold fs-5">{{ $jadwal->jam_mulai }} – {{ $jadwal->jam_selesai }}</div>
                        <div class="text-muted">{{ \Carbon\Carbon::parse($jadwal->tanggal)->format('l, d M Y') }}</div>
                        <div class="mt-2 text-muted">
                            <i class="bi bi-people me-1"></i>{{ $jadwal->permintaan->jumlah_penumpang ?? '-' }} penumpang
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <label class="text-muted small">Pemohon</label>
                <div class="fw-semibold">{{ $jadwal->permintaan->user->nama ?? '-' }}</div>
                <div class="text-muted small">{{ $jadwal->permintaan->user->jabatan ?? '' }}</div>
            </div>
            <div class="col-md-6">
                <label class="text-muted small">Urgensi</label>
                <div class="fw-semibold">{{ $jadwal->permintaan->urgensi->nama_urgensi ?? '—' }}</div>
            </div>

            {{-- Penilaian yang sudah diberikan --}}
            @if($jadwal->penilaian)
            <div class="col-12">
                <div class="alert alert-success py-2">
                    <i class="bi bi-star-fill text-warning me-1"></i>
                    Rating dari pemohon: <strong>{{ $jadwal->penilaian->nilai }}/5</strong>
                    @if($jadwal->penilaian->komentar)
                        — "{{ $jadwal->penilaian->komentar }}"
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Tombol selesai tugas --}}
        @if($jadwal->status === 'aktif' && in_array($jadwal->status_perjalanan, ['aktif', 'berjalan']))
        <hr>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @include('driver.partials.aksi_perjalanan', ['jadwal' => $jadwal, 'ukuran' => ''])
            <span class="text-muted small">
                @if($jadwal->status_perjalanan === 'aktif')
                    Tekan saat berangkat — pemohon dan dispatcher akan diberi tahu.
                @else
                    Berangkat pukul {{ $jadwal->waktu_berangkat?->format('H:i') }} WIB. Tekan Selesai setelah tiba; pemohon akan diminta konfirmasi.
                @endif
            </span>
        </div>
        @elseif($jadwal->status_perjalanan === 'selesai_driver')
        <hr>
        <div class="alert alert-warning py-2">
            <i class="bi bi-hourglass-split me-1"></i>Menunggu konfirmasi dari pemohon…
        </div>
        @endif
    </div>
</div>
</div></div>
@endsection
