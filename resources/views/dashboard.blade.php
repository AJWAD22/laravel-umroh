<x-app-layout>
    <x-slot:title>Dashboard {{ $scopeLabel }}</x-slot:title>
    <x-slot:header>
        <div class="travel-panel overflow-hidden p-5 sm:p-6">
            <div class="absolute -right-12 -top-16 size-44 rounded-full bg-blue-400/20 blur-3xl"></div>
            <div class="absolute right-24 top-8 size-28 rounded-full bg-emerald-300/20 blur-2xl"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="travel-chip mb-4 w-fit">
                        <i data-lucide="plane" class="size-4 text-blue-600"></i>
                        Travel Operations Center
                    </div>
                    <nav class="mb-2 text-sm font-medium text-slate-500">Beranda / Dashboard</nav>
                    <h1 class="text-3xl font-black tracking-tight text-slate-950 dark:text-white">Dashboard {{ $scopeLabel }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Ringkasan jamaah, petugas, rombongan, monitoring GPS, dan laporan darurat dalam satu panel.
                    </p>
                </div>
                <div class="inline-flex items-center gap-2 self-start rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                    <span class="relative flex size-2.5">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span>
                    </span>
                    Diperbarui {{ now()->format('H:i') }} WITA
                </div>
            </div>
        </div>
    </x-slot:header>

    @php
        $colorClasses = [
            'blue' => ['icon' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/60', 'accent' => 'bg-blue-500'],
            'cyan' => ['icon' => 'bg-cyan-50 text-cyan-600 dark:bg-cyan-950/60', 'accent' => 'bg-cyan-500'],
            'emerald' => ['icon' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60', 'accent' => 'bg-emerald-500'],
            'violet' => ['icon' => 'bg-violet-50 text-violet-600 dark:bg-violet-950/60', 'accent' => 'bg-violet-500'],
            'amber' => ['icon' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/60', 'accent' => 'bg-amber-500'],
            'red' => ['icon' => 'bg-red-50 text-red-600 dark:bg-red-950/60', 'accent' => 'bg-red-500'],
            'indigo' => ['icon' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60', 'accent' => 'bg-indigo-500'],
        ];
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <article class="surface-card relative overflow-hidden p-5 transition hover:-translate-y-0.5 hover:shadow-xl dark:bg-slate-900">
                <div class="absolute inset-x-0 top-0 h-1.5 {{ $colorClasses[$card['color']]['accent'] }}"></div>
                <div class="absolute -right-8 -top-10 size-24 rounded-full {{ $colorClasses[$card['color']]['accent'] }} opacity-10 blur-2xl"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight">{{ number_format($card['value']) }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-xl {{ $colorClasses[$card['color']]['icon'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="size-5"></i>
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
        <article class="surface-card p-5 sm:p-6">
            <div>
                <h2 class="font-semibold">Statistik 6 Bulan</h2>
                <p class="mt-1 text-sm text-slate-500">Jamaah baru, rombongan baru, dan laporan SOS.</p>
            </div>
            <div class="mt-6 h-80">
                <canvas id="dashboard-statistics-chart" aria-label="Grafik statistik enam bulan"></canvas>
            </div>
        </article>

        <article class="surface-card p-5 sm:p-6">
            <h2 class="font-semibold">Ringkasan Monitoring</h2>
            <p class="mt-1 text-sm text-slate-500">Status GPS terakhir dari perangkat jamaah.</p>

            <div class="mt-6 space-y-4">
                @foreach ([
                    ['label' => 'GPS Online', 'value' => $monitoring['online'], 'dot' => 'bg-emerald-500'],
                    ['label' => 'GPS Offline', 'value' => $monitoring['offline'], 'dot' => 'bg-red-500'],
                    ['label' => 'Status Belum Diketahui', 'value' => $monitoring['unknown'], 'dot' => 'bg-slate-400'],
                    ['label' => 'SOS Aktif', 'value' => $monitoring['active_sos'], 'dot' => 'bg-amber-500'],
                ] as $item)
                    <div class="flex items-center rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                        <span class="size-2.5 rounded-full {{ $item['dot'] }}"></span>
                        <span class="ml-3 text-sm text-slate-600 dark:text-slate-300">{{ $item['label'] }}</span>
                        <span class="ml-auto text-lg font-bold">{{ number_format($item['value']) }}</span>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="mt-6">
        <article class="surface-card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="font-semibold">SOS Aktif Terbaru</h2>
                <p class="mt-1 text-sm text-slate-500">Hanya menampilkan laporan yang belum diamankan.</p>
            </div>
            @forelse ($recentSos as $sos)
                <div class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-0 dark:border-slate-800">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-red-50 text-red-600 dark:bg-red-950/60">
                        <i data-lucide="siren" class="size-5"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $sos->pilgrim->full_name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $sos->branch->name }} · {{ $sos->reported_at->diffForHumans() }}</p>
                    </div>
                    <div class="ml-auto flex shrink-0 items-center gap-3">
                        <x-status-badge :value="$sos->status === 'active' ? 'sos' : $sos->status"
                                        :label="$sos->status === 'active' ? 'Aktif' : null" />
                        <a href="{{ route('monitoring.sos.show', $sos) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                            Detail
                        </a>
                        @can('update', $sos)
                            <form method="POST" action="{{ route('monitoring.sos.resolve', $sos) }}"
                                  data-confirm-title="Tandai Jamaah Aman"
                                  data-confirm-message="Laporan SOS akan diselesaikan dan otomatis hilang dari dashboard aktif.">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">
                                    Aman
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <x-empty-state icon="shield-check" title="Tidak ada laporan SOS"
                               description="Semua laporan darurat dalam cakupan Anda sudah aman." />
            @endforelse
        </article>

    </section>

    <script>
        window.dashboardChartData = {{ Illuminate\Support\Js::from($chart) }};
    </script>
</x-app-layout>
