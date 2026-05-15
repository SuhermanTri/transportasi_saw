@extends('layouts.app')
@section('title', 'Buat Jadwal')
@section('page-title', 'Buat Jadwal Kendaraan')

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
<div class="row justify-content-center"><div class="col-lg-8">

<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Form Penjadwalan Kendaraan</h6>
        <a href="{{ route('admin.penjadwalan.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.penjadwalan.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-medium">Pilih Permintaan <span class="text-danger">*</span></label>
                    <select id="selectPermintaan" name="id_permintaan" class="form-select" required>
                        <option value="">-- Pilih Permintaan yang Disetujui --</option>
                        @foreach($permintaan as $p)
                            <option value="{{ $p->id_permintaan }}"
                                {{ (request('permintaan') == $p->id_permintaan || old('id_permintaan') == $p->id_permintaan) ? 'selected' : '' }}>
                                @if($p->hasilSaw) [Rank #{{ $p->hasilSaw->ranking }}] @endif
                                #{{ $p->id_permintaan }} – {{ $p->user->nama ?? '-' }} → {{ $p->tujuan }}
                                ({{ $p->tanggal_berangkat->format('d M Y') }})
                                @if($p->urgensi) | {{ $p->urgensi->nama_urgensi }} @endif
                            </option>
                        @endforeach
                    </select>
                    @if($permintaan->isEmpty())
                        <small class="text-muted">Tidak ada permintaan yang disetujui dan belum dijadwalkan.</small>
                    @endif
                </div>

                {{-- Kendaraan tersedia akan dimuat via JS setelah permintaan dipilih --}}
                <div class="col-12" id="sectionKendaraan" style="display:none">
                    <label class="form-label fw-medium">
                        Pilih Kendaraan <span class="text-danger">*</span>
                        <span class="badge bg-info text-dark ms-2" id="infoGage" style="display:none"></span>
                    </label>
                    <select id="selectKendaraan" name="id_kendaraan" class="form-select" required>
                        <option value="">-- Memuat kendaraan tersedia... --</option>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Hanya menampilkan kendaraan yang sesuai aturan gage dan tidak bentrok jadwal.
                    </div>
                </div>

                <div id="noKendaraan" class="col-12 d-none">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Tidak ada kendaraan tersedia yang sesuai gage dan jadwal. Coba ubah waktu permintaan atau cek kendaraan.
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="btnSimpan" disabled>
                    <i class="bi bi-save me-1"></i>Simpan Jadwal
                </button>
                <a href="{{ route('admin.penjadwalan.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div></div>

@push('scripts')
<script>
const apiUrl = "{{ route('admin.penjadwalan.kendaraan-tersedia') }}";
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const selectPermintaan = document.getElementById('selectPermintaan');
const selectKendaraan  = document.getElementById('selectKendaraan');
const sectionKendaraan = document.getElementById('sectionKendaraan');
const noKendaraan      = document.getElementById('noKendaraan');
const infoGage         = document.getElementById('infoGage');
const btnSimpan        = document.getElementById('btnSimpan');

selectPermintaan.addEventListener('change', async function () {
    const pid = this.value;
    sectionKendaraan.style.display = 'none';
    noKendaraan.classList.add('d-none');
    infoGage.style.display = 'none';
    btnSimpan.disabled = true;
    selectKendaraan.innerHTML = '<option value="">-- Memuat... --</option>';

    if (!pid) return;

    try {
        const resp = await fetch(`${apiUrl}?id_permintaan=${pid}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const data = await resp.json();
        const kendaraan = data.kendaraan;

        // Tampilkan info status gage
        if (data.gage_aktif) {
            infoGage.textContent = `Gage Aktif: ${data.tipe_hari === 'ganjil' ? 'Ganjil' : 'Genap'}`;
            infoGage.className = 'badge bg-warning text-dark ms-2';
            infoGage.style.display = '';
        } else {
            infoGage.textContent = 'Di luar jam gage — semua kendaraan boleh';
            infoGage.className = 'badge bg-success ms-2';
            infoGage.style.display = '';
        }

        selectKendaraan.innerHTML = '<option value="">-- Pilih Kendaraan --</option>';
        if (kendaraan.length === 0) {
            noKendaraan.classList.remove('d-none');
            sectionKendaraan.style.display = 'none';
        } else {
            kendaraan.forEach(k => {
                const opt = document.createElement('option');
                opt.value = k.id_kendaraan;
                const gageInfo = data.gage_aktif ? `Gage: ${k.tipe_gage}` : 'Gage: bebas (tidak dibatasi)';
                opt.textContent = `${k.nama_kendaraan} – ${k.no_polisi} (${k.nama_driver || 'Tanpa driver'} · Kap. ${k.kapasitas} org · ${gageInfo})`;
                selectKendaraan.appendChild(opt);
            });
            sectionKendaraan.style.display = '';
            btnSimpan.disabled = false;
        }
    } catch (e) {
        console.error(e);
        selectKendaraan.innerHTML = '<option value="">Gagal memuat data</option>';
        sectionKendaraan.style.display = '';
    }
});

selectKendaraan.addEventListener('change', function () {
    btnSimpan.disabled = !this.value;
});

// Trigger jika ada pre-selected permintaan (dari query string)
if (selectPermintaan.value) {
    selectPermintaan.dispatchEvent(new Event('change'));
}
</script>
@endpush
@endsection
