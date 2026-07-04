<header class="sticky top-0 z-30 flex h-20 items-center border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sm:px-6 lg:px-8">
    <button @click="sidebarOpen = true" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Buka sidebar">
        <i data-lucide="menu" class="size-6"></i>
    </button>

    <div class="ml-3 lg:ml-0">
        <p class="text-sm text-slate-500 dark:text-slate-400">Panel Administrasi</p>
        <p class="font-semibold">{{ auth()->user()->hasRole('super-admin') ? 'Nasional' : 'Cabang' }}</p>
    </div>

    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <button @click="dark = !dark; localStorage.theme = dark ? 'dark' : 'light'" class="navbar-button" aria-label="Ubah tema">
            <i data-lucide="moon" class="size-5"></i>
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
            <button @click="open = !open" class="flex items-center gap-3 rounded-xl p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800">
                @if (auth()->user()->photo_path)
                    <img src="{{ asset('storage/'.auth()->user()->photo_path) }}" alt="{{ auth()->user()->name }}" class="size-9 rounded-xl object-cover">
                @else
                    <span class="grid size-9 place-items-center rounded-xl bg-blue-100 font-semibold text-blue-700">{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</span>
                @endif
                <span class="hidden text-left sm:block">
                    <span class="block text-sm font-semibold">{{ auth()->user()->name }}</span>
                    <span class="block text-xs text-slate-500">{{ auth()->user()->getRoleNames()->first() }}</span>
                </span>
                <i data-lucide="chevron-down" class="hidden size-4 sm:block"></i>
            </button>
            <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</header>
