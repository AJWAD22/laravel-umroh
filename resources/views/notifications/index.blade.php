<x-app-layout>
    <x-slot:title>Notifikasi</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Notifikasi</nav>
                <h1 class="text-2xl font-bold">Pusat Notifikasi</h1>
                <p class="mt-1 text-sm text-slate-500">Perangkat GPS offline dan pelanggaran geofence.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Tandai Semua Dibaca</button>
                </form>
                <form method="POST" action="{{ route('notifications.destroy-all') }}"
                      data-confirm="Hapus semua notifikasi dari daftar Anda?">
                    @csrf @method('DELETE')
                    <button class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 dark:border-red-900/60 dark:hover:bg-red-950/30">Hapus Semua</button>
                </form>
            </div>
        </div>
    </x-slot:header>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex gap-2 border-b border-slate-200 p-4 dark:border-slate-800">
            <a href="{{ route('notifications.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ request('status') !== 'unread' ? 'bg-blue-600 text-white' : 'text-slate-500' }}">Semua</a>
            <a href="{{ route('notifications.index', ['status' => 'unread']) }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ request('status') === 'unread' ? 'bg-blue-600 text-white' : 'text-slate-500' }}">Belum Dibaca</a>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($notifications as $notification)
                @php
                    [$icon, $colors] = match($notification->type) {
                        'gps_offline' => ['G', 'bg-slate-200 text-slate-700'],
                        'geofence_exit' => ['↗', 'bg-amber-100 text-amber-700'],
                        default => ['i', 'bg-blue-100 text-blue-700'],
                    };
                @endphp
                <div class="flex items-start gap-3 px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 {{ $notification->unread() ? 'bg-blue-50/50 dark:bg-blue-950/10' : '' }}">
                    <form method="POST" action="{{ route('notifications.read', $notification) }}" class="min-w-0 flex-1">
                        @csrf @method('PATCH')
                        <button class="flex w-full gap-4 text-left">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl text-lg font-bold {{ $colors }}">{{ $icon }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <strong class="truncate text-sm">{{ $notification->data['title'] ?? 'Notifikasi' }}</strong>
                                    @if ($notification->unread())<i class="size-2 shrink-0 rounded-full bg-blue-600"></i>@endif
                                </span>
                                <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">{{ $notification->data['message'] ?? '-' }}</span>
                                <span class="mt-2 block text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                            </span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('notifications.destroy', $notification) }}"
                          data-confirm="Hapus notifikasi ini?">
                        @csrf @method('DELETE')
                        <button class="rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-900/60 dark:hover:bg-red-950/30">
                            Hapus
                        </button>
                    </form>
                </div>
            @empty
                <x-empty-state icon="bell" title="Belum ada notifikasi"
                               description="Kondisi operasional sedang tenang. Itu kabar bagus." />
            @endforelse
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $notifications->links() }}</div>
    </section>
</x-app-layout>
