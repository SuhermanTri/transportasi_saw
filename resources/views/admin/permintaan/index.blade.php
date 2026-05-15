@extends('layouts.app')
@section('title', 'Data Permintaan')
@section('page-title', 'Data Permintaan')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.permintaan.index') }}"><i class="bi bi-card-list"></i>Data Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.kendaraan.index') }}"><i class="bi bi-truck"></i>Data Kendaraan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.ahp.index') }}"><i class="bi bi-calculator"></i>Proses AHP</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.saw.index') }}"><i class="bi bi-bar-chart"></i>Proses SAW</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.penjadwalan.index') }}"><i class="bi bi-calendar3"></i>Penjadwalan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.laporan.index') }}"><i class="bi bi-file-earmark-text"></i>Laporan</a></li>
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-card-list me-2"></i>Permintaan Aktif
            <span class="badge bg-primary ms-2">Hari ini &amp; besok</span>
            <span class="badge bg-secondary ms-1">Diurutkan berdasarkan ranking SAW</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Pemohon</th>
                        <th>Jabatan</th>
                        <th>Tujuan</th>
                        <th>Urgensi</th>
                        <th>Tgl Berangkat</th>
                        <th>Skor SAW</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permintaan as $p)
                    <tr class="{{ in_array($p->status, ['menunggu','menunggu_konfirmasi']) ? 'table-warning' : '' }}">
                        <td class="text-center fw-bold">
                            @if($p->hasilSaw)
                                <span class="badge bg-primary">{{ $p->hasilSaw->ranking }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $p->user->nama ?? '-' }}</td>
                        <td class="small text-muted">{{ $p->user->jabatan ?? '-' }}</td>
                        <td>{{ $p->tujuan }}</td>
                        <td>
                            @if($p->urgensi)
                                <span class="badge bg-info text-dark">{{ $p->urgensi->nama_urgensi }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $p->tanggal_berangkat->format('d M Y') }}</td>
                        <td class="text-center">
                            @if($p->hasilSaw)
                                <span class="text-success fw-semibold">{{ number_format($p->hasilSaw->nilai_akhir, 4) }}</span>
                            @else
                                <span class="text-muted small">belum dihitung</span>
                            @endif
                        </td>
                        <td>{!! $p->statusLabel !!}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.permintaan.show', $p->id_permintaan) }}" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a>
                            @if($p->status === 'menunggu')
                                <form method="POST" action="{{ route('admin.permintaan.approve', $p->id_permintaan) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-success btn-sm" title="Setujui" onclick="return confirm('Setujui permintaan ini?')"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <button class="btn btn-danger btn-sm" title="Tolak"
                                    data-bs-toggle="modal" data-bs-target="#modalTolak"
                                    data-id="{{ $p->id_permintaan }}" data-nama="{{ $p->tujuan }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            @elseif($p->status === 'menunggu_konfirmasi')
                                <a href="{{ route('admin.penjadwalan.create', ['permintaan' => $p->id_permintaan]) }}"
                                   class="btn btn-primary btn-sm" title="Jadwalkan Kendaraan">
                                    <i class="bi bi-calendar-plus"></i>
                                </a>
                                <button class="btn btn-danger btn-sm" title="Tolak"
                                    data-bs-toggle="modal" data-bs-target="#modalTolak"
                                    data-id="{{ $p->id_permintaan }}" data-nama="{{ $p->tujuan }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            @elseif($p->status === 'disetujui')
                                <button class="btn btn-danger btn-sm" title="Tolak"
                                    data-bs-toggle="modal" data-bs-target="#modalTolak"
                                    data-id="{{ $p->id_permintaan }}" data-nama="{{ $p->tujuan }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada permintaan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tolak --}}
<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tolak Permintaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTolak" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Tolak permintaan: <strong id="tolakNama"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Alasan penolakan (opsional)</label>
                        <textarea name="alasan" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Permintaan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('modalTolak').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    const id = btn.dataset.id;
    const nama = btn.dataset.nama;
    document.getElementById('tolakNama').textContent = nama;
    document.getElementById('formTolak').action = '/admin/permintaan/' + id + '/reject';
});
</script>
@endpush
@endsection
