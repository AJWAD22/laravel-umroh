<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

<aside class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-800 bg-slate-950 text-slate-200 transition-all duration-300"
       :class="[(sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'), (sidebarCollapsed ? 'lg:w-20' : 'lg:w-72')]">
    <div class="flex h-20 items-center gap-3 border-b border-slate-800 px-5">
        <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Logo Mantau Umroh" class="size-11 shrink-0 rounded-2xl object-contain shadow-lg shadow-blue-900/40">
        <div x-show="!sidebarCollapsed" x-transition>
            <p class="font-bold text-white">Umrah Monitor</p>
            <p class="text-xs text-slate-400">GIS Control Center</p>
        </div>
        <button class="ml-auto rounded-lg p-2 text-slate-400 hover:bg-slate-800 lg:hidden" @click="sidebarOpen = false" aria-label="Tutup sidebar">
            <i data-lucide="x" class="size-5"></i>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-6">
        <div>
            <p x-show="!sidebarCollapsed" class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Utama</p>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
                <i data-lucide="layout-dashboard" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed">Dashboard</span>
            </a>
        </div>

        @php
            $masterMenus = [
                ['label' => 'Cabang', 'resource' => 'branches', 'permission' => 'branches.manage', 'view' => 'branches.manage'],
                ['label' => 'Admin Cabang', 'resource' => 'branch-admins', 'permission' => 'branch-admins.manage', 'view' => 'branch-admins.manage'],
                ['label' => 'Jamaah', 'resource' => 'pilgrims', 'permission' => 'pilgrims.manage', 'view' => 'pilgrims.view'],
                ['label' => 'Tour Leader', 'resource' => 'tour-leaders', 'permission' => 'tour-leaders.manage', 'view' => 'tour-leaders.view'],
                ['label' => 'Muthawwif', 'resource' => 'muthawwifs', 'permission' => 'muthawwifs.manage', 'view' => 'muthawwifs.view'],
                ['label' => 'Hotel', 'resource' => 'hotels', 'permission' => 'hotels.manage', 'view' => 'hotels.view'],
                ['label' => 'Keberangkatan', 'resource' => 'departures', 'permission' => 'departures.manage', 'view' => 'departures.view'],
                ['label' => 'Rombongan', 'resource' => 'groups', 'permission' => 'groups.manage', 'view' => 'groups.view'],
            ];
        @endphp
        <div x-data="{ open: {{ request()->routeIs('master-data.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="users" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Master Data</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open && !sidebarCollapsed" x-transition class="ml-10 mt-1 space-y-1">
                @foreach ($masterMenus as $menu)
                    @canany([$menu['permission'], $menu['view']])
                        <a href="{{ route('master-data.index', $menu['resource']) }}"
                           class="block rounded-lg px-3 py-2 text-sm {{ request()->route('resource') === $menu['resource'] ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-white' }}">
                            {{ $menu['label'] }}
                        </a>
                    @endcanany
                @endforeach
            </div>
        </div>

        <div x-data="{ open: {{ request()->routeIs('monitoring.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="map" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Monitoring</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open && !sidebarCollapsed" x-transition class="ml-10 mt-1 space-y-1">
                <a href="{{ route('monitoring.map.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('monitoring.map.*') ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-white' }}">Live Map</a>
                <a href="{{ route('monitoring.tracking.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('monitoring.tracking.*') ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-white' }}">Histori Tracking</a>
                <a href="{{ route('monitoring.sos.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('monitoring.sos.*') ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-white' }}">SOS</a>
            </div>
        </div>

        <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="book-open" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Laporan</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open && !sidebarCollapsed" x-transition class="ml-10 mt-1 space-y-1">
                @foreach (['pilgrims' => 'Jamaah', 'tracking' => 'Tracking', 'sos' => 'SOS', 'departures' => 'Keberangkatan'] as $reportType => $reportLabel)
                    <a href="{{ route('reports.index', $reportType) }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->route('type') === $reportType ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-white' }}">{{ $reportLabel }}</a>
                @endforeach
            </div>
        </div>

        <div x-data="{ open: {{ request()->routeIs('profile.*', 'settings.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="settings" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Pengaturan</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open && !sidebarCollapsed" x-transition class="ml-10 mt-1 space-y-1">
                <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('profile.*') ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-white' }}">Profil</a>
                <a href="{{ route('settings.password') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('settings.password') ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-white' }}">Password</a>
                @can('system-settings.manage')
                    <a href="{{ route('settings.system.edit') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('settings.system.*') ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-white' }}">Sistem</a>
                @endcan
            </div>
        </div>
    </nav>

    <div class="border-t border-slate-800 p-3">
        <button @click="sidebarCollapsed = !sidebarCollapsed" class="sidebar-link hidden w-full lg:flex">
            <i data-lucide="menu" class="size-5 shrink-0"></i>
            <span x-show="!sidebarCollapsed">Ciutkan sidebar</span>
        </button>
    </div>
</aside>
