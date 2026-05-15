@extends('layouts.app')
@section('title', 'Dashboard User')
@section('page-title', 'Dashboard User')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link active" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.create') }}"><i class="bi bi-plus-circle"></i>Ajukan Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.index') }}"><i class="bi bi-card-list"></i>Riwayat Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')

{{-- ── Banner feedback dispatcher ──────────────────────────────────── --}}
@if($permintaanFeedback->isNotEmpty())
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-megaphone-fill fs-4 flex-shrink-0 mt-1"></i>
        <div class="flex-grow-1">
            <div class="fw-semibold">Ada {{ $permintaanFeedback->count() }} pembaruan dari Dispatcher</div>
            <div class="small mt-1 text-muted">Permintaan berikut memiliki keterangan baru dari Dispatcher. Klik untuk melihat detail.</div>
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach($permintaanFeedback as $pf)
                <a href="{{ route('user.permintaan.show', $pf->id_permintaan) }}"
                   class="btn btn-sm btn-outline-danger">
                    @php $meta = \App\Models\Permintaan::$feedbackMeta[$pf->feedback_type] ?? ['icon' => 'bi-info-circle', 'label' => 'Info']; @endphp
                    <i class="bi {{ $meta['icon'] }} me-1"></i>
                    {{ $pf->tujuan }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Banner konfirmasi pending ────────────────────────────────────── --}}
@if($pendingKonfirmasi->isNotEmpty())
<div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0 mt-1"></i>
        <div class="flex-grow-1">
            <div class="fw-semibold">
                Anda memiliki {{ $pendingKonfirmasi->count() }} perjalanan yang menunggu konfirmasi
            </div>
            <div class="small mt-1">Driver telah menyelesaikan perjalanan. Mohon konfirmasi untuk menutup perjalanan.</div>
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach($pendingKonfirmasi as $j)
                <a href="{{ route('user.penilaian.create', $j->id_jadwal) }}"
                   class="btn btn-warning btn-sm">
                    <i class="bi bi-check2-circle me-1"></i>
                    Konfirmasi: {{ $j->permintaan->tujuan ?? 'Perjalanan #'.$j->id_jadwal }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Stat cards ────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-card-list"></i></div>
                <div>
                    <div class="text-muted small">Permintaan Saya</div>
                    <div class="fs-3 fw-bold">{{ $totalPermintaan }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="text-muted small">Menunggu Persetujuan</div>
                    <div class="fs-3 fw-bold">{{ $menunggu }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="text-muted small">Disetujui</div>
                    <div class="fs-3 fw-bold">{{ $disetujui }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100
            {{ $pendingKonfirmasi->isNotEmpty() ? 'border-warning' : '' }}">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-{{ $pendingKonfirmasi->isNotEmpty() ? 'warning' : 'info' }} bg-opacity-10 text-{{ $pendingKonfirmasi->isNotEmpty() ? 'warning' : 'info' }}">
                    <i class="bi bi-{{ $pendingKonfirmasi->isNotEmpty() ? 'hourglass-split' : 'calendar-check' }}"></i>
                </div>
                <div>
                    @if($pendingKonfirmasi->isNotEmpty())
                        <div class="text-muted small">Menunggu Konfirmasi</div>
                        <div class="fs-3 fw-bold text-warning">{{ $pendingKonfirmasi->count() }}</div>
                    @else
                        <div class="text-muted small">Jadwal Hari Ini</div>
                        <div class="fs-3 fw-bold">{{ $jadwalHariIni }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- ── Riwayat permintaan ─────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Permintaan Terbaru</h6>
                <a href="{{ route('user.permintaan.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>No</th><th>Tanggal</th><th>Tujuan</th><th>Urgensi</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($permintaanTerbaru as $i => $p)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="text-nowrap">{{ $p->tanggal_berangkat->format('d M Y') }}</td>
                            <td>{{ $p->tujuan }}</td>
                            <td>
                                @if($p->urgensi)
                                    <span class="badge bg-info text-dark small">{{ $p->urgensi->nama_urgensi }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>{!! $p->statusLabel !!}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada permintaan</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Jadwal hari ini ───────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar3 me-2"></i>Jadwal Kendaraan Hari Ini</h6>
            </div>
            <div class="card-body p-0">
                @forelse($jadwalSaya as $j)
                <div class="p-3 border-bottom">
                    <div class="fw-semibold">{{ $j->kendaraan->nama_kendaraan ?? '-' }}</div>
                    <div class="text-muted small">{{ $j->permintaan->tujuan ?? '-' }}</div>
                    <div class="text-muted small">{{ $j->jam_mulai }} – {{ $j->jam_selesai }}</div>
                </div>
                @empty
                <div class="text-center text-muted py-4">Tidak ada jadwal hari ini</div>
                @endforelse
            </div>
        </div>
        <div class="mt-3">
            <a href="{{ route('user.permintaan.create') }}" class="btn btn-primary w-100">
                <i class="bi bi-plus-circle me-2"></i>Ajukan Permintaan Baru
            </a>
        </div>
    </div>
</div>
@endsection
