@extends('layouts.app')
@section('title', 'Tambah Kendaraan')
@section('page-title', 'Tambah Kendaraan')

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
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Tambah Kendaraan Baru</h6>
        <a href="{{ route('admin.kendaraan.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('admin.kendaraan.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Kendaraan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kendaraan" class="form-control" value="{{ old('nama_kendaraan') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No Polisi <span class="text-danger">*</span></label>
                    <input type="text" id="inputNoPol" name="no_polisi" class="form-control" value="{{ old('no_polisi') }}" required>
                    <div class="form-text">
                        Tipe gage otomatis terdeteksi: <span id="gagePreview" class="fw-semibold">—</span>
                    </div>
                </div>

                {{-- Nama driver: ketik bebas, dengan saran dari akun driver terdaftar --}}
                <div class="col-md-6">
                    <label class="form-label">Driver</label>
                    <input type="text" name="nama_driver" class="form-control" list="daftarDriver"
                           value="{{ old('nama_driver') }}" maxlength="100" autocomplete="off"
                           placeholder="Ketik nama driver">
                    <datalist id="daftarDriver">
                        @foreach($drivers as $d)
                            <option value="{{ $d->nama }}"></option>
                        @endforeach
                    </datalist>
                    <div class="form-text">Jika nama sama dengan akun driver, kendaraan otomatis terhubung ke akun tersebut.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No HP Driver</label>
                    <input type="text" name="no_hp_driver" class="form-control" value="{{ old('no_hp_driver') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Kapasitas <span class="text-danger">*</span></label>
                    <input type="number" name="kapasitas" class="form-control" value="{{ old('kapasitas', 5) }}" min="1" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tahun <span class="text-danger">*</span></label>
                    <input type="number" name="tahun" class="form-control" value="{{ old('tahun', date('Y')) }}" min="1990" max="2099" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="tersedia" {{ old('status') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="dipakai"  {{ old('status') === 'dipakai'  ? 'selected' : '' }}>Dipakai</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Warna</label>
                    <input type="text" name="warna" class="form-control" value="{{ old('warna') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tipe Gage</label>
                    <div class="form-control-plaintext">
                        <span id="gageBadge" class="badge bg-secondary">Masukkan no polisi</span>
                    </div>
                    <div class="form-text">Otomatis dari digit terakhir no polisi.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jam Mulai Operasional</label>
                    <input type="time" name="jam_mulai_operasional" class="form-control" value="{{ old('jam_mulai_operasional', '07:00') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jam Selesai Operasional</label>
                    <input type="time" name="jam_selesai_operasional" class="form-control" value="{{ old('jam_selesai_operasional', '17:00') }}">
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                <a href="{{ route('admin.kendaraan.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

@push('scripts')
<script>
(function () {
    const input = document.getElementById('inputNoPol');
    const preview = document.getElementById('gagePreview');
    const badge = document.getElementById('gageBadge');

    function detectGage(val) {
        const digits = (val.match(/\d+/g) || []).join('');
        if (!digits) return { label: 'Bebas', color: 'secondary' };
        const last = parseInt(digits[digits.length - 1], 10);
        if (last % 2 !== 0) return { label: 'Ganjil (tgl ganjil)', color: 'warning' };
        return { label: 'Genap (tgl genap)', color: 'info' };
    }

    function update() {
        const g = detectGage(input.value);
        preview.textContent = g.label;
        badge.className = `badge bg-${g.color} text-dark`;
        badge.textContent = g.label;
    }

    input.addEventListener('input', update);
    update();
})();
</script>
@endpush
@endsection
