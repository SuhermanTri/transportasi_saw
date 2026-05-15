@extends('layouts.app')
@section('title', 'Dashboard Driver')
@section('page-title', 'Dashboard Driver')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link active" href="{{ route('driver.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('driver.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('driver.riwayat') }}"><i class="bi bi-clock-history"></i>Riwayat Perjalanan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-calendar-day"></i></div>
                <div>
                    <div class="text-muted small">Jadwal Hari Ini</div>
                    <div class="fs-3 fw-bold">{{ $jadwalHariIni }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="text-muted small">Perjalanan Selesai</div>
                    <div class="fs-3 fw-bold">{{ $perjalananSelesai }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-arrow-right-circle"></i></div>
                <div>
                    <div class="text-muted small">Perjalanan Berjalan</div>
                    <div class="fs-3 fw-bold">{{ $perjalananBerjalan }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-list-check"></i></div>
                <div>
                    <div class="text-muted small">Total Jadwal</div>
                    <div class="fs-3 fw-bold">{{ $totalJadwal }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar-day me-2"></i>Jadwal Hari Ini
                    <span class="text-primary ms-2">{{ now()->format('d M Y') }}</span>
                </h6>
            </div>
            <div class="card-body">
                @forelse($jadwalHariIniList as $j)
                <div class="border rounded p-3 mb-2">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="fw-semibold">{{ $j->permintaan->tujuan ?? '-' }}</div>
                            <div class="text-muted small">
                                <i class="bi bi-truck me-1"></i>{{ $j->kendaraan->nama_kendaraan ?? '-' }}
                                ({{ $j->kendaraan->no_polisi ?? '' }})
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-people me-1"></i>{{ $j->permintaan->jumlah_penumpang ?? '-' }} penumpang
                            </div>
                        </div>
                        <div class="col-md-5 text-end">
                            <div class="fw-semibold">{{ $j->jam_mulai }} – {{ $j->jam_selesai }}</div>
                            <span class="badge bg-{{ $j->status === 'aktif' ? 'success' : 'secondary' }}">{{ ucfirst($j->status) }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                    Tidak ada jadwal hari ini
                </div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        {{-- Status Driver --}}
        @php
            $statusColor = match(auth()->user()->status_driver) {
                'tersedia'   => 'success',
                'cuti'       => 'info',
                'sakit'      => 'danger',
                'dinas_luar' => 'warning',
                'istirahat'  => 'secondary',
                default      => 'secondary',
            };
            $statusLabels = [
                'tersedia'   => 'Tersedia',
                'cuti'       => 'Cuti',
                'sakit'      => 'Sakit',
                'dinas_luar' => 'Dinas Luar',
                'istirahat'  => 'Istirahat',
            ];
        @endphp
        <div class="card mb-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-person-check me-2"></i>Status Saya</h6>
                <span class="badge bg-{{ $statusColor }}">{{ $statusLabels[auth()->user()->status_driver] ?? '-' }}</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('driver.status.update') }}" class="d-flex gap-2">
                    @csrf
                    <select name="status_driver" class="form-select form-select-sm">
                        @foreach($statusLabels as $val => $label)
                            <option value="{{ $val }}" {{ auth()->user()->status_driver === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-sm">Perbarui</button>
                </form>
                <div class="text-muted small mt-2">Total perjalanan: <strong>{{ auth()->user()->jumlah_perjalanan }}</strong></div>
            </div>
        </div>

        @if($kendaraan)
        <div class="card mb-3">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-truck me-2"></i>Kendaraan Saya</h6>
            </div>
            <div class="card-body">
                <div class="fw-bold fs-5">{{ $kendaraan->nama_kendaraan }}</div>
                <div class="text-muted">{{ $kendaraan->no_polisi }}</div>
                <hr>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="text-muted small">Kapasitas</div>
                        <div class="fw-bold">{{ $kendaraan->kapasitas }} org</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Tahun</div>
                        <div class="fw-bold">{{ $kendaraan->tahun }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Status</div>
                        <span class="badge bg-{{ $kendaraan->status === 'tersedia' ? 'success' : 'danger' }}">{{ ucfirst($kendaraan->status) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Perjalanan</h6>
                <a href="{{ route('driver.riwayat') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @forelse($riwayatTerbaru as $r)
                <div class="p-3 border-bottom">
                    <div class="fw-semibold small">{{ $r->permintaan->tujuan ?? '-' }}</div>
                    <div class="text-muted small">{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }} | {{ $r->jam_mulai }}</div>
                </div>
                @empty
                <div class="text-center text-muted py-3">Belum ada riwayat</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
