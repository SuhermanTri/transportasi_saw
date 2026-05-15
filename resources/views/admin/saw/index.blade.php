@extends('layouts.app')
@section('title', 'Proses SAW')
@section('page-title', 'Proses SAW – Prioritas Permintaan')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.permintaan.index') }}"><i class="bi bi-card-list"></i>Data Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.kendaraan.index') }}"><i class="bi bi-truck"></i>Data Kendaraan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.ahp.index') }}"><i class="bi bi-calculator"></i>Proses AHP</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.saw.index') }}"><i class="bi bi-bar-chart"></i>Proses SAW</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.penjadwalan.index') }}"><i class="bi bi-calendar3"></i>Penjadwalan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.laporan.index') }}"><i class="bi bi-file-earmark-text"></i>Laporan</a></li>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3">

{{-- Bobot Kriteria --}}
<div class="col-lg-4">
    <div class="card">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Bobot Kriteria (AHP)</h6>
            <a href="{{ route('admin.ahp.index') }}" class="btn btn-outline-secondary btn-sm">Atur AHP</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Kriteria</th><th>Bobot</th><th>%</th></tr>
                </thead>
                <tbody>
                    @foreach($kriteria as $k)
                    <tr>
                        <td>{{ $k->nama_kriteria }}</td>
                        <td>
                            @if($k->bobot)
                                <span class="text-success fw-semibold">{{ number_format($k->bobot, 4) }}</span>
                            @else
                                <span class="text-danger small">belum dihitung</span>
                            @endif
                        </td>
                        <td>{{ $k->bobot ? number_format($k->bobot * 100, 1) . '%' : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            @if(!$bobotLengkap)
                <div class="alert alert-warning mb-0 py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>Hitung AHP terlebih dahulu sebelum SAW.
                </div>
            @else
                <form method="POST" action="{{ route('admin.saw.hitung') }}">
                    @csrf
                    <button class="btn btn-success w-100"><i class="bi bi-play-circle me-1"></i>Hitung / Perbarui Ranking SAW</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Keterangan Kriteria --}}
    <div class="card mt-3">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Keterangan Kriteria</h6>
        </div>
        <div class="card-body small">
            <p><strong>Jabatan</strong>: Nilai SAW jabatan pemohon dari tabel master jabatan (1–5). Semakin tinggi jabatan, semakin besar skor.</p>
            <p><strong>Urgensi</strong>: Nilai SAW urgensi perjalanan dari tabel master urgensi (1–5). Semakin mendesak, semakin besar skor.</p>
            <p class="mb-0"><strong>Gage Kendaraan</strong>: Proporsi kendaraan aktif yang sesuai aturan gage (ganjil/genap) untuk tanggal berangkat. Semakin banyak kendaraan tersedia, skor semakin tinggi.</p>
        </div>
    </div>
</div>

{{-- Tabel Hasil Ranking --}}
<div class="col-lg-8">
    <div class="card">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2"></i>Ranking Prioritas Permintaan</h6>
        </div>
        <div class="card-body p-0">
            @if($hasil->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-calculator fs-2 d-block mb-2"></i>
                    <div>Belum ada hasil SAW.</div>
                    <div class="small mt-1">
                        @if(!$bobotLengkap)
                            Atur bobot AHP terlebih dahulu, lalu klik "Hitung SAW".
                        @else
                            Klik "Hitung / Perbarui Ranking SAW" untuk mulai.
                        @endif
                    </div>
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">Rank</th>
                            <th>Pemohon</th>
                            <th>Tujuan</th>
                            <th>Urgensi</th>
                            <th class="text-center">S.Jabatan</th>
                            <th class="text-center">S.Urgensi</th>
                            <th class="text-center">S.Gage</th>
                            <th class="text-center">Nilai Akhir</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hasil as $h)
                        @php $p = $h->permintaan; @endphp
                        <tr class="{{ $h->ranking == 1 ? 'table-success' : '' }}">
                            <td class="text-center fw-bold">
                                @if($h->ranking == 1)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-trophy"></i> #1</span>
                                @else
                                    <span class="badge bg-secondary">#{{ $h->ranking }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $p?->user?->nama ?? '-' }}</div>
                                <div class="small text-muted">{{ $p?->user?->jabatan ?? '' }}</div>
                            </td>
                            <td>{{ $p?->tujuan ?? '-' }}</td>
                            <td>
                                @if($p?->urgensi)
                                    <span class="badge bg-info text-dark">{{ $p->urgensi->nama_urgensi }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center small">{{ number_format($h->skor_jabatan, 4) }}</td>
                            <td class="text-center small">{{ number_format($h->skor_urgensi, 4) }}</td>
                            <td class="text-center small">{{ number_format($h->skor_gage, 4) }}</td>
                            <td class="text-center fw-bold text-success">{{ number_format($h->nilai_akhir, 4) }}</td>
                            <td>{!! $p?->statusLabel ?? '' !!}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

</div>
@endsection
