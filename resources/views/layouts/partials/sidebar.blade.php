<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

<aside class="fixed inset-y-0 left-0 z-50 flex w-[17rem] flex-col border-r border-white/[0.07] bg-[#071022] text-slate-200 shadow-2xl shadow-slate-950/20 transition-all duration-300"
       :class="[(sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'), (sidebarCollapsed ? 'lg:w-20' : 'lg:w-[17rem]')]">
    <div class="flex h-16 items-center gap-3 border-b border-white/[0.07] px-4">
        <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Logo Mantau Umroh" class="size-10 shrink-0 rounded-xl object-contain shadow-lg shadow-blue-950/60 ring-1 ring-white/10">
        <div x-show="!sidebarCollapsed" x-transition class="min-w-0">
            <p class="truncate text-sm font-extrabold tracking-tight text-white">Mantau Umroh</p>
            <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ auth()->user()->hasRole('super-admin') ? 'National Control Center' : 'Branch Operations' }}</p>
        </div>
        <button class="ml-auto rounded-lg p-2 text-slate-400 hover:bg-slate-800 lg:hidden" @click="sidebarOpen = false" aria-label="Tutup sidebar">
            <i data-lucide="x" class="size-5"></i>
        </button>
    </div>

    <div x-show="!sidebarCollapsed" x-transition class="mx-3 mt-4 rounded-2xl border border-white/[0.08] bg-gradient-to-br from-blue-500/15 to-teal-400/10 p-3.5">
        <div class="flex items-center gap-3"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/10 text-blue-300"><i data-lucide="{{ auth()->user()->hasRole('super-admin') ? 'shield-check' : 'building-2' }}" class="size-4.5"></i></span><div class="min-w-0"><p class="text-[10px] font-bold uppercase tracking-[.14em] text-slate-500">Ruang Kerja</p><p class="mt-0.5 truncate text-xs font-extrabold text-slate-200">{{ auth()->user()->hasRole('super-admin') ? 'Pusat Kendali Nasional' : auth()->user()->branch?->name }}</p></div></div>
    </div>

    <nav class="flex-1 space-y-3 overflow-y-auto px-3 py-4">
        <div>
            <p x-show="!sidebarCollapsed" class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-600">Utama</p>
            <a href="{{ route('dashboard') }}" title="Dashboard" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
                <i data-lucide="layout-dashboard" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed">Dashboard</span>
            </a>
        </div>

        @role('admin-cabang')
            <div>
                <p x-show="!sidebarCollapsed" class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-600">Layanan Jamaah</p>
                <a href="{{ route('registrations.index') }}" title="Pendaftaran Jamaah"
                   class="sidebar-link {{ request()->routeIs('registrations.*') ? 'sidebar-link-active' : '' }}">
                    <i data-lucide="clipboard-list" class="size-5 shrink-0"></i>
                    <span x-show="!sidebarCollapsed">Pendaftaran Jamaah</span>
                </a>
            </div>
        @endrole

        @php
            $masterMenus = [
                ['label' => 'Jamaah', 'resource' => 'pilgrims', 'permission' => 'pilgrims.manage', 'view' => 'pilgrims.view'],
                ['label' => 'Tour Leader', 'resource' => 'tour-leaders', 'permission' => 'tour-leaders.manage', 'view' => 'tour-leaders.view'],
                ['label' => 'Muthawwif', 'resource' => 'muthawwifs', 'permission' => 'muthawwifs.manage', 'view' => 'muthawwifs.view'],
                ['label' => 'Rombongan', 'resource' => 'groups', 'permission' => 'groups.manage', 'view' => 'groups.view'],
            ];
            $supportMenus = [
                ['label' => 'Jadwal Perjalanan', 'resource' => 'departures', 'permission' => 'departures.manage', 'view' => 'departures.view'],
                ['label' => 'Hotel', 'resource' => 'hotels', 'permission' => 'hotels.manage', 'view' => 'hotels.view'],
                ['label' => 'Tujuan & Titik Penting', 'resource' => 'checkpoints', 'permission' => 'checkpoints.manage', 'view' => 'checkpoints.view'],
            ];
            $organizationMenus = [
                ['label' => 'Data Cabang', 'resource' => 'branches', 'permission' => 'branches.manage', 'view' => 'branches.manage'],
                ['label' => 'Akun Admin Cabang', 'resource' => 'branch-admins', 'permission' => 'branch-admins.manage', 'view' => 'branch-admins.manage'],
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

        <div x-data="{ open: {{ request()->routeIs('master-data.*') && in_array(request()->route('resource'), array_column($supportMenus, 'resource'), true) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="calendar-range" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Data Pendukung Sistem</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-cloak x-show="open && !sidebarCollapsed" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-slate-800 pl-5">
                @foreach ($supportMenus as $menu)
                    @canany([$menu['permission'], $menu['view']])
                        <a href="{{ route('master-data.index', $menu['resource']) }}"
                           class="sidebar-submenu-link {{ request()->route('resource') === $menu['resource'] ? 'sidebar-submenu-link-active' : '' }}">
                            {{ $menu['label'] }}
                        </a>
                    @endcanany
                @endforeach
            </div>
        </div>

        @canany(['branches.manage', 'branch-admins.manage'])
            <div x-data="{ open: {{ request()->routeIs('master-data.*') && in_array(request()->route('resource'), array_column($organizationMenus, 'resource'), true) ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-link w-full">
                    <i data-lucide="building-2" class="size-5 shrink-0"></i>
                    <span x-show="!sidebarCollapsed" class="flex-1 text-left">Organisasi</span>
                    <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-cloak x-show="open && !sidebarCollapsed" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-slate-800 pl-5">
                    @foreach ($organizationMenus as $menu)
                        @canany([$menu['permission'], $menu['view']])
                            <a href="{{ route('master-data.index', $menu['resource']) }}"
                               class="sidebar-submenu-link {{ request()->route('resource') === $menu['resource'] ? 'sidebar-submenu-link-active' : '' }}">
                                {{ $menu['label'] }}
                            </a>
                        @endcanany
                    @endforeach
                </div>
            </div>
        @endcanany

        @role('admin-cabang')
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
        @endrole

        <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="sidebar-link w-full">
                <i data-lucide="book-open" class="size-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="flex-1 text-left">Laporan</span>
                <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="size-4 transition" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-cloak x-show="open && !sidebarCollapsed" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-slate-800 pl-5">
                <a href="{{ route('reports.index', 'all') }}" class="sidebar-submenu-link {{ request()->route('type') === 'all' || request()->routeIs('reports.home') ? 'sidebar-submenu-link-active' : '' }}">Laporan Gabungan</a>
            </div>
        </div>

        @canany(['audit.global.view', 'audit.branch.view'])
            <div>
                <a href="{{ route('audit-logs.index') }}" title="Audit Log"
                   class="sidebar-link {{ request()->routeIs('audit-logs.*') ? 'sidebar-link-active' : '' }}">
                    <i data-lucide="history" class="size-5 shrink-0"></i>
                    <span x-show="!sidebarCollapsed">Audit Log</span>
                </a>
            </div>
        @endcanany

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
