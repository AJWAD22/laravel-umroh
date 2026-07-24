<x-app-layout>
    <x-slot:title>{{ $isNational ? 'Pusat Kendali Nasional' : 'Dashboard Cabang' }}</x-slot:title>
    <x-slot:header>
        <section class="relative isolate overflow-hidden rounded-[1.75rem] bg-[#071827] p-6 text-white shadow-2xl shadow-slate-950/10 sm:p-8">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_15%,rgba(20,184,166,.25),transparent_28%),radial-gradient(circle_at_15%_90%,rgba(37,99,235,.22),transparent_26%)]"></div>
            <div class="absolute inset-0 opacity-[.06]" style="background-image:linear-gradient(rgba(255,255,255,.6) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.6) 1px,transparent 1px);background-size:42px 42px"></div>
            <div class="relative flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
                <div><div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[.14em] text-teal-200"><span class="size-2 rounded-full bg-emerald-400"></span>{{ $isNational ? 'National Control Center' : 'Branch Operations Center' }}</div><p class="text-sm text-slate-400">{{ now()->translatedFormat('l, d F Y') }}</p><h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">{{ $isNational ? 'Pusat Kendali Nasional' : $scopeLabel }}</h1><p class="mt-3 max-w-2xl leading-7 text-slate-300">{{ $isNational ? 'Lihat ringkasan tata kelola, perjalanan, kapasitas, dan kesehatan sistem tanpa membuka lokasi individu jamaah.' : 'Selesaikan pendaftaran, pembayaran, keberangkatan, dan kebutuhan jamaah cabang secara terarah.' }}</p></div>
                <div class="grid grid-cols-2 gap-3 sm:flex"><div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status Sistem</p><p class="mt-1 flex items-center gap-2 text-sm font-extrabold"><span class="size-2 rounded-full bg-emerald-400"></span>Operasional</p></div><div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pembaruan</p><p class="mt-1 text-sm font-extrabold">{{ now()->format('H:i') }} WITA</p></div></div>
            </div>
        </section>
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

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6" aria-label="Ringkasan operasional">
        @foreach ($cards as $card)
            <article class="surface-card relative overflow-hidden p-5 transition duration-200 hover:-translate-y-1 hover:shadow-xl"><div class="absolute inset-x-0 top-0 h-1 {{ $colorClasses[$card['color']]['accent'] }}"></div><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[.08em] text-slate-500">{{ $card['label'] }}</p><p class="mt-3 text-3xl font-black tracking-tight">{{ number_format($card['value']) }}</p></div><span class="grid size-11 place-items-center rounded-2xl {{ $colorClasses[$card['color']]['icon'] }}"><i data-lucide="{{ $card['icon'] }}" class="size-5"></i></span></div></article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
        <article class="surface-card p-5 sm:p-6"><div class="flex items-center justify-between"><div><p class="text-xs font-bold uppercase tracking-[.14em] text-blue-600">Pekerjaan Prioritas</p><h2 class="mt-1 text-xl font-extrabold">Perlu Ditindaklanjuti</h2></div><span class="travel-chip">Hari ini</span></div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @php $priorityItems = $isNational ? [
                    ['label'=>'Cabang aktif','value'=>$priorities['branches'],'help'=>'Kelola struktur cabang travel','icon'=>'building-2','route'=>route('master-data.index', 'branches'),'iconClass'=>'bg-blue-50 text-blue-600','hoverClass'=>'hover:border-blue-200 hover:bg-blue-50/50'],
                    ['label'=>'Admin cabang','value'=>$priorities['branchAdmins'],'help'=>'Pastikan setiap cabang punya pengelola','icon'=>'shield-check','route'=>route('master-data.index', 'branch-admins'),'iconClass'=>'bg-cyan-50 text-cyan-600','hoverClass'=>'hover:border-cyan-200 hover:bg-cyan-50/50'],
                    ['label'=>'Laporan agregat','value'=>$priorities['departures'],'help'=>'Ringkasan nasional tanpa data operasional individu','icon'=>'book-open','route'=>route('reports.index','all'),'iconClass'=>'bg-violet-50 text-violet-600','hoverClass'=>'hover:border-violet-200 hover:bg-violet-50/50'],
                    ['label'=>'Audit sistem','value'=>$priorities['auditLogs'],'help'=>'Jejak aktivitas sistem dan cabang','icon'=>'history','route'=>route('audit-logs.index'),'iconClass'=>'bg-amber-50 text-amber-600','hoverClass'=>'hover:border-amber-200 hover:bg-amber-50/50'],
                ] : [
                    ['label'=>'Pendaftaran baru','value'=>$priorities['registrations'],'help'=>'Periksa kelengkapan biodata','icon'=>'clipboard-list','route'=>route('registrations.index', ['status'=>'submitted']),'iconClass'=>'bg-blue-50 text-blue-600','hoverClass'=>'hover:border-blue-200 hover:bg-blue-50/50'],
                    ['label'=>'Menunggu pembayaran','value'=>$priorities['payments'],'help'=>'Verifikasi pembayaran cabang','icon'=>'wallet','route'=>route('registrations.index', ['payment_status'=>'pending_branch_payment']),'iconClass'=>'bg-amber-50 text-amber-600','hoverClass'=>'hover:border-amber-200 hover:bg-amber-50/50'],
                    ['label'=>'SOS aktif','value'=>$priorities['sos'],'help'=>'Prioritas keselamatan jamaah','icon'=>'siren','route'=>route('monitoring.sos.index'),'iconClass'=>'bg-red-50 text-red-600','hoverClass'=>'hover:border-red-200 hover:bg-red-50/50'],
                    ['label'=>'Perjalanan aktif','value'=>$priorities['departures'],'help'=>'Jadwal dan rombongan berjalan','icon'=>'plane','route'=>route('master-data.index', ['resource'=>'departures','status'=>'scheduled']),'iconClass'=>'bg-violet-50 text-violet-600','hoverClass'=>'hover:border-violet-200 hover:bg-violet-50/50'],
                ]; @endphp
                @foreach ($priorityItems as $item)
                    <a href="{{ $item['route'] }}" class="group flex items-center gap-4 rounded-2xl border border-slate-200 p-4 transition {{ $item['hoverClass'] }} dark:border-slate-800 dark:hover:bg-slate-800"><span class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $item['iconClass'] }} dark:bg-slate-800"><i data-lucide="{{ $item['icon'] }}" class="size-5"></i></span><span class="min-w-0 flex-1"><strong class="block text-sm">{{ $item['label'] }}</strong><small class="mt-1 block truncate text-slate-500">{{ $item['help'] }}</small></span><strong class="text-2xl">{{ number_format($item['value']) }}</strong></a>
                @endforeach
            </div>
        </article>

        <article class="surface-card p-5 sm:p-6"><p class="text-xs font-bold uppercase tracking-[.14em] text-teal-600">Akses Cepat</p><h2 class="mt-1 text-xl font-extrabold">Menu Utama</h2><div class="mt-5 grid grid-cols-2 gap-3">
            @php $quickActions = $isNational ? [
                ['Data Cabang', 'building-2', route('master-data.index','branches')], ['Admin Cabang', 'shield-check', route('master-data.index','branch-admins')], ['Profil Travel', 'settings', route('settings.system.edit')], ['Audit Sistem', 'history', route('audit-logs.index')],
            ] : [
                ['Pendaftaran', 'clipboard-list', route('registrations.index')], ['Live Map', 'map', route('monitoring.map.index')], ['Jadwal', 'plane', route('master-data.index','departures')], ['Rombongan', 'users-round', route('master-data.index','groups')],
            ]; @endphp
            @foreach ($quickActions as $action)<a href="{{ $action[2] }}" class="grid min-h-24 place-items-center rounded-2xl border border-slate-200 p-3 text-center transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 dark:border-slate-800 dark:hover:bg-slate-800"><i data-lucide="{{ $action[1] }}" class="size-5 text-blue-600"></i><span class="mt-2 text-xs font-extrabold">{{ $action[0] }}</span></a>@endforeach
        </div></article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,.55fr)]">
        <article class="surface-card p-5 sm:p-6"><div class="flex items-center justify-between"><div><h2 class="text-lg font-extrabold">Tren Operasional 6 Bulan</h2><p class="mt-1 text-sm text-slate-500">Pertumbuhan jamaah dan rombongan baru.</p></div><span class="hidden travel-chip sm:inline-flex">Analitik</span></div><div class="mt-6 h-80"><canvas id="dashboard-statistics-chart" aria-label="Grafik statistik enam bulan"></canvas></div></article>
        @if ($isNational)
            <article class="surface-card p-6"><p class="text-xs font-bold uppercase tracking-[.14em] text-blue-600">Tata Kelola Nasional</p><h2 class="mt-1 text-lg font-extrabold">Privasi Operasional Cabang</h2><p class="mt-4 text-sm leading-6 text-slate-600">Lokasi individu, histori tracking, dan detail SOS ditangani oleh Admin Cabang. Pusat hanya menerima ringkasan agregat untuk pengawasan sistem.</p><a href="{{ route('reports.index','all') }}" class="button-secondary mt-5">Buka Rekap Nasional</a></article>
        @else
            <article class="surface-card overflow-hidden"><div class="border-b border-slate-200 p-5 dark:border-slate-800"><div class="flex items-center justify-between"><div><p class="text-xs font-bold uppercase tracking-[.14em] text-red-600">Kondisi Darurat</p><h2 class="mt-1 text-lg font-extrabold">SOS Aktif</h2></div><a href="{{ route('monitoring.sos.index') }}" class="text-xs font-bold text-blue-600">Lihat Semua</a></div></div><div class="divide-y divide-slate-100 dark:divide-slate-800">@forelse ($recentSos as $sos)<a href="{{ route('monitoring.sos.show', $sos) }}" class="flex items-center gap-3 p-4 transition hover:bg-red-50/50 dark:hover:bg-red-950/10"><span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-950/40"><i data-lucide="siren" class="size-4"></i></span><span class="min-w-0 flex-1"><strong class="block truncate text-sm">{{ $sos->pilgrim?->full_name }}</strong><small class="mt-1 block truncate text-slate-500">{{ $sos->branch?->name }} · {{ $sos->reported_at?->diffForHumans() }}</small></span><span class="rounded-full bg-red-50 px-2 py-1 text-[10px] font-bold uppercase text-red-700">{{ $sos->status }}</span></a>@empty<div class="p-8 text-center"><span class="mx-auto grid size-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-600"><i data-lucide="circle-check" class="size-5"></i></span><p class="mt-3 text-sm font-extrabold">Tidak ada SOS aktif</p><p class="mt-1 text-xs text-slate-500">Kondisi operasional saat ini aman.</p></div>@endforelse</div></article>
        @endif
    </section>

    <section class="mt-6 surface-card p-5 sm:p-6"><div class="flex flex-col gap-5 lg:flex-row lg:items-center"><div class="min-w-0 flex-1"><p class="text-xs font-bold uppercase tracking-[.14em] text-slate-500">Kesehatan GPS</p><h2 class="mt-1 text-lg font-extrabold">{{ $isNational ? 'Ringkasan Agregat Perangkat' : 'Ringkasan Perangkat Jamaah' }}</h2></div>@foreach ([['GPS Online',$monitoring['online'],'bg-emerald-500'],['GPS Offline',$monitoring['offline'],'bg-red-500'],['Belum Diketahui',$monitoring['unknown'],'bg-slate-400']] as $item)<div class="flex min-w-48 items-center rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800"><span class="size-2.5 rounded-full {{ $item[2] }}"></span><span class="ml-3 text-xs font-semibold text-slate-500">{{ $item[0] }}</span><strong class="ml-auto text-xl">{{ number_format($item[1]) }}</strong></div>@endforeach@if (! $isNational)<a href="{{ route('monitoring.map.index') }}" class="button-primary shrink-0">Buka Monitoring</a>@endif</div></section>

    <script>window.dashboardChartData = {{ Illuminate\Support\Js::from($chart) }};</script>
</x-app-layout>
