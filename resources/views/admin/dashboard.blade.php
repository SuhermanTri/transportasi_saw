@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Dispatcher')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.permintaan.index') }}"><i class="bi bi-card-list"></i>Data Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.kendaraan.index') }}"><i class="bi bi-truck"></i>Data Kendaraan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.ahp.index') }}"><i class="bi bi-calculator"></i>Proses AHP</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.saw.index') }}"><i class="bi bi-bar-chart"></i>Proses SAW</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.penjadwalan.index') }}"><i class="bi bi-calendar3"></i>Penjadwalan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.laporan.index') }}"><i class="bi bi-file-earmark-text"></i>Laporan</a></li>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-card-list"></i></div>
                <div>
                    <div class="text-muted small">Permintaan Hari Ini</div>
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
                    <div class="text-muted small">Permintaan Pending</div>
                    <div class="fs-3 fw-bold">{{ $permintaanPending }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-truck"></i></div>
                <div>
                    <div class="text-muted small">Kendaraan Tersedia</div>
                    <div class="fs-3 fw-bold">{{ $kendaraanTersedia }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="text-muted small">Kendaraan Digunakan</div>
                    <div class="fs-3 fw-bold">{{ $kendaraanDigunakan }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-card-list me-2"></i>Permintaan Masuk</h6>
                <a href="{{ route('admin.permintaan.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Pemohon</th>
                                <th>Tujuan</th>
                                <th>Tanggal</th>
                                <th>Urgensi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($permintaanTerbaru as $i => $p)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $p->user->nama ?? '-' }}</td>
                                <td>{{ $p->tujuan }}</td>
                                <td>{{ $p->tanggal_berangkat->format('d M Y') }}</td>
                                <td>
                                    @if($p->urgensi)
                                        <span class="badge bg-info text-dark small">{{ $p->urgensi->nama_urgensi }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->status === 'menunggu')
                                        <form method="POST" action="{{ route('admin.permintaan.approve', $p->id_permintaan) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.permintaan.reject', $p->id_permintaan) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    @else
                                        {!! $p->statusLabel !!}
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada permintaan</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar3 me-2"></i>Jadwal Hari Ini</h6>
                <a href="{{ route('admin.penjadwalan.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @forelse($jadwalHariIniList as $j)
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fw-semibold">{{ $j->kendaraan->nama_kendaraan ?? '-' }}</div>
                            <div class="text-muted small">{{ $j->permintaan->tujuan ?? '-' }}</div>
                            <div class="text-muted small">{{ $j->jam_mulai }} - {{ $j->jam_selesai }}</div>
                        </div>
                        <span class="badge bg-{{ $j->status === 'aktif' ? 'success' : ($j->status === 'selesai' ? 'secondary' : 'danger') }} align-self-start">
                            {{ ucfirst($j->status) }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">Tidak ada jadwal hari ini</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
