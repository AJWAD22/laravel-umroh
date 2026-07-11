<x-app-layout>
    <x-slot:title>Live Map</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Monitoring / Live Map</nav>
                <h1 class="text-2xl font-bold">Monitoring Jamaah</h1>
                <p class="mt-1 text-sm text-slate-500">Pantau posisi terakhir jamaah berdasarkan data GPS aplikasi.</p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Data GPS Langsung</span>
        </div>
    </x-slot:header>

    <section class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['id' => 'monitoring-total', 'label' => 'Total Jamaah', 'color' => 'text-blue-600'],
            ['id' => 'monitoring-online', 'label' => 'Online', 'color' => 'text-emerald-600'],
            ['id' => 'monitoring-offline', 'label' => 'Offline', 'color' => 'text-slate-600'],
            ['id' => 'monitoring-sos', 'label' => 'SOS', 'color' => 'text-red-600'],
        ] as $stat)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                <p id="{{ $stat['id'] }}" class="mt-1 text-2xl font-bold {{ $stat['color'] }}">0</p>
            </div>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div id="monitoring-filters" class="grid gap-3 border-b border-slate-200 p-4 dark:border-slate-800 sm:grid-cols-2 xl:grid-cols-5">
            @if ($canFilterBranches)
                <select id="monitoring-branch" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <option value="">Semua cabang</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @else
                <input type="hidden" id="monitoring-branch" value="{{ auth()->user()->branch_id }}">
                <div class="flex items-center rounded-xl bg-slate-100 px-3 text-sm text-slate-600 dark:bg-slate-800">{{ auth()->user()->branch?->name }}</div>
            @endif

            <select id="monitoring-group" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">Semua rombongan</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" data-branch="{{ $group->branch_id }}">{{ $group->name }}</option>
                @endforeach
            </select>

            <select id="monitoring-status" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">Semua status</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
                <option value="sos">SOS</option>
            </select>

            <select id="monitoring-refresh" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="10000">Refresh 10 detik</option>
                <option value="30000" selected>Refresh 30 detik</option>
                <option value="60000">Refresh 1 menit</option>
                <option value="0">Nonaktifkan refresh</option>
            </select>

            <button id="monitoring-reload" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Muat Ulang</button>
        </div>

        <div class="relative">
            <div id="monitoring-map"
                 data-endpoint="{{ route('monitoring.map.data') }}"
                 class="h-[620px] w-full bg-slate-100"
                 aria-label="Peta monitoring jamaah"></div>
            <div id="monitoring-loading" class="pointer-events-none absolute inset-0 z-[500] hidden place-items-center bg-white/50 backdrop-blur-sm">
                <x-loading-state label="Memuat lokasi jamaah..." class="rounded-2xl bg-white px-5 py-3 shadow-lg dark:bg-slate-900" />
            </div>
            <aside id="monitoring-detail"
                   class="absolute inset-y-4 right-4 z-[600] hidden w-[calc(100%-2rem)] max-w-sm overflow-y-auto rounded-2xl border border-slate-200 bg-white/95 shadow-2xl backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
                <div id="monitoring-detail-content"></div>
            </aside>
            <div class="absolute bottom-5 left-5 z-[500] rounded-xl bg-white/95 p-3 text-xs shadow-lg backdrop-blur dark:bg-slate-900/95">
                <p class="mb-2 font-semibold">Legenda</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                    <span><i class="mr-1 inline-block size-2.5 rounded-full bg-emerald-500"></i> Online</span>
                    <span><i class="mr-1 inline-block size-2.5 rounded-full bg-slate-500"></i> Offline</span>
                    <span><i class="mr-1 inline-block size-2.5 rounded-full bg-red-500"></i> SOS</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800">
            <span id="monitoring-updated">Belum diperbarui</span>
            <span>Sumber peta © OpenStreetMap contributors</span>
        </div>
    </section>
</x-app-layout>
