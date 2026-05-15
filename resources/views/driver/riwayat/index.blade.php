@extends('layouts.app')
@section('title', 'Riwayat Perjalanan')
@section('page-title', 'Riwayat Perjalanan')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('driver.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('driver.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('driver.riwayat') }}"><i class="bi bi-clock-history"></i>Riwayat Perjalanan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Semua Perjalanan</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>No</th><th>Tanggal</th><th>Tujuan</th><th>Kendaraan</th><th>Jam</th><th>Status</th><th>Rating</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($riwayat as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}</td>
                        <td>{{ $r->permintaan->tujuan ?? '-' }}</td>
                        <td>{{ $r->kendaraan->nama_kendaraan ?? '-' }}</td>
                        <td>{{ $r->jam_mulai }} – {{ $r->jam_selesai }}</td>
                        <td>
                            @php
                                $spColor = match($r->status_perjalanan) {
                                    'selesai_final'  => 'success',
                                    'selesai_driver' => 'warning',
                                    default          => 'primary',
                                };
                                $spText = match($r->status_perjalanan) {
                                    'selesai_final'  => 'Selesai',
                                    'selesai_driver' => 'Tunggu Konfirmasi',
                                    default          => 'Aktif',
                                };
                            @endphp
                            <span class="badge bg-{{ $spColor }}">{{ $spText }}</span>
                        </td>
                        <td>
                            @if($r->penilaian)
                                <span class="text-warning">
                                    @for($s=1;$s<=5;$s++)<i class="bi bi-star{{ $s <= $r->penilaian->nilai ? '-fill' : '' }}"></i>@endfor
                                </span>
                                <small class="text-muted">{{ $r->penilaian->nilai }}/5</small>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('driver.jadwal.show', $r->id_jadwal) }}" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada riwayat perjalanan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
