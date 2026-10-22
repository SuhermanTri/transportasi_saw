@extends('layouts.app')
@section('title', 'Detail Permintaan')
@section('page-title', 'Detail Permintaan')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.create') }}"><i class="bi bi-plus-circle"></i>Ajukan Permintaan</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('user.permintaan.index') }}"><i class="bi bi-card-list"></i>Riwayat Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
@endsection

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Detail Permintaan #{{ $permintaan->id_permintaan }}</h6>
        <a href="{{ route('user.permintaan.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>
    <div class="card-body">

        {{-- ── Feedback Dispatcher ─────────────────────────────────────── --}}
        @if($permintaan->catatan_dispatcher)
        @php
            $fbMeta = \App\Models\Permintaan::$feedbackMeta[$permintaan->feedback_type]
                      ?? ['color' => 'secondary', 'icon' => 'bi-info-circle', 'label' => 'Keterangan'];
        @endphp
        <div class="alert alert-{{ $fbMeta['color'] === 'warning' ? 'warning' : 'danger' }} mb-4">
            <div class="d-flex align-items-start gap-3">
                <i class="bi {{ $fbMeta['icon'] }} fs-5 flex-shrink-0 mt-1"></i>
                <div>
                    <div class="fw-semibold">{{ $fbMeta['label'] }} dari Dispatcher</div>
                    <div class="mt-1">{{ $permintaan->catatan_dispatcher }}</div>
                    @if($permintaan->feedback_type !== 'ditolak')
                    <div class="small text-muted mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Permintaan Anda masih aktif dan akan dijadwalkan ulang oleh Dispatcher.
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ── Banner menunggu konfirmasi ──────────────────────────────── --}}
        @if($permintaan->jadwal && $permintaan->jadwal->status_perjalanan === 'selesai_driver' && !$permintaan->jadwal->penilaian)
        <div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="fw-semibold"><i class="bi bi-hourglass-split me-2"></i>Driver telah menyelesaikan perjalanan</div>
                <div class="small mt-1">Silakan konfirmasi dan berikan penilaian untuk menutup perjalanan ini.</div>
            </div>
            <a href="{{ route('user.penilaian.create', $permintaan->jadwal->id_jadwal) }}"
               class="btn btn-warning btn-sm text-nowrap flex-shrink-0">
                <i class="bi bi-check2-circle me-1"></i>Konfirmasi Selesai
            </a>
        </div>
        @endif

        <div class="row g-3">
            <div class="col-md-8">
                <label class="text-muted small">Tujuan</label>
                <div class="fw-semibold fs-5">{{ $permintaan->tujuan }}</div>
            </div>
            <div class="col-md-4">
                <label class="text-muted small">Status</label>
                <div>{!! $permintaan->statusLabel !!}</div>
            </div>

            <div class="col-md-6">
                <label class="text-muted small">Tanggal &amp; Jam Berangkat</label>
                <div class="fw-semibold">
                    {{ $permintaan->tanggal_berangkat->format('d M Y') }}
                    @if($permintaan->jam_berangkat)
                        <span class="text-muted fw-normal">pukul {{ $permintaan->jam_berangkat }}</span>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <label class="text-muted small">Tanggal &amp; Jam Kembali</label>
                <div class="fw-semibold">
                    @if($permintaan->tanggal_kembali)
                        {{ $permintaan->tanggal_kembali->format('d M Y') }}
                        @if($permintaan->jam_kembali)
                            <span class="text-muted fw-normal">pukul {{ $permintaan->jam_kembali }}</span>
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </div>

            <div class="col-md-4">
                <label class="text-muted small">Tingkat Urgensi</label>
                <div class="fw-semibold">
                    @if($permintaan->urgensi)
                        <span class="badge bg-info text-dark fs-6">{{ $permintaan->urgensi->nama_urgensi }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <label class="text-muted small">Jumlah Penumpang</label>
                <div class="fw-semibold">{{ $permintaan->jumlah_penumpang }} orang</div>
            </div>

            @if($permintaan->hasilSaw)
            <div class="col-md-4">
                <label class="text-muted small">Prioritas SAW</label>
                <div class="fw-semibold text-primary">
                    <i class="bi bi-trophy me-1"></i>Ranking #{{ $permintaan->hasilSaw->ranking }}
                    <span class="text-muted fw-normal small">({{ number_format($permintaan->hasilSaw->nilai_akhir, 4) }})</span>
                </div>
            </div>
            @endif

            <div class="col-12">
                <label class="text-muted small">Keterangan</label>
                <div>{{ $permintaan->keterangan ?? '—' }}</div>
            </div>
        </div>

        {{-- ── Jadwal kendaraan ─────────────────────────────────────────── --}}
        @if($permintaan->jadwal)
        <hr>
        @php $jadwal = $permintaan->jadwal; @endphp
        <div class="alert alert-{{ $jadwal->status_perjalanan === 'selesai_final' ? 'secondary' : 'success' }} mb-0">
            <h6 class="fw-semibold mb-2">
                <i class="bi bi-truck me-2"></i>Kendaraan Ditetapkan
                @if($jadwal->status_perjalanan === 'selesai_driver')
                    <span class="badge bg-warning text-dark ms-2">Menunggu Konfirmasi Anda</span>
                @elseif($jadwal->status_perjalanan === 'selesai_final')
                    <span class="badge bg-secondary ms-2">Perjalanan Selesai</span>
                @endif
            </h6>
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="text-muted small">Kendaraan</div>
                    <div class="fw-semibold">{{ $jadwal->kendaraan->nama_kendaraan ?? '-' }}</div>
                    <div class="text-muted small">{{ $jadwal->kendaraan->no_polisi ?? '' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Driver</div>
                    <div class="fw-semibold">{{ $jadwal->kendaraan->nama_driver ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Jam</div>
                    <div class="fw-semibold">{{ $jadwal->jam_mulai }} – {{ $jadwal->jam_selesai }}</div>
                </div>
            </div>

            {{-- Rating yang sudah diberikan --}}
            @if($jadwal->penilaian)
            <div class="mt-2 pt-2 border-top border-opacity-25">
                <i class="bi bi-star-fill text-warning me-1"></i>
                Anda memberi rating: <strong>{{ $jadwal->penilaian->nilai }}/5</strong>
                @if($jadwal->penilaian->komentar)
                    — "{{ $jadwal->penilaian->komentar }}"
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- ── Tombol Cancel & Reschedule ──────────────────────────────── --}}
        @if($permintaan->status === 'terjadwal')
        <hr>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('user.permintaan.reschedule', $permintaan->id_permintaan) }}"
               class="btn btn-warning">
                <i class="bi bi-arrow-repeat me-1"></i>Reschedule
            </a>
            <a href="{{ route('user.permintaan.cancel', $permintaan->id_permintaan) }}"
               class="btn btn-outline-danger">
                <i class="bi bi-slash-circle me-1"></i>Batalkan Booking
            </a>
        </div>
        @elseif($permintaan->status === 'disetujui')
        <hr>
        <a href="{{ route('user.permintaan.reschedule', $permintaan->id_permintaan) }}"
           class="btn btn-warning">
            <i class="bi bi-arrow-repeat me-1"></i>Reschedule
        </a>
        @endif

        {{-- ── Riwayat Pembatalan ───────────────────────────────────────── --}}
        @if($permintaan->status === 'dibatalkan_user' && $permintaan->alasan_batal)
        <hr>
        <div class="alert alert-danger mb-0">
            <h6 class="fw-semibold"><i class="bi bi-slash-circle me-2"></i>Booking Dibatalkan</h6>
            <div><strong>Alasan:</strong> {{ $permintaan->alasan_batal }}</div>
            @if($permintaan->dibatalkan_pada)
            <div class="text-muted small mt-1">
                Dibatalkan pada {{ $permintaan->dibatalkan_pada->format('d M Y, H:i') }}
            </div>
            @endif
        </div>
        @endif

        {{-- ── Audit Trail / Riwayat Perubahan ────────────────────────── --}}
        @if($permintaan->logs && $permintaan->logs->count() > 0)
        <hr>
        <h6 class="fw-semibold mb-3"><i class="bi bi-clock-history me-2"></i>Riwayat Aktivitas</h6>
        <div class="timeline">
            @foreach($permintaan->logs as $log)
            @php $meta = \App\Models\PermintaanLog::$tipeMeta[$log->tipe_log] ?? ['color' => 'secondary', 'icon' => 'bi-info-circle', 'label' => $log->tipe_log]; @endphp
            <div class="d-flex gap-3 mb-3">
                <div class="flex-shrink-0">
                    <span class="badge bg-{{ $meta['color'] }} rounded-circle p-2">
                        <i class="bi {{ $meta['icon'] }}"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold small">{{ $meta['label'] }}</div>
                    @if($log->keterangan)
                    <div class="text-muted small">{{ $log->keterangan }}</div>
                    @endif
                    <div class="text-muted" style="font-size:.75rem">
                        {{ $log->actor?->nama ?? 'Sistem' }} •
                        {{ $log->created_at->format('d M Y, H:i') }}
                    </div>
                    @if($log->data_lama && $log->tipe_log === 'reschedule_user')
                    <div class="mt-1 small text-muted">
                        Dari: <strong>{{ $log->data_lama['tanggal_berangkat'] ?? '-' }}</strong>
                        → <strong>{{ $log->data_baru['tanggal_berangkat'] ?? '-' }}</strong>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

    </div>
</div>
</div></div>
@endsection
