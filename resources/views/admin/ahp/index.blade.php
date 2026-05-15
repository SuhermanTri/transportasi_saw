@extends('layouts.app')
@section('title', 'Proses AHP')
@section('page-title', 'Proses AHP – Perbandingan Kriteria')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.permintaan.index') }}"><i class="bi bi-card-list"></i>Data Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.kendaraan.index') }}"><i class="bi bi-truck"></i>Data Kendaraan</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.ahp.index') }}"><i class="bi bi-calculator"></i>Proses AHP</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.saw.index') }}"><i class="bi bi-bar-chart"></i>Proses SAW</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.penjadwalan.index') }}"><i class="bi bi-calendar3"></i>Penjadwalan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.laporan.index') }}"><i class="bi bi-file-earmark-text"></i>Laporan</a></li>
@endsection

@section('content')
<div class="row g-3">

{{-- Tambah Kriteria --}}
<div class="col-lg-4">
    <div class="card">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-circle me-2"></i>Tambah Kriteria</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ahp.kriteria.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nama Kriteria</label>
                    <input type="text" name="nama_kriteria" class="form-control" required placeholder="cth: Kapasitas">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jenis</label>
                    <select name="jenis" class="form-select">
                        <option value="benefit">Benefit (makin besar makin baik)</option>
                        <option value="cost">Cost (makin kecil makin baik)</option>
                    </select>
                </div>
                <button class="btn btn-primary w-100">Tambah Kriteria</button>
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-list-check me-2"></i>Daftar Kriteria</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Nama</th><th>Jenis</th><th>Bobot</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($kriteria as $k)
                    <tr>
                        <td>{{ $k->id_kriteria }}</td>
                        <td>{{ $k->nama_kriteria }}</td>
                        <td><span class="badge bg-{{ $k->jenis === 'benefit' ? 'success' : 'danger' }}">{{ $k->jenis }}</span></td>
                        <td>{{ $k->bobot ? number_format($k->bobot, 4) : '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.ahp.kriteria.delete', $k->id_kriteria) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Perbandingan & Hasil --}}
<div class="col-lg-8">
    <div class="card mb-3">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2"></i>Input Perbandingan Kriteria</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ahp.perbandingan.store') }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Kriteria 1</label>
                        <select name="kriteria1" class="form-select" required>
                            @foreach($kriteria as $k)
                                <option value="{{ $k->id_kriteria }}">{{ $k->nama_kriteria }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kriteria 2</label>
                        <select name="kriteria2" class="form-select" required>
                            @foreach($kriteria as $k)
                                <option value="{{ $k->id_kriteria }}">{{ $k->nama_kriteria }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Nilai (1–9)</label>
                        <input type="number" name="nilai" class="form-control" min="1" max="9" step="0.5" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Simpan</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr><th>Kriteria 1</th><th>Kriteria 2</th><th>Nilai</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @foreach($perbandingan as $p)
                        <tr>
                            <td>{{ $p->kriteriaA->nama_kriteria ?? '-' }}</td>
                            <td>{{ $p->kriteriaB->nama_kriteria ?? '-' }}</td>
                            <td>{{ $p->nilai }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.ahp.perbandingan.delete', $p->id_perbandingan) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <form method="POST" action="{{ route('admin.ahp.hitung') }}">
                    @csrf
                    <button class="btn btn-success"><i class="bi bi-calculator me-1"></i>Hitung Bobot AHP</button>
                </form>
            </div>
        </div>
    </div>

    @if($hasil)
    <div class="card">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2"></i>Hasil Perhitungan AHP</h6>
        </div>
        <div class="card-body">
            {{-- Matriks Perbandingan --}}
            <h6 class="fw-semibold mb-2">Matriks Perbandingan Berpasangan</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Kriteria</th>
                            @foreach($hasil['kriteria'] as $k) <th>{{ $k->nama_kriteria }}</th> @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hasil['kriteria'] as $i => $k)
                        <tr>
                            <th class="table-light">{{ $k->nama_kriteria }}</th>
                            @foreach($hasil['kriteria'] as $j => $k2)
                                <td>{{ number_format($hasil['matrix'][$i][$j], 4) }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                        <tr class="table-warning">
                            <th>Jumlah</th>
                            @foreach($hasil['colSum'] as $s)
                                <td>{{ number_format($s, 4) }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Matriks Normalisasi & Bobot --}}
            <h6 class="fw-semibold mb-2">Matriks Normalisasi & Bobot Prioritas</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Kriteria</th>
                            @foreach($hasil['kriteria'] as $k) <th>{{ $k->nama_kriteria }}</th> @endforeach
                            <th class="table-primary">Bobot (Eigen)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hasil['kriteria'] as $i => $k)
                        <tr>
                            <th class="table-light">{{ $k->nama_kriteria }}</th>
                            @foreach($hasil['kriteria'] as $j => $k2)
                                <td>{{ number_format($hasil['normalized'][$i][$j], 4) }}</td>
                            @endforeach
                            <td class="fw-bold text-primary">{{ number_format($hasil['priority'][$i], 4) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- CI/CR --}}
            <div class="row g-2">
                <div class="col-auto">
                    <div class="p-3 bg-light rounded">
                        <div class="small text-muted">λ max</div>
                        <div class="fw-bold">{{ number_format($hasil['lambdaMax'], 4) }}</div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="p-3 bg-light rounded">
                        <div class="small text-muted">CI</div>
                        <div class="fw-bold">{{ number_format($hasil['ci'], 4) }}</div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="p-3 bg-light rounded">
                        <div class="small text-muted">RI</div>
                        <div class="fw-bold">{{ number_format($hasil['ri'], 4) }}</div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="p-3 {{ $hasil['consistent'] ? 'bg-success' : 'bg-danger' }} bg-opacity-10 rounded">
                        <div class="small text-muted">CR</div>
                        <div class="fw-bold {{ $hasil['consistent'] ? 'text-success' : 'text-danger' }}">
                            {{ number_format($hasil['cr'], 4) }}
                        </div>
                        <div class="small {{ $hasil['consistent'] ? 'text-success' : 'text-danger' }}">
                            {{ $hasil['consistent'] ? 'Konsisten ✓' : 'Tidak Konsisten ✗' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
</div>
@endsection
