@extends('layouts.app')
@section('title', 'Laporan')
@section('page-title', 'Laporan Sistem Transportasi')

@section('content')

{{-- ═══════════════════════════════════════════════════════
     FILTER CARD
═══════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-funnel me-2 text-primary"></i>Filter Laporan
        </h6>
        @if(!$isDefault)
        <span class="badge rounded-pill text-bg-primary">
            <i class="bi bi-check-circle me-1"></i>Filter Aktif
        </span>
        @endif
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.laporan.index') }}" id="filterForm">

            {{-- Mode selector --}}
            <div class="mb-3">
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="_m" id="modeSingle" value="single"
                           {{ (!$dateFrom && !$dateTo) ? 'checked' : '' }} autocomplete="off">
                    <label class="btn btn-outline-primary" for="modeSingle">
                        <i class="bi bi-calendar-date me-1"></i>Tanggal Tunggal
                    </label>

                    <input type="radio" class="btn-check" name="_m" id="modeRange" value="range"
                           {{ ($dateFrom || $dateTo) ? 'checked' : '' }} autocomplete="off">
                    <label class="btn btn-outline-primary" for="modeRange">
                        <i class="bi bi-calendar-range me-1"></i>Rentang Tanggal
                    </label>
                </div>
            </div>

            {{-- ── Tanggal Tunggal ── --}}
            <div id="singleSection">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-sm-5 col-md-4 col-lg-3">
                        <label class="filter-label">Pilih Tanggal</label>
                        <input type="date" name="tanggal" id="tanggalInput"
                               class="form-control form-control-sm"
                               value="{{ $tanggal ?? '' }}">
                    </div>
                    <div class="col-12 col-sm-7 col-md-8 col-lg-9 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-search me-1"></i>Cari
                        </button>
                        <a href="{{ route('admin.laporan.index') }}"
                           class="btn btn-outline-secondary btn-sm px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </a>
                        <button type="button" class="btn btn-outline-info btn-sm"
                                onclick="setToday()">
                            <i class="bi bi-calendar-check me-1"></i>Hari Ini
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Rentang Tanggal ── --}}
            <div id="rangeSection" class="d-none">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-sm-5 col-md-3">
                        <label class="filter-label">Dari Tanggal</label>
                        <input type="date" name="date_from" id="dateFromInput"
                               class="form-control form-control-sm"
                               value="{{ $dateFrom ?? '' }}">
                    </div>
                    <div class="col-12 col-sm-5 col-md-3">
                        <label class="filter-label">Sampai Tanggal</label>
                        <input type="date" name="date_to" id="dateToInput"
                               class="form-control form-control-sm"
                               value="{{ $dateTo ?? '' }}">
                    </div>
                    <div class="col-12 col-sm-2 col-md-6 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-search me-1"></i>Cari
                        </button>
                        <a href="{{ route('admin.laporan.index') }}"
                           class="btn btn-outline-secondary btn-sm px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </a>
                        <button type="button" class="btn btn-outline-info btn-sm"
                                onclick="setThisMonth()">
                            <i class="bi bi-calendar-month me-1"></i>Bulan Ini
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     INFO BAR
═══════════════════════════════════════════════════════ --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <p class="mb-0 small text-muted">
        <i class="bi bi-info-circle me-1"></i>
        Menampilkan
        <strong class="text-dark">{{ $permintaan->total() }} permintaan</strong>
        dan
        <strong class="text-dark">{{ $jadwalCount }} jadwal</strong>
        untuk <span class="fw-semibold text-primary">{{ $filterLabel }}</span>
    </p>
    <small class="text-muted fst-italic">
        <i class="bi bi-sort-down me-1"></i>Diurutkan: jam terbaru ke terlama
    </small>
</div>

