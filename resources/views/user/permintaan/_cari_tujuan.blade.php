{{-- Saran alamat tujuan saat mengetik (autocomplete). Dipakai bersama #inputTujuan. --}}
<div id="saranTujuan" class="dropdown-menu w-100 shadow-sm" style="max-height: 280px; overflow-y: auto;"></div>
<div class="form-text">
    <i class="bi bi-search me-1"></i>Ketik minimal 3 huruf, lalu pilih alamat dari daftar saran.
    Jika tidak ditemukan, alamat tetap bisa diketik lengkap secara manual.
</div>

@push('scripts')
<script>
(function () {
    const input = document.getElementById('inputTujuan');
    const menu  = document.getElementById('saranTujuan');
    const url   = @json(route('user.lokasi.cari', [], false)); // relatif: aman untuk domain apa pun
    let timer = null, aktif = -1, items = [], permintaanTerakhir = 0;

    function tutup() { menu.classList.remove('show'); aktif = -1; }

    function tampilkan(list, pesan) {
        menu.innerHTML = '';
        items = list;
        if (!list.length) {
            const el = document.createElement('span');
            el.className = 'dropdown-item-text text-muted small';
            el.textContent = pesan;
            menu.appendChild(el);
        }
        list.forEach((label, i) => {
            const el = document.createElement('button');
            el.type = 'button';
            el.className = 'dropdown-item text-wrap';
            el.innerHTML = '<i class="bi bi-geo-alt me-2 text-danger"></i>';
            el.appendChild(document.createTextNode(label));
            el.addEventListener('mousedown', e => { e.preventDefault(); pilih(i); });
            menu.appendChild(el);
        });
        menu.classList.add('show');
        aktif = -1;
    }

    function pilih(i) {
        if (!items[i]) return;
        input.value = items[i].slice(0, 150);
        tutup();
    }

    function sorot(i) {
        const tombol = menu.querySelectorAll('.dropdown-item');
        tombol.forEach(b => b.classList.remove('active'));
        if (tombol[i]) { tombol[i].classList.add('active'); tombol[i].scrollIntoView({ block: 'nearest' }); }
        aktif = i;
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 3) { tutup(); return; }
        timer = setTimeout(async () => {
            const nomor = ++permintaanTerakhir;
            tampilkan([], 'Mencari alamat…');
            try {
                const res  = await fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                const data = res.ok ? await res.json() : [];
                if (nomor !== permintaanTerakhir) return; // abaikan hasil lama
                tampilkan(data, 'Alamat tidak ditemukan — ketik alamat lengkap secara manual.');
            } catch (e) {
                if (nomor === permintaanTerakhir) tampilkan([], 'Pencarian alamat sedang tidak tersedia — ketik secara manual.');
            }
        }, 350);
    });

    input.addEventListener('keydown', e => {
        if (!menu.classList.contains('show') || !items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); sorot(Math.min(aktif + 1, items.length - 1)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); sorot(Math.max(aktif - 1, 0)); }
        else if (e.key === 'Enter' && aktif >= 0) { e.preventDefault(); pilih(aktif); }
        else if (e.key === 'Escape') { tutup(); }
    });

    input.addEventListener('blur', () => setTimeout(tutup, 150));
})();
</script>
@endpush
