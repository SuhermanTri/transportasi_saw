{{--
    Avatar pengguna. Parameter: $user (App\Models\User|null), $size (px, default 36).
    Foto bulat bila ada; bila tidak, lingkaran berisi inisial nama (maks. 2 huruf).
--}}
@php
    $size    = $size ?? 36;
    $fotoUrl = $user?->foto_url;
    $inisial = $user?->inisial ?? '?';
@endphp
@if($fotoUrl)
    <img src="{{ $fotoUrl }}" alt="Foto {{ $user->nama }}"
         class="rounded-circle flex-shrink-0 border" style="width:{{ $size }}px;height:{{ $size }}px;object-fit:cover;">
@else
    <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 fw-semibold text-white bg-secondary"
          style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size * 0.4) }}px;line-height:1;"
          title="{{ $user?->nama }}">{{ $inisial }}</span>
@endif
