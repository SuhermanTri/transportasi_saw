{{--
    Tombol aksi perjalanan driver. Butuh $jadwal (App\Models\Jadwal).
    - Belum berangkat & tanggal hari ini (WIB) → "Mulai Perjalanan"
    - Belum berangkat & bukan hari ini        → tombol nonaktif
    - Sedang berjalan                         → "Selesai"
--}}
@php $hariIniWib = now(\App\Models\Jadwal::ZONA_WAKTU)->format('Y-m-d'); @endphp
@if($jadwal->status === 'aktif' && $jadwal->status_perjalanan === 'aktif')
    @if($jadwal->tanggal->format('Y-m-d') === $hariIniWib)
        <form method="POST" action="{{ route('driver.jadwal.mulai', $jadwal->id_jadwal) }}" class="d-inline"
              onsubmit="return confirm('Mulai perjalanan sekarang? Pemohon dan dispatcher akan diberi tahu.')">
            @csrf
            <button class="btn btn-primary {{ $ukuran ?? 'btn-sm' }}">
                <i class="bi bi-play-circle-fill me-1"></i>Mulai Perjalanan
            </button>
        </form>
    @else
        <button class="btn btn-outline-secondary {{ $ukuran ?? 'btn-sm' }}" disabled
                title="Bisa dimulai pada {{ $jadwal->tanggal->format('d M Y') }}">
            <i class="bi bi-play-circle me-1"></i>Mulai {{ $jadwal->tanggal->format('d M') }}
        </button>
    @endif
@elseif($jadwal->status === 'aktif' && $jadwal->status_perjalanan === 'berjalan')
    <form method="POST" action="{{ route('driver.jadwal.selesai', $jadwal->id_jadwal) }}" class="d-inline"
          onsubmit="return confirm('Tandai perjalanan ini sudah selesai?')">
        @csrf
        <button class="btn btn-success {{ $ukuran ?? 'btn-sm' }}">
            <i class="bi bi-check2-circle me-1"></i>Selesai
        </button>
    </form>
@endif
