@extends('layouts.app')
@section('title', 'Detail Permintaan')
@section('page-title', 'Detail Permintaan')

@section('sidebar-menu')
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.permintaan.index') }}"><i class="bi bi-card-list"></i>Data Permintaan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.kendaraan.index') }}"><i class="bi bi-truck"></i>Data Kendaraan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.ahp.index') }}"><i class="bi bi-calculator"></i>Proses AHP</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.saw.index') }}"><i class="bi bi-bar-chart"></i>Proses SAW</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.penjadwalan.index') }}"><i class="bi bi-calendar3"></i>Penjadwalan</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.laporan.index') }}"><i class="bi bi-file-earmark-text"></i>Laporan</a></li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- ── Skor SAW (jika sudah dihitung) ────────────────────────── --}}
        @if($permintaan->hasilSaw)
        <div class="card border-primary mb-3">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2"></i>Hasil SAW</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-3">
                        <div class="text-muted small">Ranking</div>
                        <div class="fs-4 fw-bold text-primary">#{{ $permintaan->hasilSaw->ranking }}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Jabatan</div>
                        <div class="fw-semibold">{{ number_format($permintaan->hasilSaw->skor_jabatan, 3) }}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Urgensi</div>
                        <div class="fw-semibold">{{ number_format($permintaan->hasilSaw->skor_urgensi, 3) }}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Nilai Akhir</div>
                        <div class="fs-5 fw-bold">{{ number_format($permintaan->hasilSaw->nilai_akhir, 4) }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Priority + Konflik (Fitur 3) ─────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-flag me-2"></i>Prioritas Booking</h6>
                <div>{!! $permintaan->priorityLabel !!}</div>
            </div>
            <div class="card-body py-3">
                <form method="POST" action="{{ route('admin.permintaan.priority', $permintaan->id_permintaan) }}"
                      class="d-flex gap-2 align-items-end">
                    @csrf
                    <div class="flex-grow-1">
                        <label class="form-label small mb-1 text-muted">Ubah Prioritas</label>
                        <select name="priority" class="form-select form-select-sm">
                            <option value="normal"  {{ ($permintaan->priority ?? 'normal') === 'normal'  ? 'selected' : '' }}>Normal</option>
                            <option value="high"    {{ ($permintaan->priority ?? 'normal') === 'high'   ? 'selected' : '' }}>High — Prioritas Tinggi</option>
                            <option value="urgent"  {{ ($permintaan->priority ?? 'normal') === 'urgent' ? 'selected' : '' }}>Urgent — Mendesak</option>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Simpan</button>
                </form>

                {{-- Konflik booking (Fitur 3: override) --}}
                @if($konflikBooking->count() > 0)
                <div class="alert alert-danger mt-3 mb-0">
                    <div class="fw-semibold mb-2">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        {{ $konflikBooking->count() }} booking prioritas lebih rendah terjadwal di tanggal yang sama:
                    </div>
                    @foreach($konflikBooking as $kb)
                    <div class="d-flex justify-content-between align-items-center bg-white rounded p-2 mb-2">
                        <div>
                            <div class="fw-semibold small">{{ $kb->user->nama ?? '-' }} → {{ $kb->tujuan }}</div>
                            <div class="text-muted small">
                                {{ $kb->tanggal_berangkat->format('d M Y') }} {{ $kb->jam_berangkat }}
                                {!! $kb->priorityLabel !!}
                                @if($kb->jadwal?->kendaraan)
                                    • Kendaraan: {{ $kb->jadwal->kendaraan->nama_kendaraan }}
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <a href="{{ route('admin.permintaan.show', $kb->id_permintaan) }}"
                               class="btn btn-outline-secondary btn-sm" title="Lihat">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.penjadwalan.edit', $kb->jadwal->id_jadwal) }}"
                               class="btn btn-outline-warning btn-sm" title="Reschedule">
                                <i class="bi bi-arrow-repeat"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ── Detail Permintaan ───────────────────────────────────────── --}}
        <div class="card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Detail Permintaan #{{ $permintaan->id_permintaan }}</h6>
                <a href="{{ route('admin.permintaan.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Nama Pemohon</label>
                        <div class="fw-semibold">{{ $permintaan->user->nama ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Jabatan</label>
                        <div class="fw-semibold">{{ $permintaan->user->jabatan ?? '-' }}</div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Tujuan Perjalanan</label>
                        <div class="fw-semibold">{{ $permintaan->tujuan }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Tanggal Berangkat</label>
                        <div class="fw-semibold">{{ $permintaan->tanggal_berangkat->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Tanggal Kembali</label>
                        <div class="fw-semibold">{{ $permintaan->tanggal_kembali?->format('d M Y') ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Jumlah Penumpang</label>
                        <div class="fw-semibold">{{ $permintaan->jumlah_penumpang }} orang</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Urgensi</label>
                        <div class="fw-semibold">
                            @if($permintaan->urgensi)
                                <span class="badge bg-info text-dark">{{ $permintaan->urgensi->nama_urgensi }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Keterangan</label>
                        <div class="fw-semibold">{{ $permintaan->keterangan ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Status</label>
                        <div>{!! $permintaan->statusLabel !!}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Tanggal Pengajuan</label>
                        <div class="fw-semibold">{{ \Carbon\Carbon::parse($permintaan->created_at)->format('d M Y H:i') }}</div>
                    </div>
                </div>

                {{-- Info Jadwal (jika sudah ada) --}}
                @if($permintaan->jadwal)
                <hr>
                <h6 class="fw-semibold mb-3"><i class="bi bi-calendar3 me-2"></i>Info Jadwal</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="text-muted small">Kendaraan</label>
                        <div class="fw-semibold">{{ $permintaan->jadwal->kendaraan->nama_kendaraan ?? '-' }}</div>
                        <div class="text-muted small">{{ $permintaan->jadwal->kendaraan->no_polisi ?? '' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Tanggal</label>
                        <div class="fw-semibold">{{ \Carbon\Carbon::parse($permintaan->jadwal->tanggal)->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Waktu</label>
                        <div class="fw-semibold">{{ $permintaan->jadwal->jam_mulai }} – {{ $permintaan->jadwal->jam_selesai }}</div>
                    </div>
                </div>
                @endif

                {{-- Tombol Approve / Tolak (jika masih menunggu) --}}
                @if($permintaan->status === 'menunggu')
                <hr>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    <form method="POST" action="{{ route('admin.permintaan.approve', $permintaan->id_permintaan) }}">
                        @csrf
                        <button class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Setujui</button>
                    </form>
                    <button class="btn btn-danger" data-bs-toggle="collapse" data-bs-target="#formTolak">
                        <i class="bi bi-x-lg me-1"></i>Tolak
                    </button>
                </div>
                <div class="collapse mt-3" id="formTolak">
                    <form method="POST" action="{{ route('admin.permintaan.reject', $permintaan->id_permintaan) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Alasan Penolakan</label>
                            <textarea name="alasan" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan..."></textarea>
                        </div>
                        <button class="btn btn-danger"><i class="bi bi-x-circle me-1"></i>Konfirmasi Tolak</button>
                    </form>
                </div>
                @endif

                {{-- Tombol untuk status menunggu_konfirmasi (tidak ada kendaraan tersedia) --}}
                @if($permintaan->status === 'menunggu_konfirmasi')
                <hr>
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Tidak ada kendaraan tersedia</strong> untuk tanggal ini. Jadwalkan secara manual atau ubah tanggal perjalanan.
                </div>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    @if(!$permintaan->jadwal)
                    <a href="{{ route('admin.penjadwalan.create', ['permintaan' => $permintaan->id_permintaan]) }}"
                       class="btn btn-primary">
                        <i class="bi bi-calendar-plus me-1"></i>Jadwalkan Manual
                    </a>
                    @endif
                    <button class="btn btn-warning" data-bs-toggle="collapse" data-bs-target="#formReschedule">
                        <i class="bi bi-arrow-repeat me-1"></i>Ubah Tanggal
                    </button>
                    <button class="btn btn-danger" data-bs-toggle="collapse" data-bs-target="#formTolakMK">
                        <i class="bi bi-x-lg me-1"></i>Tolak
                    </button>
                </div>
                <div class="collapse mb-3" id="formReschedule">
                    <div class="card card-body border-warning">
                        <form method="POST" action="{{ route('admin.permintaan.reschedule', $permintaan->id_permintaan) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Tanggal Baru</label>
                                <input type="date" name="tanggal_berangkat" class="form-control"
                                       value="{{ $tanggalSaran ?? $permintaan->tanggal_berangkat->format('Y-m-d') }}" required>
                                @if($tanggalSaran)
                                <div class="form-text">Tanggal terdekat dengan kendaraan tersedia pada jam yang sama: {{ \Carbon\Carbon::parse($tanggalSaran)->format('d M Y') }}</div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Keterangan untuk Pemohon <span class="text-muted fw-normal small">(opsional)</span></label>
                                <textarea name="catatan" class="form-control" rows="2"
                                    placeholder="Contoh: Tanggal diubah karena semua kendaraan sedang digunakan."></textarea>
                            </div>
                            <button class="btn btn-warning"><i class="bi bi-arrow-repeat me-1"></i>Simpan Perubahan Tanggal</button>
                        </form>
                    </div>
                </div>
                <div class="collapse" id="formTolakMK">
                    <div class="card card-body border-danger">
                        <form method="POST" action="{{ route('admin.permintaan.reject', $permintaan->id_permintaan) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Alasan Penolakan</label>
                                <textarea name="alasan" class="form-control" rows="2" placeholder="Tuliskan alasan penolakan..."></textarea>
                            </div>
                            <button class="btn btn-danger"><i class="bi bi-x-circle me-1"></i>Konfirmasi Tolak</button>
                        </form>
                    </div>
                </div>
                @endif

                {{-- Tombol Jadwalkan (jika sudah disetujui & belum terjadwal) --}}
                @if($permintaan->status === 'disetujui' && !$permintaan->jadwal)
                <hr>
                <a href="{{ route('admin.penjadwalan.create', ['permintaan' => $permintaan->id_permintaan]) }}"
                   class="btn btn-primary">
                    <i class="bi bi-calendar-plus me-1"></i>Buat Jadwal
                </a>
                @endif

                {{-- Info pembatalan oleh user --}}
                @if($permintaan->status === 'dibatalkan_user' && $permintaan->alasan_batal)
                <hr>
                <div class="alert alert-danger mb-0">
                    <h6 class="fw-semibold"><i class="bi bi-slash-circle me-2"></i>Dibatalkan oleh Pemohon</h6>
                    <div><strong>Alasan:</strong> {{ $permintaan->alasan_batal }}</div>
                    @if($permintaan->dibatalkan_pada)
                    <div class="text-muted small mt-1">
                        Pada {{ $permintaan->dibatalkan_pada->format('d M Y, H:i') }}
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- ── Audit Trail (Fitur 4) ───────────────────────────────────── --}}
        @if($permintaan->logs && $permintaan->logs->count() > 0)
        <div class="card mt-3">
            <div class="card-header bg-white py-2">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Riwayat Aktivitas</h6>
            </div>
            <div class="card-body">
                @foreach($permintaan->logs as $log)
                @php $meta = \App\Models\PermintaanLog::$tipeMeta[$log->tipe_log] ?? ['color' => 'secondary', 'icon' => 'bi-info-circle', 'label' => $log->tipe_log]; @endphp
                <div class="d-flex gap-3 {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
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
                        @if($log->data_lama && in_array($log->tipe_log, ['reschedule_user','reschedule_admin']))
                            <div class="small text-muted mt-1">
                                Dari: <code>{{ $log->data_lama['tanggal_berangkat'] ?? '-' }}</code>
                                → <code>{{ $log->data_baru['tanggal_berangkat'] ?? '-' }}</code>
                            </div>
                        @endif
                        @if($log->tipe_log === 'prioritas_diubah' && $log->data_lama)
                            <div class="small text-muted mt-1">
                                Dari: <code>{{ $log->data_lama['priority'] ?? '-' }}</code>
                                → <code>{{ $log->data_baru['priority'] ?? '-' }}</code>
                            </div>
                        @endif
                        <div class="text-muted" style="font-size:.75rem">
                            {{ $log->actor?->nama ?? 'Sistem' }} •
                            {{ $log->created_at->format('d M Y, H:i') }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
