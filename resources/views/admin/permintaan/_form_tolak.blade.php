{{--
    Isi form tolak permintaan (dipakai di modal admin/permintaan/index & form di admin/permintaan/show).
    $urlSaran: URL JSON saran jadwal (boleh kosong — diisi lewat JavaScript untuk modal).
--}}
<div class="form-tolak" data-url-saran="{{ $urlSaran ?? '' }}">
    <div class="small text-muted mb-2">
        <i class="bi bi-lightbulb me-1"></i>
        Untuk kendaraan penuh, disarankan klik <strong>Setujui</strong> agar permintaan masuk antrean
        dan dijadwalkan otomatis saat kendaraan tersedia.
    </div>
    <div class="mb-2">
        <label class="form-label fw-semibold">Jenis Alasan <span class="text-danger">*</span></label>
        <select name="jenis_alasan" class="form-select pilih-jenis-alasan" required>
            <option value="">-- Pilih alasan --</option>
            @foreach(\App\Http\Controllers\Admin\PermintaanController::JENIS_ALASAN_TOLAK as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="alert alert-info py-2 small kotak-saran d-none">
        <div class="fw-semibold"><i class="bi bi-calendar-check me-1"></i>Saran jadwal untuk pemohon</div>
        <div class="isi-saran mt-1">Memuat saran jadwal…</div>
        <div class="text-muted mt-1">Saran otomatis disisipkan ke catatan di bawah.</div>
    </div>
    <div class="mb-2">
        <label class="form-label fw-semibold">Catatan untuk Pemohon</label>
        <textarea name="alasan" class="form-control" rows="3" maxlength="1000"
                  placeholder="Tuliskan alasan penolakan..."></textarea>
    </div>
</div>

@once
@push('scripts')
<script>
// Pilih "Kendaraan penuh" → muat saran jadwal & sisipkan ke catatan
document.addEventListener('change', async function (e) {
    if (!e.target.matches('.pilih-jenis-alasan')) return;
    const wadah  = e.target.closest('.form-tolak');
    const kotak  = wadah.querySelector('.kotak-saran');
    const isi    = wadah.querySelector('.isi-saran');
    const catatan = wadah.querySelector('textarea[name="alasan"]');

    if (e.target.value !== 'kendaraan_penuh') { kotak.classList.add('d-none'); return; }

    kotak.classList.remove('d-none');
    isi.textContent = 'Memuat saran jadwal…';
    try {
        const res  = await fetch(wadah.dataset.urlSaran, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        isi.innerHTML = '';
        if (data.saran && data.saran.length) {
            const ul = document.createElement('ul');
            ul.className = 'mb-0 ps-3';
            data.saran.forEach(s => { const li = document.createElement('li'); li.textContent = s.label; ul.appendChild(li); });
            isi.appendChild(ul);
        } else {
            isi.textContent = data.teks;
        }
        if (!catatan.value.includes('Saran jadwal')) {
            catatan.value = (catatan.value.trim() + ' ' + data.teks).trim();
        }
    } catch (err) {
        isi.textContent = 'Saran tidak dapat dimuat — akan ditambahkan otomatis saat permintaan ditolak.';
    }
});
</script>
@endpush
@endonce
