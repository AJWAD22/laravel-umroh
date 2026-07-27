@php
    $tabs = ['' => 'Semua', 'new' => 'Baru', 'active' => 'Ditangani', 'resolved' => 'Selesai'];
    $statusClass = [
        'new' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-200',
        'handling' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
        'acknowledged' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
        'assigned' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
        'on_the_way' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
        'arrived' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
        'resolved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
        'cancelled' => 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-200',
        'false_alarm' => 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-200',
    ];
    $statusLabel = ['new' => 'Baru', 'handling' => 'Ditangani', 'acknowledged' => 'Ditangani', 'assigned' => 'Ditugaskan', 'on_the_way' => 'Menuju Lokasi', 'arrived' => 'Sudah Tiba', 'resolved' => 'Selesai', 'cancelled' => 'Dibatalkan', 'false_alarm' => 'Salah Tekan'];
    $activeTotal = ($summary['new'] ?? 0) + ($summary['handling'] ?? 0);
@endphp

<x-app-layout>
    <x-slot:title>Monitoring SOS</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Monitoring / SOS Jamaah</nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">Pusat Respons SOS</h1>
                <p class="mt-1 text-sm text-slate-500">Pantau laporan darurat, hubungi jamaah, lalu catat hasil penanganan sebelum menutup laporan.</p>
            </div>
            <a href="{{ route('monitoring.map.index') }}" class="button-secondary">
                <i data-lucide="map" class="size-4"></i>
                Buka Live Map
            </a>
        </div>
    </x-slot:header>

    <section class="mb-4 grid gap-3 md:grid-cols-4">
        <article class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950/40 dark:text-red-100">
            <p class="text-xs font-bold uppercase tracking-wide">Perlu Respons</p>
            <strong class="mt-2 block text-3xl">{{ $activeTotal }}</strong>
            <span class="text-xs">Baru dan sedang ditangani</span>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Baru</p>
            <strong class="mt-2 block text-3xl text-red-600">{{ $summary['new'] }}</strong>
            <span class="text-xs text-slate-500">Belum ditangani petugas</span>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Ditangani</p>
            <strong class="mt-2 block text-3xl text-amber-600">{{ $summary['handling'] }}</strong>
            <span class="text-xs text-slate-500">Sedang direspons petugas</span>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Selesai</p>
            <strong class="mt-2 block text-3xl text-emerald-600">{{ $summary['resolved'] }}</strong>
            <span class="text-xs text-slate-500">Sudah ditandai aman</span>
        </article>
    </section>

    <section class="mb-4 grid gap-3 lg:grid-cols-4">
        @foreach ([
            ['1', 'Buka detail', 'Lihat posisi terakhir, rombongan, dan kontak jamaah.'],
            ['2', 'Hubungi jamaah', 'Gunakan WhatsApp atau koordinasikan dengan petugas rombongan.'],
            ['3', 'Tangani di lapangan', 'Tour Leader menandai SOS sebagai ditangani dari aplikasi mobile.'],
            ['4', 'Tutup dengan catatan', 'Admin Cabang menutup laporan setelah jamaah aman.'],
        ] as $step)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="grid size-8 place-items-center rounded-xl bg-blue-50 text-sm font-extrabold text-blue-700 dark:bg-blue-950 dark:text-blue-200">{{ $step[0] }}</span>
                <h2 class="mt-3 text-sm font-extrabold text-slate-950 dark:text-white">{{ $step[1] }}</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $step[2] }}</p>
            </article>
        @endforeach
    </section>

    <nav class="mb-4 flex gap-2 overflow-x-auto pb-1">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('monitoring.sos.index', array_filter(['status' => $value])) }}" class="whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold {{ ($status ?? '') === $value ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 shadow-sm dark:bg-slate-900' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/60">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Jamaah</th>
                        <th class="px-4 py-3">Rombongan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Petugas</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($reports as $report)
                        @php
                            $phone = preg_replace('/\D+/', '', $report->pilgrim?->phone ?? '');
                            $waPhone = str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone;
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="font-semibold">{{ $report->reported_at?->format('d M Y H:i') }}</div>
                                <div class="text-xs text-slate-500">{{ $report->reported_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold">{{ $report->pilgrim?->full_name ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $report->pilgrim?->registration_number ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ $report->group?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $report->group?->code ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass[$report->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabel[$report->status] ?? $report->status }}</span></td>
                            <td class="px-4 py-3">{{ $report->handler?->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    @if($waPhone)
                                        <a href="https://wa.me/{{ $waPhone }}" target="_blank" rel="noopener" class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-300">
                                            WhatsApp
                                        </a>
                                    @endif
                                    <a href="{{ route('monitoring.sos.show', $report) }}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="shield-check" title="Tidak ada laporan SOS" description="Laporan darurat akan muncul di sini saat jamaah menekan tombol SOS dari aplikasi mobile." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $reports->links() }}</div>
    </section>
</x-app-layout>