{{-- ═══════════════════════════════════════════════════════
     STAT CARDS — Export dengan filter yang sama
═══════════════════════════════════════════════════════ --}}
@php
    $fp = array_filter(['tanggal' => $tanggal, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
    $fq = $fp ? '?' . http_build_query($fp) : '';
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-card-list me-2 text-primary"></i>Laporan Permintaan
                </h6>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="text-muted small mb-3">
                    <span class="badge bg-primary fs-6">{{ $permintaan->total() }}</span>
                    permintaan pada periode yang dipilih.
                </p>
                <div class="d-flex gap-2 flex-wrap mt-auto">
                    <a href="{{ route('admin.laporan.permintaan.pdf') . $fq }}"
                       class="btn btn-danger btn-sm" target="_blank">
                        <i class="bi bi-file-pdf me-1"></i>Export PDF
                    </a>
                    <a href="{{ route('admin.laporan.permintaan.excel') . $fq }}"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-file-excel me-1"></i>Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-calendar3 me-2 text-success"></i>Laporan Jadwal
                </h6>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="text-muted small mb-3">
                    <span class="badge bg-success fs-6">{{ $jadwalCount }}</span>
                    jadwal perjalanan pada periode yang dipilih.
                </p>
                <div class="d-flex gap-2 flex-wrap mt-auto">
                    <a href="{{ route('admin.laporan.jadwal.pdf') . $fq }}"
                       class="btn btn-danger btn-sm" target="_blank">
                        <i class="bi bi-file-pdf me-1"></i>Export PDF
                    </a>
                    <a href="{{ route('admin.laporan.jadwal.excel') . $fq }}"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-file-excel me-1"></i>Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     REKAP PERMINTAAN — filtered + paginated
═══════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-table me-2 text-primary"></i>Rekap Permintaan Kendaraan
        </h6>
        @if($permintaan->total() > 0)
        <span class="badge text-bg-secondary rounded-pill">
            {{ $permintaan->total() }} data
        </span>
        @endif
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:46px">No</th>
                        <th>Pemohon</th>
                        <th>Tujuan</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th class="text-center">Penumpang</th>
                        <th>Urgensi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permintaan as $p)
                    <tr>
                        <td class="ps-3 text-muted small">
                            {{ ($permintaan->currentPage() - 1) * $permintaan->perPage() + $loop->iteration }}
                        </td>
                        <td>
                            <div class="fw-semibold lh-sm">{{ $p->user->nama ?? '-' }}</div>
                            @if($p->user?->jabatan)
                            <div class="text-muted" style="font-size:.78rem">{{ $p->user->jabatan }}</div>
                            @endif
                        </td>
                        <td class="text-truncate" style="max-width:160px" title="{{ $p->tujuan }}">
                            {{ $p->tujuan }}
                        </td>
                        <td class="text-nowrap small">
                            {{ $p->tanggal_berangkat->format('d M Y') }}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border fw-semibold">
                                <i class="bi bi-clock me-1"></i>{{ $p->jam_berangkat }}
                            </span>
                        </td>
                        <td class="text-center small">
                            {{ $p->jumlah_penumpang }}
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle small">
                                {{ $p->urgensi->nama_urgensi ?? '-' }}
                            </span>
                        </td>
                        <td>{!! $p->statusLabel !!}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-5 text-center">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                            <strong class="text-muted">Tidak ada data</strong>
                            <br>
                            <small class="text-muted">
                                Tidak ada permintaan pada periode <strong>{{ $filterLabel }}</strong>.<br>
                                Coba ubah filter tanggal untuk melihat data lain.
                            </small>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($permintaan->hasPages())
        <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <small class="text-muted">
                Halaman {{ $permintaan->currentPage() }} dari {{ $permintaan->lastPage() }}
                &nbsp;&middot;&nbsp;
                Total {{ $permintaan->total() }} data
            </small>
            <div>
                {{ $permintaan->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     RANKING SAW
═══════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-trophy me-2 text-warning"></i>Ranking SAW (Semua Permintaan)
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:90px">Ranking</th>
                        <th>Pemohon</th>
                        <th>Tujuan</th>
                        <th>Urgensi</th>
                        <th>Nilai Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranking as $r)
                    <tr class="{{ $r->ranking == 1 ? 'table-warning' : '' }}">
                        <td class="ps-3">
                            @if($r->ranking == 1)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-trophy-fill me-1"></i>#1
                                </span>
                            @else
                                <span class="badge bg-secondary">#{{ $r->ranking }}</span>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $r->permintaan->user->nama ?? '-' }}</td>
                        <td>{{ $r->permintaan->tujuan ?? '-' }}</td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle small">
                                {{ $r->permintaan->urgensi->nama_urgensi ?? '-' }}
                            </span>
                        </td>
                        <td class="fw-semibold text-primary">
                            {{ number_format($r->nilai_akhir, 4) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-calculator me-2"></i>
                            Belum ada data ranking. Proses SAW terlebih dahulu.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .filter-label {
        display: block;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #6c757d;
        margin-bottom: 5px;
    }
    .pagination { margin-bottom: 0; }
    .page-link  { font-size: .8rem; padding: .3rem .6rem; }
    .table td, .table th { vertical-align: middle; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form          = document.getElementById('filterForm');
    const singleSection = document.getElementById('singleSection');
    const rangeSection  = document.getElementById('rangeSection');
    const tanggalInput  = document.getElementById('tanggalInput');
    const dateFromInput = document.getElementById('dateFromInput');
    const dateToInput   = document.getElementById('dateToInput');

    function getMode() {
        return document.querySelector('input[name="_m"]:checked')?.value ?? 'single';
    }

    function syncSections() {
        if (getMode() === 'single') {
            singleSection.classList.remove('d-none');
            rangeSection.classList.add('d-none');
        } else {
            singleSection.classList.add('d-none');
            rangeSection.classList.remove('d-none');
        }
    }

    // Initialise based on pre-checked radio
    syncSections();

    document.querySelectorAll('input[name="_m"]').forEach(function (r) {
        r.addEventListener('change', syncSections);
    });

    // On submit: disable hidden-section inputs + mode radios so they don't pollute the URL
    form.addEventListener('submit', function () {
        document.querySelectorAll('input[name="_m"]').forEach(function (r) {
            r.disabled = true;
        });
        if (getMode() === 'single') {
            if (dateFromInput) dateFromInput.disabled = true;
            if (dateToInput)   dateToInput.disabled   = true;
        } else {
            if (tanggalInput) tanggalInput.disabled = true;
        }
    });

    // Quick-fill helpers
    window.setToday = function () {
        const t = new Date().toISOString().split('T')[0];
        if (tanggalInput) tanggalInput.value = t;
    };

    window.setThisMonth = function () {
        const n     = new Date();
        const first = new Date(n.getFullYear(), n.getMonth(), 1).toISOString().split('T')[0];
        const last  = new Date(n.getFullYear(), n.getMonth() + 1, 0).toISOString().split('T')[0];
        if (dateFromInput) dateFromInput.value = first;
        if (dateToInput)   dateToInput.value   = last;
    };
})();
</script>
@endpush
