<x-app-layout>
    <x-slot:title>Audit Log</x-slot:title>
    <x-slot:header>
        <nav class="mb-1 text-xs font-medium text-slate-500">Keamanan / Audit</nav>
        <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">Audit Log</h1>
        <p class="mt-1 text-sm text-slate-500">Jejak tindakan penting, pelaku, waktu, dan cakupan cabang.</p>
    </x-slot:header>

    <section class="surface-card overflow-hidden">
        <form method="GET" class="grid gap-3 border-b border-slate-200 p-5 dark:border-slate-800 md:grid-cols-3">
            <input name="action" value="{{ request('action') }}" placeholder="Cari tindakan" class="control-field w-full">
            <input name="actor" value="{{ request('actor') }}" placeholder="Cari pelaku" class="control-field w-full">
            <button class="button-secondary justify-center"><i data-lucide="list-filter" class="size-4"></i>Terapkan Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-5 py-3.5">Waktu</th>
                        <th class="px-5 py-3.5">Tindakan</th>
                        <th class="px-5 py-3.5">Pelaku</th>
                        <th class="px-5 py-3.5">Cabang</th>
                        <th class="px-5 py-3.5">Subjek</th>
                        <th class="px-5 py-3.5">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($logs as $log)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-5 py-4">{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">{{ $log->action }}</span></td>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $log->actor?->name ?? 'Sistem' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $log->actor?->email }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $log->branch?->name ?? 'Nasional' }}</td>
                            <td class="px-5 py-4 text-xs text-slate-500">
                                <p>{{ class_basename($log->subject_type ?? '-') }}</p>
                                <p>ID: {{ $log->subject_id ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs leading-5 text-slate-600 dark:text-slate-300">
                                @php $metadata = collect($log->metadata ?? [])->except(['branch_id'])->filter(fn ($value) => filled($value)); @endphp
                                @if ($metadata->isEmpty())
                                    <span class="text-slate-400">-</span>
                                @else
                                    @foreach ($metadata as $key => $value)
                                        <p><span class="font-semibold">{{ str($key)->replace('_', ' ')->title() }}:</span> {{ is_scalar($value) ? $value : json_encode($value) }}</p>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="history" title="Belum ada audit log" description="Tindakan penting akan tercatat di sini." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $logs->links() }}</div>
    </section>
</x-app-layout>
