@extends('layouts.app')
@section('title', 'Edit Jadwal')
@section('page-title', 'Edit Status Jadwal')

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
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Edit Jadwal #{{ $jadwal->id_jadwal }}</h6>
        <a href="{{ route('admin.penjadwalan.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
    <div class="card-body">

        {{-- Info jadwal (read-only) --}}
        <div class="alert alert-light border mb-4">
            <div class="row g-2 small">
                <div class="col-md-6">
                    <span class="text-muted">Pemohon:</span>
                    <strong class="ms-1">{{ $jadwal->permintaan->user->nama ?? '-' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted">Tujuan:</span>
                    <strong class="ms-1">{{ $jadwal->permintaan->tujuan ?? '-' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted">Kendaraan:</span>
                    <strong class="ms-1">{{ $jadwal->kendaraan->nama_kendaraan ?? '-' }} ({{ $jadwal->kendaraan->no_polisi ?? '' }})</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted">Jadwal:</span>
                    <strong class="ms-1">{{ \Carbon\Carbon::parse($jadwal->tanggal)->format('d M Y') }}, {{ $jadwal->jam_mulai }} – {{ $jadwal->jam_selesai }}</strong>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.penjadwalan.update', $jadwal->id_jadwal) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Status Jadwal</label>
                <select name="status" class="form-select" id="statusSelect">
                    <option value="aktif"  {{ $jadwal->status === 'aktif'  ? 'selected' : '' }}>Aktif</option>
                    <option value="selesai"{{ $jadwal->status === 'selesai'? 'selected' : '' }}>Selesai</option>
                    <option value="batal"  {{ $jadwal->status === 'batal'  ? 'selected' : '' }}>Batal</option>
                </select>
            </div>

            <div class="mb-3" id="catatanSection" style="display:none">
                <label class="form-label fw-semibold">
                    Keterangan Pembatalan untuk Pemohon
                    <span class="text-muted fw-normal small">(opsional — akan tampil di dashboard &amp; detail permintaan user)</span>
                </label>
                <textarea name="catatan_dispatcher" class="form-control" rows="3"
                    placeholder="Contoh: Jadwal dibatalkan karena kendaraan mengalami gangguan teknis.">{{ old('catatan_dispatcher') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update</button>
                <a href="{{ route('admin.penjadwalan.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div></div>

@push('scripts')
<script>
(function () {
    const sel = document.getElementById('statusSelect');
    const sec = document.getElementById('catatanSection');
    function toggle() { sec.style.display = sel.value === 'batal' ? '' : 'none'; }
    sel.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush
@endsection
