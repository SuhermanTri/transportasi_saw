@extends('layouts.app')
@section('title', 'Nilai Driver')
@section('page-title', 'Konfirmasi & Penilaian Driver')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('user.permintaan.index') }}"><i class="bi bi-file-earmark-text"></i>Permintaan Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-star me-2"></i>Konfirmasi & Nilai Driver</h6>
        <a href="{{ route('user.permintaan.show', $jadwal->permintaan->id_permintaan) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
    <div class="card-body">
        <div class="alert alert-info py-2 mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Driver <strong>{{ $jadwal->kendaraan?->driverUser?->nama ?? $jadwal->kendaraan?->nama_driver ?? '-' }}</strong>
            telah menyelesaikan perjalanan ke <strong>{{ $jadwal->permintaan->tujuan }}</strong>.
            Berikan konfirmasi dan penilaian untuk menyelesaikan proses.
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('user.penilaian.store', $jadwal->id_jadwal) }}">
            @csrf
            <div class="mb-4 text-center">
                <label class="form-label fw-medium d-block mb-2">Rating Driver <span class="text-danger">*</span></label>
                <div class="d-flex justify-content-center gap-2" id="star-rating">
                    @for($i = 1; $i <= 5; $i++)
                    <label class="star-label" style="cursor:pointer;font-size:2.5rem;color:#ccc" data-val="{{ $i }}">
                        <i class="bi bi-star-fill"></i>
                        <input type="radio" name="nilai" value="{{ $i }}" class="d-none" {{ old('nilai') == $i ? 'checked' : '' }}>
                    </label>
                    @endfor
                </div>
                <div class="text-muted small mt-1" id="star-label-text">Pilih rating</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Komentar (Opsional)</label>
                <textarea name="komentar" class="form-control" rows="3"
                          placeholder="Bagaimana layanan driver?">{{ old('komentar') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i>Konfirmasi Selesai & Kirim Penilaian
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>

@push('scripts')
<script>
const labels = ['', 'Kurang', 'Cukup', 'Baik', 'Sangat Baik', 'Luar Biasa'];
const stars = document.querySelectorAll('.star-label');
const labelText = document.getElementById('star-label-text');

function highlight(val) {
    stars.forEach(s => {
        s.style.color = parseInt(s.dataset.val) <= val ? '#f59e0b' : '#ccc';
    });
    labelText.textContent = val > 0 ? labels[val] : 'Pilih rating';
}

stars.forEach(s => {
    s.addEventListener('mouseover', () => highlight(parseInt(s.dataset.val)));
    s.addEventListener('mouseout',  () => {
        const checked = document.querySelector('input[name=nilai]:checked');
        highlight(checked ? parseInt(checked.value) : 0);
    });
    s.addEventListener('click', () => {
        s.querySelector('input').checked = true;
        highlight(parseInt(s.dataset.val));
    });
});

// Init state
const initialChecked = document.querySelector('input[name=nilai]:checked');
if (initialChecked) highlight(parseInt(initialChecked.value));
</script>
@endpush
@endsection
