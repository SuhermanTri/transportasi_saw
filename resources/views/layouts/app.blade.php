<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Transportasi') | Transportasi Kantor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* ── Brand palette (matching login identity) ── */
        :root {
            --brand-navy:      #001e52;
            --brand-navy-dk:   #001230;
            --brand-navy-lt:   #002878;
            --brand-red:       #e31c25;
            --brand-green:     #6ab04c;
            --brand-sky:       #5bb3e8;
            /* Bootstrap 5 utility overrides */
            --bs-primary:      #001e52;
            --bs-primary-rgb:  0, 30, 82;
            --bs-success:      #6ab04c;
            --bs-success-rgb:  106, 176, 76;
            --bs-danger:       #e31c25;
            --bs-danger-rgb:   227, 28, 37;
            --bs-info:         #5bb3e8;
            --bs-info-rgb:     91, 179, 232;
            --bs-link-color:       #001e52;
            --bs-link-hover-color: #002878;
        }

        body { background-color: #f0f5fa; }

        /* ── Sidebar ── */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #001230 0%, #001e52 55%, #002878 100%);
            border-right: 3px solid #6ab04c;
            width: 260px;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            padding-top: 0;
            transition: all .3s;
        }
        .sidebar-brand {
            padding: 1.4rem 1rem;
            border-bottom: 1px solid rgba(106,176,76,.35);
            background: rgba(0,0,0,.15);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.72);
            padding: .58rem 1rem .58rem 1.1rem;
            border-radius: .4rem;
            margin: 2px 8px;
            font-size: .875rem;
            border-left: 3px solid transparent;
            transition: all .18s;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.1);
            border-left-color: rgba(106,176,76,.5);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(106,176,76,.18);
            border-left-color: #6ab04c;
            font-weight: 600;
        }
        .sidebar .nav-link i { margin-right: 8px; }

        /* ── Main content ── */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }

        /* ── Topbar ── */
        .topbar {
            background: #fff;
            border-bottom: 3px solid #e31c25;
            padding: .72rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 2px 8px rgba(0,30,82,.07);
        }

        /* ── Cards ── */
        .card {
            border: none;
            box-shadow: 0 .125rem .5rem rgba(0,30,82,.09);
            border-radius: .6rem;
        }
        .card-header { border-bottom: 1px solid rgba(0,30,82,.06); }
        .stat-card { border-radius: .8rem; overflow: hidden; }
        .stat-icon {
            width: 56px; height: 56px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }

        /* ── Button overrides (Bootstrap compiled values, not CSS-var) ── */
        .btn-primary {
            background-color: #001e52; border-color: #001e52; color: #fff;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #002878; border-color: #002878; color: #fff;
        }
        .btn-outline-primary {
            color: #001e52; border-color: #001e52;
        }
        .btn-outline-primary:hover {
            background-color: #001e52; border-color: #001e52; color: #fff;
        }
        .btn-success {
            background-color: #6ab04c; border-color: #6ab04c; color: #fff;
        }
        .btn-success:hover, .btn-success:focus {
            background-color: #5a9c3d; border-color: #5a9c3d; color: #fff;
        }
        .btn-outline-success {
            color: #6ab04c; border-color: #6ab04c;
        }
        .btn-outline-success:hover {
            background-color: #6ab04c; border-color: #6ab04c; color: #fff;
        }
        .btn-danger {
            background-color: #e31c25; border-color: #e31c25; color: #fff;
        }
        .btn-danger:hover, .btn-danger:focus {
            background-color: #c0151d; border-color: #c0151d; color: #fff;
        }
        .btn-outline-danger {
            color: #e31c25; border-color: #e31c25;
        }
        .btn-outline-danger:hover {
            background-color: #e31c25; border-color: #e31c25; color: #fff;
        }
        .btn-info {
            background-color: #5bb3e8; border-color: #5bb3e8; color: #fff;
        }
        .btn-info:hover { background-color: #4a9fd4; border-color: #4a9fd4; color: #fff; }

        /* ── Alert overrides ── */
        .alert-success { border-left: 4px solid #6ab04c; }
        .alert-danger  { border-left: 4px solid #e31c25; }

        @media (max-width: 768px) {
            .sidebar { width: 0; overflow: hidden; }
            .main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="sidebar d-flex flex-column">
    <div class="sidebar-brand text-center">
        <div class="text-white fw-bold fs-5"><i class="bi bi-truck me-2"></i>Transportasi</div>
        <small class="text-white-50">Manajemen Kendaraan</small>
    </div>
    <ul class="nav flex-column mt-3 flex-grow-1">
        @if(auth()->check() && auth()->user()->role?->nama_role === 'dispatcher')
            @include('admin.partials.sidebar')
        @else
            @yield('sidebar-menu')
        @endif
    </ul>
    <div class="p-3 border-top border-white border-opacity-10 mt-auto">
        <div class="text-white-50 small mb-1">
            <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->nama }}
        </div>
        <div class="text-white-50 small mb-2">{{ auth()->user()->jabatan }}</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</div>

<div class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold text-dark">@yield('page-title', 'Dashboard')</h6>
        <div class="d-flex align-items-center gap-3">
            @auth
            @php $unreadCount = \App\Models\Notifikasi::unreadCount(auth()->id()); @endphp
            <a id="notif-btn" href="{{ route('notifikasi.index') }}"
               class="btn btn-sm btn-outline-secondary position-relative">
                <i class="bi bi-bell"></i>
                <span id="notif-badge"
                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                      style="font-size:.6rem;{{ $unreadCount === 0 ? 'display:none' : '' }}">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            </a>
            @endauth
            <span class="badge bg-primary">{{ auth()->user()->role->nama_role ?? '-' }}</span>
            <span class="text-muted small">{{ now()->format('d M Y') }}</span>
        </div>
    </div>

    <div class="p-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')

@auth
<script>
(function () {
    var badge   = document.getElementById('notif-badge');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrf    = csrfMeta ? csrfMeta.content : '';
    var countUrl = '{{ route("notifikasi.count") }}';

    function updateBadge(count) {
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }
    }

    function poll() {
        fetch(countUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d) updateBadge(d.count); })
            .catch(function () { /* gagal-diam */ });
    }

    // Poll setiap 30 detik; tidak perlu poll langsung (nilai awal sudah dari Blade)
    setInterval(poll, 30000);
})();
</script>
@endauth
</body>
</html>
