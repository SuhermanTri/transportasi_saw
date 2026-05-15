@extends('layouts.app')
@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi')

@section('sidebar-menu')
    @php $role = auth()->user()->role?->nama_role; @endphp
    @if($role === 'user')
        <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('user.permintaan.index') }}"><i class="bi bi-file-earmark-text"></i>Permintaan Saya</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('user.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
        <li class="nav-item"><a class="nav-link active" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
    @elseif($role === 'driver')
        <li class="nav-item"><a class="nav-link" href="{{ route('driver.dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('driver.jadwal') }}"><i class="bi bi-calendar3"></i>Jadwal Saya</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('driver.riwayat') }}"><i class="bi bi-clock-history"></i>Riwayat Perjalanan</a></li>
        <li class="nav-item"><a class="nav-link active" href="{{ route('notifikasi.index') }}"><i class="bi bi-bell"></i>Notifikasi</a></li>
    @endif
@endsection

@section('content')
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-bell me-2"></i>Semua Notifikasi
            @php $unreadTotal = $notifikasi->where('status', 'unread')->count(); @endphp
            @if($unreadTotal > 0)
                <span class="badge bg-danger ms-1" id="unread-count-badge">{{ $unreadTotal }}</span>
            @endif
        </h6>
        @if($notifikasi->where('status', 'unread')->isNotEmpty())
        <form id="formReadAll" method="POST" action="{{ route('notifikasi.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-check2-all me-1"></i>Tandai Semua Dibaca
            </button>
        </form>
        @endif
    </div>
    <div class="card-body p-0" id="notif-list">
        @forelse($notifikasi as $n)
        <div class="d-flex align-items-start p-3 border-bottom notif-item {{ $n->status === 'unread' ? 'bg-light' : '' }}"
             id="notif-{{ $n->id }}">
            <div class="me-3 mt-1 flex-shrink-0">
                <i class="bi bi-{{ $n->status === 'unread' ? 'bell-fill text-primary' : 'bell text-muted' }} fs-5"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="{{ $n->status === 'unread' ? 'fw-semibold' : 'text-muted' }}">
                    {{ $n->pesan }}
                </div>
                <small class="text-muted">
                    <i class="bi bi-clock me-1"></i>{{ $n->created_at->diffForHumans() }}
                </small>
            </div>
            <div class="ms-3 flex-shrink-0 d-flex gap-1">
                @if($n->url)
                {{-- goto: mark read + redirect ke URL --}}
                <a href="{{ route('notifikasi.goto', $n->id) }}"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Lihat
                </a>
                @endif
                @if($n->status === 'unread')
                <form method="POST" action="{{ route('notifikasi.read', $n->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Tandai dibaca">
                        <i class="bi bi-check2"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
            Tidak ada notifikasi
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
// Setelah halaman notifikasi terbuka: mark semua unread as read via AJAX
// sehingga badge di topbar langsung update tanpa menunggu polling 30 detik
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    var badge = document.getElementById('notif-badge');
    var unreadBadge = document.getElementById('unread-count-badge');
    var hasUnread = document.querySelectorAll('.notif-item.bg-light').length > 0;

    if (!hasUnread) return;

    fetch('{{ route("notifikasi.read-all") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        if (!d.ok) return;
        // Update topbar badge → 0
        if (badge) badge.style.display = 'none';
        // Update header badge di halaman notifikasi
        if (unreadBadge) unreadBadge.style.display = 'none';
        // Update visual style tiap item (ganti dari bg-light ke normal)
        document.querySelectorAll('.notif-item.bg-light').forEach(function (el) {
            el.classList.remove('bg-light');
        });
    })
    .catch(function () { /* gagal-diam */ });
})();
</script>
@endpush
