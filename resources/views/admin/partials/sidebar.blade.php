<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
       href="{{ route('admin.dashboard') }}">
        <i class="bi bi-speedometer2"></i>Dashboard
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
       href="{{ route('admin.users.index') }}">
        <i class="bi bi-people"></i>Data User
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.permintaan.*') ? 'active' : '' }}"
       href="{{ route('admin.permintaan.index') }}">
        <i class="bi bi-card-list"></i>Data Permintaan
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.kendaraan.*') ? 'active' : '' }}"
       href="{{ route('admin.kendaraan.index') }}">
        <i class="bi bi-truck"></i>Data Kendaraan
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.ahp.*') ? 'active' : '' }}"
       href="{{ route('admin.ahp.index') }}">
        <i class="bi bi-calculator"></i>Proses AHP
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.saw.*') ? 'active' : '' }}"
       href="{{ route('admin.saw.index') }}">
        <i class="bi bi-bar-chart"></i>Proses SAW
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.penjadwalan.*') ? 'active' : '' }}"
       href="{{ route('admin.penjadwalan.index') }}">
        <i class="bi bi-calendar3"></i>Penjadwalan
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}"
       href="{{ route('admin.laporan.index') }}">
        <i class="bi bi-file-earmark-text"></i>Laporan
    </a>
</li>
<li class="nav-item">
    @php $dispatcherUnread = \App\Models\Notifikasi::unreadCount(auth()->id()); @endphp
    <a class="nav-link {{ request()->routeIs('notifikasi.*') ? 'active' : '' }}"
       href="{{ route('notifikasi.index') }}">
        <i class="bi bi-bell"></i>Notifikasi
        @if($dispatcherUnread > 0)
            <span class="badge bg-danger ms-1" style="font-size:.65rem">
                {{ $dispatcherUnread > 9 ? '9+' : $dispatcherUnread }}
            </span>
        @endif
    </a>
</li>
