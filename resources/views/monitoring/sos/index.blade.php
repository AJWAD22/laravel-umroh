@php
    $tabs = ['' => 'Semua', 'new' => 'Baru', 'handling' => 'Ditangani', 'resolved' => 'Selesai'];
    $statusClass = [
        'new' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-200',
        'handling' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
        'resolved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
    ];
    $statusLabel = ['new' => 'Baru', 'handling' => 'Ditangani', 'resolved' => 'Selesai'];
@endphp

<x-app-layout>
    <x-slot:title>Monitoring SOS</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Monitoring / SOS</nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">Monitoring SOS</h1>
                <p class="mt-1 text-sm text-slate-500">Pantau permintaan bantuan jamaah dan status penanganannya.</p>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-2xl bg-red-50 px-4 py-3 text-red-700 dark:bg-red-950 dark:text-red-200"><strong class="block text-xl">{{ $summary['new'] }}</strong><span class="text-xs">Baru</span></div>
                <div class="rounded-2xl bg-amber-50 px-4 py-3 text-amber-700 dark:bg-amber-950 dark:text-amber-200"><strong class="block text-xl">{{ $summary['handling'] }}</strong><span class="text-xs">Ditangani</span></div>
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200"><strong class="block text-xl">{{ $summary['resolved'] }}</strong><span class="text-xs">Selesai</span></div>
            </div>
        </div>
    </x-slot:header>

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
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="whitespace-nowrap px-4 py-3">{{ $report->reported_at?->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold">{{ $report->pilgrim?->full_name ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $report->pilgrim?->registration_number ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $report->group?->name ?? '-' }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass[$report->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabel[$report->status] ?? $report->status }}</span></td>
                            <td class="px-4 py-3">{{ $report->handler?->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('monitoring.sos.show', $report) }}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="shield-check" title="Tidak ada laporan SOS" description="Laporan darurat akan muncul di sini saat jamaah menekan tombol SOS." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $reports->links() }}</div>
    </section>
</x-app-layout>
