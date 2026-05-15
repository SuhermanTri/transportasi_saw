@extends('layouts.app')
@section('title', 'Penjadwalan')
@section('page-title', 'Penjadwalan Kendaraan')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.permintaan.index') }}"><i class="bi bi-card-list"></i>Data Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.kendaraan.index') }}"><i class="bi bi-truck"></i>Data Kendaraan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.ahp.index') }}"><i class="bi bi-calculator"></i>Proses AHP</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.saw.index') }}"><i class="bi bi-bar-chart"></i>Proses SAW</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.penjadwalan.index') }}"><i class="bi bi-calendar3"></i>Penjadwalan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.laporan.index') }}"><i class="bi bi-file-earmark-text"></i>Laporan</a></li>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Permintaan Siap Dijadwalkan (sudah disetujui, berurut ranking SAW) --}}
@if($permintaanSiap->isNotEmpty())
<div class="card border-primary mb-4">
    <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-stars me-2"></i>Permintaan Siap Dijadwalkan (Urutan Prioritas SAW)</h6>
        <a href="{{ route('admin.penjadwalan.create') }}" class="btn btn-light btn-sm"><i class="bi bi-plus me-1"></i>Buat Jadwal</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Rank SAW</th>
                        <th>Pemohon</th>
                        <th>Jabatan</th>
                        <th>Tujuan</th>
                        <th>Urgensi</th>
                        <th>Tgl Berangkat</th>
                        <th>Skor SAW</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permintaanSiap as $p)
                    <tr>
                        <td class="text-center">
                            @if($p->hasilSaw)
                                <span class="badge bg-primary">#{{ $p->hasilSaw->ranking }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $p->user->nama ?? '-' }}</td>
                        <td class="small text-muted">{{ $p->user->jabatan ?? '-' }}</td>
                        <td>{{ $p->tujuan }}</td>
                        <td>
                            @if($p->urgensi)
                                <span class="badge bg-info text-dark">{{ $p->urgensi->nama_urgensi }}</span>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $p->tanggal_berangkat->format('d M Y') }}</td>
                        <td>
                            @if($p->hasilSaw)
                                <span class="text-success fw-semibold">{{ number_format($p->hasilSaw->nilai_akhir, 4) }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.penjadwalan.create') }}?permintaan={{ $p->id_permintaan }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-calendar-plus me-1"></i>Jadwalkan
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Daftar Jadwal --}}
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar3 me-2"></i>Daftar Jadwal Kendaraan</h6>
        <a href="{{ route('admin.penjadwalan.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Buat Jadwal</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Pemohon</th>
                        <th>Tujuan</th>
                        <th>Kendaraan</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jadwal as $i => $j)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $j->permintaan->user->nama ?? '-' }}</td>
                        <td>{{ $j->permintaan->tujuan ?? '-' }}</td>
                        <td>
                            <div>{{ $j->kendaraan->nama_kendaraan ?? '-' }}</div>
                            <small class="text-muted">{{ $j->kendaraan->no_polisi ?? '' }}</small>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($j->tanggal)->format('d M Y') }}</td>
                        <td class="text-nowrap">{{ $j->jam_mulai }} – {{ $j->jam_selesai }}</td>
                        <td>
                            <span class="badge bg-{{ $j->status === 'aktif' ? 'success' : ($j->status === 'selesai' ? 'secondary' : 'danger') }}">
                                {{ ucfirst($j->status) }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.penjadwalan.edit', $j->id_jadwal) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                            <button class="btn btn-danger btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#modalHapusJadwal"
                                    data-action="{{ route('admin.penjadwalan.destroy', $j->id_jadwal) }}"
                                    data-pemohon="{{ $j->permintaan->user->nama ?? '' }}"
                                    data-tujuan="{{ $j->permintaan->tujuan ?? '' }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada jadwal</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Hapus Jadwal dengan Catatan --}}
<div class="modal fade" id="modalHapusJadwal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-trash me-2"></i>Hapus / Batalkan Jadwal</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formHapusJadwal" method="POST">
                @csrf @method('DELETE')
                <div class="modal-body">
                    <p class="mb-3">
                        Hapus jadwal <strong id="hapusTujuan"></strong>
                        (<span id="hapusPemohon" class="text-muted small"></span>)?
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Pembatalan</label>
                        <select name="feedback_type" class="form-select">
                            <option value="dijadwalkan_ulang">Dijadwalkan Ulang — kendaraan/waktu akan diubah</option>
                            <option value="dioverride">Dialihkan — kendaraan dibutuhkan permintaan prioritas lebih tinggi</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">
                            Keterangan untuk Pemohon
                            <span class="text-muted fw-normal small">(opsional)</span>
                        </label>
                        <textarea name="catatan_dispatcher" class="form-control" rows="3"
                            placeholder="Contoh: Kendaraan dialihkan karena kebutuhan operasional mendesak dengan urgensi lebih tinggi."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Hapus Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('modalHapusJadwal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    document.getElementById('formHapusJadwal').action = btn.dataset.action;
    document.getElementById('hapusPemohon').textContent = btn.dataset.pemohon;
    document.getElementById('hapusTujuan').textContent  = btn.dataset.tujuan;
});
</script>
@endpush

@endsection
