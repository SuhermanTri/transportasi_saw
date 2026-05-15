@extends('layouts.app')
@section('title', 'Data User')
@section('page-title', 'Manajemen User')

@section('content')

{{-- ─────────────────────────────────────────────────────
     PAGE TOOLBAR
───────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <p class="text-muted small mb-0">
            Pilih kategori untuk melihat dan mengelola data pengguna.
        </p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm px-4">
        <i class="bi bi-person-plus me-1"></i>Tambah User
    </a>
</div>

{{-- ─────────────────────────────────────────────────────
     ROLE SELECTOR CARDS
───────────────────────────────────────────────────── --}}
@php
    $cards = [
        'user' => [
            'label'    => 'Pegawai / User',
            'sublabel' => 'akun pegawai terdaftar',
            'icon'     => 'bi-people-fill',
            'class'    => 'rc-user',
            'cta'      => 'Kelola Pegawai',
        ],
        'driver' => [
            'label'    => 'Driver',
            'sublabel' => 'pengemudi kendaraan',
            'icon'     => 'bi-truck',
            'class'    => 'rc-driver',
            'cta'      => 'Kelola Driver',
        ],
        'dispatcher' => [
            'label'    => 'Dispatcher',
            'sublabel' => 'administrator sistem',
            'icon'     => 'bi-person-workspace',
            'class'    => 'rc-dispatcher',
            'cta'      => 'Kelola Dispatcher',
        ],
    ];
@endphp

<div class="row g-4 mb-4">
    @foreach($cards as $key => $card)
    @php
        $isActive  = $role === $key;
        $href      = $isActive
            ? route('admin.users.index')
            : route('admin.users.index', ['role' => $key]);
        $count     = $counts[$key] ?? 0;
    @endphp
    <div class="col-12 col-sm-4">
        <a href="{{ $href }}"
           class="role-card {{ $card['class'] }} {{ $isActive ? 'is-active' : '' }}"
           aria-label="{{ $isActive ? 'Tutup tampilan' : 'Tampilkan' }} {{ $card['label'] }}">

            {{-- Decorative blob --}}
            <div class="rc-blob" aria-hidden="true"></div>

            {{-- Icon --}}
            <div class="rc-icon">
                <i class="bi {{ $card['icon'] }}"></i>
            </div>

            {{-- Role name --}}
            <div class="rc-name">{{ $card['label'] }}</div>

            {{-- Count --}}
            <div class="rc-count">{{ number_format($count) }}</div>
            <div class="rc-sublabel">{{ $card['sublabel'] }}</div>

            {{-- CTA --}}
            <div class="rc-cta">
                @if($isActive)
                    <i class="bi bi-x-circle me-1"></i>Tutup
                @else
                    {{ $card['cta'] }}&nbsp;<i class="bi bi-arrow-right"></i>
                @endif
            </div>

            @if($isActive)
            <div class="rc-active-badge">
                <i class="bi bi-check-circle-fill me-1"></i>Aktif
            </div>
            @endif
        </a>
    </div>
    @endforeach
</div>

{{-- ─────────────────────────────────────────────────────
     DATA TABLE — only rendered when a role is selected
───────────────────────────────────────────────────── --}}
@if($role && $users !== null)
@php
    $roleLabel = [
        'user'       => 'Pegawai / User',
        'driver'     => 'Driver',
        'dispatcher' => 'Dispatcher',
    ];
    $roleIcon = [
        'user'       => 'bi-people-fill text-primary',
        'driver'     => 'bi-truck text-warning',
        'dispatcher' => 'bi-person-workspace text-success',
    ];
    $clearUrl = route('admin.users.index', ['role' => $role]);
@endphp

<div class="card" id="tableSection">
    {{-- Card header: title + search + close --}}
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

            {{-- Left: label + count --}}
            <div class="d-flex align-items-center gap-2">
                <i class="bi {{ $roleIcon[$role] }} fs-5"></i>
                <span class="fw-semibold">{{ $roleLabel[$role] }}</span>
                <span class="badge text-bg-secondary rounded-pill">{{ $users->total() }}</span>
                @if($q)
                    <span class="badge text-bg-primary rounded-pill">
                        <i class="bi bi-funnel-fill me-1"></i>Pencarian aktif
                    </span>
                @endif
            </div>

            {{-- Right: search form --}}
            <form method="GET" action="{{ route('admin.users.index') }}"
                  id="searchForm" class="d-flex align-items-center gap-2">
                <input type="hidden" name="role" value="{{ $role }}">
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text"
                           name="q"
                           id="searchInput"
                           class="form-control form-control-sm search-input"
                           placeholder="Cari nama, username, jabatan…"
                           value="{{ $q }}"
                           autocomplete="off">
                    @if($q)
                    <a href="{{ $clearUrl }}"
                       class="search-clear" title="Hapus pencarian">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Search result info --}}
        @if($q)
        <div class="mt-2 pt-2 border-top">
            <small class="text-muted">
                <i class="bi bi-search me-1"></i>
                Menampilkan <strong>{{ $users->total() }}</strong> hasil
                untuk kata kunci "<em class="text-primary">{{ $q }}</em>"
            </small>
        </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:46px">No</th>
                        <th>Nama</th>
                        @if($role !== 'driver')
                            <th>Username</th>
                            <th>Jabatan</th>
                        @else
                            <th>Kendaraan</th>
                            <th>Status</th>
                        @endif
                        <th style="width:90px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td class="ps-3 text-muted small">
                            {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                        </td>

                        {{-- Nama cell --}}
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="u-avatar u-avatar-{{ $role }}">
                                    {{ strtoupper(substr($u->nama, 0, 1)) }}
                                </div>
                                <div class="lh-sm">
                                    <div class="fw-semibold">{{ $u->nama }}</div>
                                    @if($role === 'driver')
                                        <code class="small text-muted">{{ $u->username }}</code>
                                    @endif
                                    @if($u->id_user === auth()->id())
                                        <div class="badge text-bg-light border small">Anda</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Role-specific columns --}}
                        @if($role !== 'driver')
                            <td><code class="small">{{ $u->username }}</code></td>
                            <td class="text-muted small">{{ $u->jabatan ?? '—' }}</td>
                        @else
                            <td>
                                @if($u->kendaraan)
                                    <div class="fw-semibold text-success small lh-sm">
                                        <i class="bi bi-truck me-1"></i>{{ $u->kendaraan->nama_kendaraan }}
                                    </div>
                                    <div class="text-muted" style="font-size:.75rem">
                                        {{ $u->kendaraan->no_polisi ?? '' }}
                                    </div>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle small">
                                        <i class="bi bi-dash-circle me-1"></i>Belum ditugaskan
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($u->kendaraan)
                                    <span class="badge text-bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Aktif
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">Tanpa kendaraan</span>
                                @endif
                            </td>
                        @endif

                        {{-- Aksi --}}
                        <td>
                            <a href="{{ route('admin.users.edit', $u->id_user) }}"
                               class="btn btn-warning btn-sm" title="Edit user">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($u->id_user !== auth()->id())
                            <form method="POST"
                                  action="{{ route('admin.users.destroy', $u->id_user) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Hapus user {{ addslashes($u->nama) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                            @if($q)
                                <strong class="text-muted">
                                    Tidak ada hasil untuk "<em>{{ $q }}</em>"
                                </strong>
                                <br>
                                <a href="{{ $clearUrl }}" class="btn btn-sm btn-outline-secondary mt-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Hapus Pencarian
                                </a>
                            @else
                                <strong class="text-muted">
                                    Belum ada data {{ $roleLabel[$role] }}
                                </strong>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <small class="text-muted">
                Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}
                &nbsp;&middot;&nbsp;
                {{ $users->total() }} total data
            </small>
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
/* ═══════════════════════════════════════════════
   ROLE SELECTOR CARDS
═══════════════════════════════════════════════ */
.role-card {
    display: block;
    position: relative;
    text-decoration: none;
    border-radius: 20px;
    border: 2px solid #e8edf3;
    padding: 28px 24px 24px;
    background: #fff;
    color: #1e293b;
    overflow: hidden;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}
