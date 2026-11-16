{{-- Badge status perjalanan seragam (sumber: accessor Jadwal::label_status_perjalanan). Butuh $jadwal. --}}
@if($jadwal)
    @php $sp = $jadwal->label_status_perjalanan; @endphp
    <span class="badge bg-{{ $sp['warna'] }}"
          @if($jadwal->terlambatBerangkat()) title="Jam berangkat sudah lewat, driver belum menekan Mulai Perjalanan" @endif>
        <i class="bi {{ $sp['ikon'] }} me-1"></i>{{ $sp['label'] }}
    </span>
@endif
