@extends('layouts.app')
@section('title', 'Riwayat Permintaan')
@section('page-title', 'Riwayat Permintaan Saya')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.create') }}"><i class="bi bi-plus-circle"></i>Ajukan Permintaan</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('user.permintaan.index') }}"><i class="bi bi-card-list"></i>Riwayat Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-card-list me-2"></i>Riwayat Permintaan Kendaraan</h6>
        <a href="{{ route('user.permintaan.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Ajukan Baru</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tgl Berangkat</th>
                        <th>Tujuan</th>
                        <th>Urgensi</th>
                        <th>Penumpang</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permintaan as $i => $p)
                    @php $menungguKonfirmasi = $p->jadwal?->status_perjalanan === 'selesai_driver'; @endphp
                    <tr class="{{ $menungguKonfirmasi ? 'table-warning' : '' }}">
                        <td>{{ $i + 1 }}</td>
                        <td class="text-nowrap">{{ $p->tanggal_berangkat->format('d M Y') }}</td>
                        <td>{{ $p->tujuan }}</td>
                        <td>
                            @if($p->urgensi)
                                <span class="badge bg-info text-dark">{{ $p->urgensi->nama_urgensi }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>{{ $p->jumlah_penumpang }}</td>
                        <td>
                            @if($menungguKonfirmasi)
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu Konfirmasi Anda</span>
                            @else
                                {!! $p->statusLabel !!}
                            @endif
                            @if($p->catatan_dispatcher)
                                {!! $p->feedbackLabel !!}
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('user.permintaan.show', $p->id_permintaan) }}" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a>
                            @if($menungguKonfirmasi)
                                <a href="{{ route('user.penilaian.create', $p->jadwal->id_jadwal) }}" class="btn btn-warning btn-sm">
                                    <i class="bi bi-check2-circle me-1"></i>Konfirmasi
                                </a>
                            @endif
                            @if($p->status === 'menunggu')
                                <a href="{{ route('user.permintaan.edit', $p->id_permintaan) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('user.permintaan.destroy', $p->id_permintaan) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Batalkan permintaan ini?')"><i class="bi bi-x-circle"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada permintaan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