.role-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 40px rgba(0,0,0,.11);
    border-color: var(--rc-color, #0d3b8c);
    color: #1e293b;
}
.role-card.is-active {
    background: linear-gradient(135deg, var(--rc-from), var(--rc-to));
    border-color: transparent;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 12px 36px var(--rc-shadow, rgba(0,0,0,.25));
}

/* Decorative blob */
.rc-blob {
    position: absolute;
    top: -28px; right: -28px;
    width: 110px; height: 110px;
    border-radius: 50%;
    background: var(--rc-color, #0d3b8c);
    opacity: .06;
    pointer-events: none;
    transition: opacity .22s;
}
.role-card.is-active .rc-blob { background: #fff; opacity: .1; }

/* Icon */
.rc-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(var(--rc-rgb, 13,59,140), .1);
    color: var(--rc-color, #0d3b8c);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    margin-bottom: 18px;
    transition: background .22s, color .22s;
}
.role-card.is-active .rc-icon {
    background: rgba(255,255,255,.2);
    color: #fff;
}

/* Texts */
.rc-name {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
    line-height: 1.2;
}
.rc-count {
    font-size: 40px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -1.5px;
    color: var(--rc-color, #0d3b8c);
    margin-bottom: 3px;
    transition: color .22s;
}
.role-card.is-active .rc-count { color: #fff; }

.rc-sublabel {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 22px;
    transition: color .22s;
}
.role-card.is-active .rc-sublabel { color: rgba(255,255,255,.72); }

.rc-cta {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--rc-color, #0d3b8c);
    display: flex;
    align-items: center;
    gap: 4px;
    transition: color .22s;
}
.role-card.is-active .rc-cta { color: rgba(255,255,255,.88); }

/* Active badge */
.rc-active-badge {
    position: absolute;
    top: 14px; right: 14px;
    background: rgba(255,255,255,.22);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,.35);
}

/* ── Per-role colour tokens ── */
.rc-user {
    --rc-color:  #0d3b8c;
    --rc-from:   #0d3b8c;
    --rc-to:     #1a5fd1;
    --rc-shadow: rgba(13,59,140,.32);
    --rc-rgb:    13, 59, 140;
}
.rc-driver {
    --rc-color:  #b45309;
    --rc-from:   #c2410c;
    --rc-to:     #ea580c;
    --rc-shadow: rgba(194,65,12,.32);
    --rc-rgb:    180, 83, 9;
}
.rc-dispatcher {
    --rc-color:  #065f46;
    --rc-from:   #065f46;
    --rc-to:     #059669;
    --rc-shadow: rgba(6,95,70,.32);
    --rc-rgb:    6, 95, 70;
}

/* ═══════════════════════════════════════════════
   TABLE SECTION
═══════════════════════════════════════════════ */
#tableSection {
    animation: slideDown .28s ease-out;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-14px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── User avatars ── */
.u-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    font-size: 12px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: #fff; text-transform: uppercase; line-height: 1;
}
.u-avatar-user       { background: linear-gradient(135deg, #0d3b8c, #1a5fd1); }
.u-avatar-driver     { background: linear-gradient(135deg, #c2410c, #ea580c); }
.u-avatar-dispatcher { background: linear-gradient(135deg, #065f46, #059669); }

/* ── Search box ── */
.search-box {
    position: relative;
    width: 280px;
    max-width: 100%;
}
.search-icon {
    position: absolute;
    left: 10px; top: 50%;
    transform: translateY(-50%);
    font-size: 13px; color: #adb5bd;
    pointer-events: none; z-index: 4;
}
.search-input {
    padding-left: 30px !important;
    padding-right: 30px !important;
    background: #f8f9fa;
    border-radius: 8px !important;
    border-color: #dee2e6;
    transition: border-color .2s, box-shadow .2s, background .2s;
}
.search-input:focus {
    background: #fff;
    border-color: #86b7fe;
    box-shadow: 0 0 0 3px rgba(13,110,253,.12);
}
.search-clear {
    position: absolute;
    right: 9px; top: 50%;
    transform: translateY(-50%);
    font-size: 13px; color: #adb5bd;
    text-decoration: none; z-index: 4; line-height: 1;
}
.search-clear:hover { color: #6c757d; }

/* ── Pagination ── */
.pagination  { margin-bottom: 0; }
.page-link   { font-size: .8rem; padding: .3rem .6rem; }
.table td, .table th { vertical-align: middle; }

/* ── Mobile ── */
@media (max-width: 575px) {
    .rc-count   { font-size: 34px; }
    .role-card  { padding: 22px 20px 20px; }
    .search-box { width: 100%; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    // Smooth scroll ke table saat halaman load dengan role aktif
    var section = document.getElementById('tableSection');
    if (section) {
        setTimeout(function () {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 120);
    }

    // Auto-submit search dengan debounce 420ms
    var searchInput = document.getElementById('searchInput');
    if (searchInput) {
        var timer;
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                document.getElementById('searchForm').submit();
            }, 420);
        });
    }

    // Loading state saat role card diklik
    document.querySelectorAll('.role-card').forEach(function (card) {
        card.addEventListener('click', function () {
            if (!this.classList.contains('is-active')) {
                this.style.opacity = '.7';
                this.style.pointerEvents = 'none';
            }
        });
    });
})();
</script>
@endpush
