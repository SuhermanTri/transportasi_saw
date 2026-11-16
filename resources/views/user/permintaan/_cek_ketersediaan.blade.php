{{--
    Peringatan ketersediaan kendaraan di form pengajuan (TIDAK memblokir).
    Dipanggil saat tanggal / jam / jumlah penumpang berubah. Saran bisa diklik untuk mengisi field.
--}}
<div id="cekKetersediaan" class="d-none"></div>

@push('scripts')
<script>
(function () {
    const form   = document.querySelector('input[name="tanggal_berangkat"]').form;
    const kotak  = document.getElementById('cekKetersediaan');
    const url    = @json(route('user.permintaan.cek-ketersediaan', [], false));
    const f      = n => form.querySelector('[name="' + n + '"]');
    let timer = null, nomor = 0;

    function cek() {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const tanggal = f('tanggal_berangkat').value, jam = f('jam_berangkat').value;
            if (!tanggal || !jam) { kotak.className = 'd-none'; return; }

            const q = new URLSearchParams({ tanggal, jam_berangkat: jam.slice(0, 5) });
            if (f('jam_kembali').value)      q.set('jam_kembali', f('jam_kembali').value.slice(0, 5));
            if (f('jumlah_penumpang').value) q.set('jumlah_penumpang', f('jumlah_penumpang').value);

            const n = ++nomor;
            try {
                const res = await fetch(url + '?' + q, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) { kotak.className = 'd-none'; return; }
                const data = await res.json();
                if (n !== nomor) return;   // abaikan hasil lama
                tampilkan(data);
            } catch (e) {
                kotak.className = 'd-none'; // gagal cek tidak mengganggu pengajuan
            }
        }, 400);
    }

    function tampilkan(data) {
        kotak.innerHTML = '';
        if (data.tersedia) {
            kotak.className = 'small text-success';
            kotak.innerHTML = '<i class="bi bi-check-circle me-1"></i>Kendaraan tersedia pada jam ini.';
            return;
        }

        kotak.className = 'alert alert-warning mb-0';
        const judul = document.createElement('div');
        judul.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>';
        judul.appendChild(document.createTextNode(data.pesan));
        kotak.appendChild(judul);

        const sub = document.createElement('div');
        sub.className = 'mt-2 small fw-semibold';
        sub.textContent = data.saran.length ? 'Alternatif yang tersedia (klik untuk memakai):' : 'Belum ada alternatif jam/tanggal lain yang tersedia saat ini.';
        kotak.appendChild(sub);

        const daftar = document.createElement('div');
        daftar.className = 'd-flex flex-wrap gap-2 mt-1';
        data.saran.forEach(s => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-sm btn-outline-dark';
            b.innerHTML = '<i class="bi bi-clock me-1"></i>';
            b.appendChild(document.createTextNode(s.label));
            b.addEventListener('click', () => pakai(s));
            daftar.appendChild(b);
        });
        kotak.appendChild(daftar);
    }

    function pakai(s) {
        const tanggalLama = f('tanggal_berangkat').value;
        f('tanggal_berangkat').value = s.tanggal;
        f('jam_berangkat').value     = s.jam_berangkat;
        if (s.jam_kembali) f('jam_kembali').value = s.jam_kembali;
        // Tanggal kembali ikut berpindah bila sebelumnya sama dengan tanggal berangkat
        if (f('tanggal_kembali') && f('tanggal_kembali').value === tanggalLama) f('tanggal_kembali').value = s.tanggal;
        cek();
    }

    ['tanggal_berangkat', 'jam_berangkat', 'jam_kembali', 'jumlah_penumpang'].forEach(n => {
        f(n)?.addEventListener('change', cek);
        f(n)?.addEventListener('input', cek);
    });
    cek(); // isian otomatis (Ajukan Ulang) langsung dicek
})();
</script>
@endpush
