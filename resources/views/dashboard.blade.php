<x-app-layout>
    <x-slot:title>Dashboard {{ $scopeLabel }}</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Beranda / Dashboard</nav>
                <h1 class="text-2xl font-bold tracking-tight">Dashboard {{ $scopeLabel }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan operasional dan kondisi monitoring terkini.
                </p>
            </div>
            <div class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <span class="relative flex size-2">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                </span>
                Diperbarui {{ now()->format('H:i') }} WITA
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
            <article class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="absolute inset-x-0 top-0 h-1 {{ $colorClasses[$card['color']]['accent'] }}"></div>
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
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <div>
                <h2 class="font-semibold">Statistik 6 Bulan</h2>
                <p class="mt-1 text-sm text-slate-500">Jamaah baru, jadwal keberangkatan, dan laporan SOS.</p>
            </div>
            <div class="mt-6 h-80">
                <canvas id="dashboard-statistics-chart" aria-label="Grafik statistik enam bulan"></canvas>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
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

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="font-semibold">SOS Terbaru</h2>
                <p class="mt-1 text-sm text-slate-500">Laporan darurat terbaru dalam cakupan Anda.</p>
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
                    <x-status-badge :value="$sos->status === 'active' ? 'sos' : $sos->status"
                                    :label="$sos->status === 'active' ? 'Aktif' : null" class="ml-auto" />
                </div>
            @empty
                <x-empty-state icon="shield-check" title="Tidak ada laporan SOS"
                               description="Belum ada laporan darurat dalam cakupan Anda." />
            @endforelse
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="font-semibold">Keberangkatan Mendatang</h2>
                <p class="mt-1 text-sm text-slate-500">Lima jadwal terdekat yang belum berangkat.</p>
            </div>
            @forelse ($upcomingDepartures as $departure)
                <div class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-0 dark:border-slate-800">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60">
                        <i data-lucide="plane" class="size-5"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $departure->program_name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $departure->branch->name }} · {{ $departure->code }}</p>
                    </div>
                    <div class="ml-auto text-right">
                        <p class="text-sm font-semibold">{{ $departure->departure_date->format('d M Y') }}</p>
                        <p class="text-xs text-slate-500">{{ $departure->departure_date->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <x-empty-state icon="plane" title="Belum ada jadwal"
                               description="Keberangkatan mendatang akan ditampilkan di sini." />
            @endforelse
        </article>
    </section>

    <script>
        window.dashboardChartData = {{ Illuminate\Support\Js::from($chart) }};
    </script>
</x-app-layout>
