<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

<aside class="fixed inset-y-0 left-0 z-50 flex w-[17rem] flex-col border-r border-white/[0.07] bg-[#071022] text-slate-200 shadow-2xl shadow-slate-950/20 transition-all duration-300"
       :class="[(sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'), (sidebarCollapsed ? 'lg:w-20' : 'lg:w-[17rem]')]">
    <div class="flex h-16 items-center gap-3 border-b border-white/[0.07] px-4">
        <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Logo Mantau Umroh" class="size-10 shrink-0 rounded-xl object-contain shadow-lg shadow-blue-950/60 ring-1 ring-white/10">
        <div x-show="!sidebarCollapsed" x-transition>
            <p class="text-sm font-bold tracking-tight text-white">Mantau Umroh</p>
            <p class="mt-0.5 text-[11px] text-slate-400">Monitoring & Control Center</p>
        </div>
        <button class="ml-auto rounded-lg p-2 text-slate-400 hover:bg-slate-800 lg:hidden" @click="sidebarOpen = false" aria-label="Tutup sidebar">
            <i data-lucide="x" class="size-5"></i>
        </button>
    </div>

    <nav class="flex-1 space-y-3 overflow-y-auto px-3 py-5">
        <div>
            <p x-show="!sidebarCollapsed" class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-600">Utama</p>
            <a href="{{ route('dashboard') }}" title="Dashboard" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
                <i data-lucide="layout-dashboard" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed">Dashboard</span>
            </a>
        </div>

        @php
            $masterMenus = [
                ['label' => 'Admin Cabang', 'resource' => 'branch-admins', 'permission' => 'branch-admins.manage', 'view' => 'branch-admins.manage'],
                ['label' => 'Jamaah', 'resource' => 'pilgrims', 'permission' => 'pilgrims.manage', 'view' => 'pilgrims.view'],
                ['label' => 'Tour Leader', 'resource' => 'tour-leaders', 'permission' => 'tour-leaders.manage', 'view' => 'tour-leaders.view'],
                ['label' => 'Muthawwif', 'resource' => 'muthawwifs', 'permission' => 'muthawwifs.manage', 'view' => 'muthawwifs.view'],
                ['label' => 'Rombongan', 'resource' => 'groups', 'permission' => 'groups.manage', 'view' => 'groups.view'],
            ];
            $organizationMenus = [
                ['label' => 'Cabang', 'resource' => 'branches', 'permission' => 'branches.manage', 'view' => 'branches.manage'],
            ];
        @endphp
        <div x-data="{ open: {{ request()->routeIs('master-data.*') && in_array(request()->route('resource'), array_column($masterMenus, 'resource'), true) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="users" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Data Master</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-cloak x-show="open && !sidebarCollapsed" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-slate-800 pl-5">
                @foreach ($masterMenus as $menu)
                    @canany([$menu['permission'], $menu['view']])
                        <a href="{{ route('master-data.index', $menu['resource']) }}"
                           class="sidebar-submenu-link {{ request()->route('resource') === $menu['resource'] ? 'sidebar-submenu-link-active' : '' }}">
                            {{ $menu['label'] }}
                        </a>
                    @endcanany
                @endforeach
            </div>
        </div>

        @can('branches.manage')
            <div x-data="{ open: {{ request()->routeIs('master-data.*') && request()->route('resource') === 'branches' ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-link w-full">
                    <i data-lucide="building-2" class="size-5 shrink-0"></i>
                    <span x-show="!sidebarCollapsed" class="flex-1 text-left">Organisasi</span>
                    <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-cloak x-show="open && !sidebarCollapsed" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-slate-800 pl-5">
                    @foreach ($organizationMenus as $menu)
                        <a href="{{ route('master-data.index', $menu['resource']) }}"
                           class="sidebar-submenu-link {{ request()->route('resource') === $menu['resource'] ? 'sidebar-submenu-link-active' : '' }}">
                            {{ $menu['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endcan

        <div x-data="{ open: {{ request()->routeIs('monitoring.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="map" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Monitoring</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-cloak x-show="open && !sidebarCollapsed" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-slate-800 pl-5">
                <a href="{{ route('monitoring.map.index') }}" class="sidebar-submenu-link {{ request()->routeIs('monitoring.map.*') ? 'sidebar-submenu-link-active' : '' }}">Live Map</a>
                <a href="{{ route('monitoring.tracking.index') }}" class="sidebar-submenu-link {{ request()->routeIs('monitoring.tracking.*') ? 'sidebar-submenu-link-active' : '' }}">Histori Tracking</a>
                <a href="{{ route('monitoring.sos.index') }}" class="sidebar-submenu-link {{ request()->routeIs('monitoring.sos.*') ? 'sidebar-submenu-link-active' : '' }}">SOS Jamaah</a>
            </div>
        </div>

        <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="book-open" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Laporan</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-cloak x-show="open && !sidebarCollapsed" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-slate-800 pl-5">
                @foreach (['all' => 'Gabungan', 'pilgrims' => 'Jamaah', 'tracking' => 'Tracking', 'sos' => 'SOS'] as $reportType => $reportLabel)
                    <a href="{{ route('reports.index', $reportType) }}" class="sidebar-submenu-link {{ request()->route('type') === $reportType ? 'sidebar-submenu-link-active' : '' }}">{{ $reportLabel }}</a>
                @endforeach
            </div>
        </div>

        <div x-data="{ open: {{ request()->routeIs('profile.*', 'settings.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="settings" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Pengaturan</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-cloak x-show="open && !sidebarCollapsed" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-slate-800 pl-5">
                <a href="{{ route('profile.edit') }}" class="sidebar-submenu-link {{ request()->routeIs('profile.*') ? 'sidebar-submenu-link-active' : '' }}">Profil</a>
                <a href="{{ route('settings.password') }}" class="sidebar-submenu-link {{ request()->routeIs('settings.password') ? 'sidebar-submenu-link-active' : '' }}">Password</a>
                @can('system-settings.manage')
                    <a href="{{ route('settings.system.edit') }}" class="sidebar-submenu-link {{ request()->routeIs('settings.system.*') ? 'sidebar-submenu-link-active' : '' }}">Sistem</a>
                @endcan
            </div>
        </div>
    </nav>

    <div class="border-t border-white/[0.07] p-3">
        <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.sidebarCollapsed = sidebarCollapsed" class="sidebar-link hidden w-full lg:flex" :title="sidebarCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'">
            <span x-show="!sidebarCollapsed"><i data-lucide="panel-left-close" class="size-5 shrink-0"></i></span>
            <span x-cloak x-show="sidebarCollapsed"><i data-lucide="panel-left-open" class="size-5 shrink-0"></i></span>
            <span x-show="!sidebarCollapsed">Ciutkan sidebar</span>
        </button>
    </div>
</aside>
