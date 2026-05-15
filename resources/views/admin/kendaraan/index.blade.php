@extends('layouts.app')
@section('title', 'Data Kendaraan')
@section('page-title', 'Data Kendaraan')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.permintaan.index') }}"><i class="bi bi-card-list"></i>Data Permintaan</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.kendaraan.index') }}"><i class="bi bi-truck"></i>Data Kendaraan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.ahp.index') }}"><i class="bi bi-calculator"></i>Proses AHP</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.saw.index') }}"><i class="bi bi-bar-chart"></i>Proses SAW</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.penjadwalan.index') }}"><i class="bi bi-calendar3"></i>Penjadwalan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.laporan.index') }}"><i class="bi bi-file-earmark-text"></i>Laporan</a></li>
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-truck me-2"></i>Daftar Kendaraan Operasional</h6>
        <a href="{{ route('admin.kendaraan.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Tambah</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Kendaraan</th>
                        <th>No Polisi</th>
                        <th>Driver</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                        <th>Tahun</th>
                        <th>Warna</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kendaraan as $i => $k)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $k->nama_kendaraan }}</td>
                        <td><span class="badge bg-secondary">{{ $k->no_polisi }}</span></td>
                        <td>{{ $k->nama_driver ?? '-' }}</td>
                        <td>{{ $k->kapasitas }} org</td>
                        <td>
                            <span class="badge bg-{{ $k->status === 'tersedia' ? 'success' : 'danger' }}">
                                {{ ucfirst($k->status) }}
                            </span>
                        </td>
                        <td>{{ $k->tahun }}</td>
                        <td>{{ $k->warna ?? '-' }}</td>
                        <td>
                            <a href="{{ route('admin.kendaraan.edit', $k->id_kendaraan) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('admin.kendaraan.destroy', $k->id_kendaraan) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Hapus kendaraan ini?')"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data kendaraan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
