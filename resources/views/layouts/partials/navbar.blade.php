<header class="sticky top-0 z-30 flex h-16 items-center border-b border-slate-200/70 bg-white/80 px-4 shadow-[0_1px_0_rgba(15,23,42,0.02)] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/80 sm:px-6 lg:px-8">
    <button @click="sidebarOpen = true" class="navbar-button mr-2 lg:hidden" aria-label="Buka sidebar">
        <i data-lucide="menu" class="size-6"></i>
    </button>

    <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">Panel Administrasi</p>
        <div class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <span class="size-1.5 rounded-full bg-emerald-500 ring-4 ring-emerald-500/10"></span>
            <span>{{ auth()->user()->hasRole('super-admin') ? 'Operasional Nasional' : (auth()->user()->branch?->name ?? 'Operasional Cabang') }}</span>
        </div>
    </div>

    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <button @click="dark = !dark; localStorage.theme = dark ? 'dark' : 'light'" class="navbar-button" :aria-label="dark ? 'Gunakan tema terang' : 'Gunakan tema gelap'">
            <span x-show="!dark"><i data-lucide="moon" class="size-5"></i></span>
            <span x-cloak x-show="dark"><i data-lucide="sun" class="size-5"></i></span>
        </button>
        @php $unreadNotificationCount = auth()->user()->unreadNotifications()->count(); @endphp
        <a href="{{ route('notifications.index') }}" class="navbar-button relative" aria-label="Notifikasi">
            <i data-lucide="bell" class="size-5"></i>
            @if ($unreadNotificationCount > 0)
                <span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-5 text-white ring-2 ring-white">
                    {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                </span>
            @endif
        </a>

        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @keydown.escape.window="open = false" class="flex items-center gap-3 rounded-xl p-1 hover:bg-slate-100 dark:hover:bg-slate-800" :aria-expanded="open">
                @if (auth()->user()->photo_path)
                    <img src="{{ asset('storage/'.auth()->user()->photo_path) }}" alt="{{ auth()->user()->name }}" class="size-9 rounded-xl object-cover">
                @else
                    <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-blue-100 to-cyan-50 font-semibold text-blue-700 ring-1 ring-blue-200/70 dark:from-blue-950 dark:to-slate-900 dark:text-blue-300 dark:ring-blue-800">{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</span>
                @endif
                <span class="hidden text-left sm:block">
                    <span class="block text-sm font-semibold">{{ auth()->user()->name }}</span>
                    <span class="block text-xs text-slate-500">{{ auth()->user()->getRoleNames()->first() }}</span>
                </span>
                <i data-lucide="chevron-down" class="hidden size-4 sm:block"></i>
            </button>
            <div x-cloak x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/80 bg-white p-2 shadow-2xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-3 py-2.5 dark:border-slate-800 sm:hidden">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm hover:bg-slate-100 dark:hover:bg-slate-800">
                    <i data-lucide="user-round" class="size-4 text-slate-400"></i> Profil Saya
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40">
                        <i data-lucide="log-out" class="size-4"></i> Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
