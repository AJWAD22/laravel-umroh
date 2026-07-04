<x-app-layout>
    <x-slot:title>Monitoring SOS</x-slot:title>
    <x-slot:header>
        <div>
            <nav class="mb-2 text-sm text-slate-500">Monitoring / SOS</nav>
            <h1 class="text-2xl font-bold">Monitoring SOS</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau dan tangani laporan darurat jamaah.</p>
        </div>
    </x-slot:header>

    <section class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['label' => 'SOS Aktif', 'value' => $summary['active'], 'class' => 'text-red-600'],
            ['label' => 'Ditangani', 'value' => $summary['acknowledged'], 'class' => 'text-amber-600'],
            ['label' => 'Selesai', 'value' => $summary['resolved'], 'class' => 'text-emerald-600'],
            ['label' => 'Total Laporan', 'value' => $summary['total'], 'class' => 'text-blue-600'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $card['class'] }}">{{ number_format($card['value']) }}</p>
            </div>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" class="grid gap-3 border-b border-slate-200 p-4 dark:border-slate-800 md:grid-cols-[minmax(220px,1fr)_180px_180px_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Cari nama atau nomor jamaah..."
                   class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
            @if ($canFilterBranches)
                <select name="branch_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <option value="">Semua cabang</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            @else
                <span></span>
            @endif
            <select name="status" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">Semua status</option>
                @foreach (['active' => 'Aktif', 'acknowledged' => 'Ditangani', 'resolved' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Terapkan</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/60">
                    <tr>
                        <th class="px-5 py-3">Jamaah</th>
                        <th class="px-5 py-3">Cabang / Rombongan</th>
                        <th class="px-5 py-3">Lokasi</th>
                        <th class="px-5 py-3">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'reported_at', 'direction' => $sort === 'reported_at' && $direction === 'desc' ? 'asc' : 'desc']) }}">Waktu</a>
                        </th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $report->pilgrim->full_name }}</p>
                                <p class="text-xs text-slate-500">{{ $report->pilgrim->registration_number }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p>{{ $report->branch->name }}</p>
                                <p class="text-xs text-slate-500">{{ $report->group?->name ?? 'Tanpa rombongan' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs">{{ $report->latitude }}, {{ $report->longitude }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $report->reported_at->format('d M Y H:i') }}</td>
                            <td class="px-5 py-4">
                                <x-status-badge :value="$report->status === 'active' ? 'sos' : $report->status"
                                                :label="$report->status === 'active' ? 'Aktif' : null" />
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <a href="{{ route('monitoring.sos.show', $report) }}" class="font-semibold text-blue-600">Detail</a>
                                @can('update', $report)
                                @if ($report->status !== 'resolved')
                                    <form method="POST" action="{{ route('monitoring.sos.resolve', $report) }}" class="ml-3 inline"
                                          data-confirm-title="Selesaikan Laporan SOS"
                                          data-confirm="Pastikan jamaah sudah mendapatkan bantuan sebelum menandai laporan ini selesai.">
                                        @csrf @method('PATCH')
                                        <button class="font-semibold text-emerald-600">Tandai Selesai</button>
                                    </form>
                                @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="shield-check" title="Tidak ada laporan SOS"
                                                          description="Semua jamaah dalam kondisi aman pada filter saat ini." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $reports->links() }}</div>
    </section>
</x-app-layout>
