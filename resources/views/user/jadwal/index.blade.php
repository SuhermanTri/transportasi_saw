@extends('layouts.app')
@section('title', 'Jadwal Saya')
@section('page-title', 'Jadwal Kendaraan Saya')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.create') }}"><i class="bi bi-plus-circle"></i>Ajukan Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.index') }}"><i class="bi bi-card-list"></i>Riwayat Permintaan</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar3 me-2"></i>Jadwal Penggunaan Kendaraan Saya</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>No</th><th>Tanggal</th><th>Tujuan</th><th>Kendaraan</th><th>Driver</th><th>Jam</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($jadwal as $i => $j)
                    @php $menungguKonfirmasi = $j->status_perjalanan === 'selesai_driver'; @endphp
                    <tr class="{{ $menungguKonfirmasi ? 'table-warning' : '' }}">
                        <td>{{ $i + 1 }}</td>
                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($j->tanggal)->format('d M Y') }}</td>
                        <td>{{ $j->permintaan->tujuan ?? '-' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $j->kendaraan->nama_kendaraan ?? '-' }}</div>
                            <div class="text-muted small">{{ $j->kendaraan->no_polisi ?? '' }}</div>
                        </td>
                        <td>{{ $j->kendaraan->nama_driver ?? '-' }}</td>
                        <td class="text-nowrap">{{ $j->jam_mulai }} – {{ $j->jam_selesai }}</td>
                        <td>
                            @if($menungguKonfirmasi)
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu Konfirmasi</span>
                            @elseif($j->status_perjalanan === 'selesai_final')
                                <span class="badge bg-secondary"><i class="bi bi-flag-fill me-1"></i>Selesai</span>
                            @elseif($j->status === 'aktif')
                                <span class="badge bg-success"><i class="bi bi-car-front me-1"></i>Aktif</span>
                            @elseif($j->status === 'batal')
                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Batal</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($j->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($menungguKonfirmasi)
                                <a href="{{ route('user.penilaian.create', $j->id_jadwal) }}"
                                   class="btn btn-warning btn-sm">
                                    <i class="bi bi-check2-circle me-1"></i>Konfirmasi
                                </a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada jadwal kendaraan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
