{{-- Informasi batas waktu pengajuan request (H-1 & hari yang sama). Butuh variabel $jendela. --}}
<div class="form-text">
    <i class="bi bi-info-circle me-1"></i>
    Request <strong>hari ini</strong> ({{ $jendela['hariIni']->translatedFormat('d M Y') }}) dapat diajukan kapan saja.
    Request <strong>H-1</strong> untuk besok ({{ $jendela['besok']->translatedFormat('d M Y') }}) hanya dibuka
    pukul {{ $jendela['jamBuka'] }}–{{ $jendela['jamTutup'] }} WIB, setiap hari.
</div>
@if($jendela['besokDibuka'])
    <div class="form-text text-success">
        <i class="bi bi-unlock me-1"></i>
        Pengajuan H-1 untuk besok sedang dibuka sampai pukul {{ $jendela['jamTutup'] }} WIB.
    </div>
@else
    <div class="form-text text-danger">
        <i class="bi bi-lock me-1"></i>
        Pengajuan H-1 untuk besok sedang ditutup (sekarang {{ $jendela['sekarang']->format('H:i') }} WIB).
        Dibuka pukul {{ $jendela['jamBuka'] }} WIB.
    </div>
@endif
