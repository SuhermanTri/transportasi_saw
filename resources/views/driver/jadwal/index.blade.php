@extends('layouts.app')
@section('title', 'Jadwal Saya')
@section('page-title', 'Jadwal Perjalanan Saya')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('driver.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('driver.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('driver.riwayat') }}"><i class="bi bi-clock-history"></i>Riwayat Perjalanan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')

{{-- Header info hari ini --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-0">Jadwal Aktif</h5>
        <small class="text-muted">{{ now()->translatedFormat('l, d F Y') }}</small>
    </div>
    <span class="badge bg-primary fs-6 px-3 py-2">{{ $jadwal->count() }} Tugas</span>
</div>

@forelse($jadwal as $j)
@php
    $pemohon   = $j->permintaan?->user?->nama    ?? '-';
    $jabatan   = $j->permintaan?->user?->jabatan ?? '';
    $tujuan    = $j->permintaan?->tujuan         ?? '-';
    $urgensi   = $j->permintaan?->urgensi?->nama_urgensi ?? '';
    $penumpang = $j->permintaan?->jumlah_penumpang ?? '-';
    $kendaraan = $j->kendaraan?->nama_kendaraan    ?? '-';
    $nopol     = $j->kendaraan?->no_polisi         ?? '';

    $spColor = match($j->status_perjalanan) {
        'selesai_driver' => 'warning',
        'selesai_final'  => 'success',
        default          => 'primary',
    };
    $spLabel = match($j->status_perjalanan) {
        'selesai_driver' => 'Menunggu Konfirmasi',
        'selesai_final'  => 'Selesai',
        default          => 'Aktif',
    };
@endphp

<div class="card mb-3 border-start border-{{ $spColor }} border-3">
    <div class="card-body">

        {{-- Baris 1: Status + Tanggal + Tombol --}}
        <div class="d-flex align-items-start justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-{{ $spColor }}">{{ $spLabel }}</span>
                @if($urgensi)
                    <span class="badge bg-secondary"><i class="bi bi-flag-fill me-1"></i>{{ $urgensi }}</span>
                @endif
                <span class="text-muted small">
                    <i class="bi bi-calendar2 me-1"></i>
                    {{ \Carbon\Carbon::parse($j->tanggal)->format('d M Y') }}
                    &nbsp;·&nbsp;
                    <i class="bi bi-clock me-1"></i>
                    {{ \Illuminate\Support\Str::substr($j->jam_mulai, 0, 5) }} –
                    {{ \Illuminate\Support\Str::substr($j->jam_selesai, 0, 5) }}
                </span>
            </div>
            <a href="{{ route('driver.jadwal.show', $j->id_jadwal) }}"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye me-1"></i>Detail
            </a>
        </div>

        {{-- Baris 2: 4 kolom info --}}
        <div class="row g-3">

            {{-- 1. Tujuan perjalanan --}}
            <div class="col-sm-6 col-lg-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="flex-shrink-0 mt-1">
                        <i class="bi bi-geo-alt-fill text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Tujuan</div>
                        <div class="fw-semibold">{{ $tujuan }}</div>
                    </div>
                </div>
            </div>

            {{-- 2. Pemesan --}}
            <div class="col-sm-6 col-lg-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="flex-shrink-0 mt-1">
                        <i class="bi bi-person-fill text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Pemesan</div>
                        <div class="fw-semibold">{{ $pemohon }}</div>
                        @if($jabatan)
                            <div class="text-muted small">{{ $jabatan }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 3. Kendaraan --}}
            <div class="col-sm-6 col-lg-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="flex-shrink-0 mt-1">
                        <i class="bi bi-truck-front-fill text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Kendaraan</div>
                        <div class="fw-semibold">{{ $kendaraan }}</div>
                        @if($nopol)
                            <div class="text-muted small"><code>{{ $nopol }}</code></div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 4. Penumpang --}}
            <div class="col-sm-6 col-lg-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="flex-shrink-0 mt-1">
                        <i class="bi bi-people-fill text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Penumpang</div>
                        <div class="fw-semibold">{{ $penumpang }} orang</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Pesan aksi jika menunggu konfirmasi --}}
        @if($j->status_perjalanan === 'selesai_driver')
        <div class="alert alert-warning py-2 mb-0 mt-3 d-flex align-items-center gap-2">
            <i class="bi bi-hourglass-split"></i>
            <span class="small">Menunggu konfirmasi selesai dari pemohon.</span>
        </div>
        @endif

    </div>
</div>

@empty
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-calendar2-x text-muted" style="font-size:3rem"></i>
        <div class="mt-3 fw-semibold text-muted">Tidak ada jadwal aktif</div>
        <div class="text-muted small mt-1">Jadwal perjalanan yang ditugaskan akan muncul di sini.</div>
    </div>
</div>
@endforelse

@endsection
