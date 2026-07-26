<x-app-layout>
    <x-slot:title>Audit Log</x-slot:title>
    <x-slot:header>
        <nav class="mb-1 text-xs font-medium text-slate-500">Pengaturan / Audit Log</nav>
        <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">Audit Log</h1>
        <p class="mt-1 text-sm text-slate-500">Jejak tindakan penting, pelaku, waktu, dan cakupan cabang.</p>
    </x-slot:header>

    @if ($canPurgeExpired)
        <section class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-amber-700 ring-1 ring-amber-200 dark:bg-slate-900 dark:text-amber-300 dark:ring-amber-900">
                        <i data-lucide="shield-check" class="size-5"></i>
                    </span>
                    <div>
                        <h2 class="font-bold text-amber-950 dark:text-amber-100">Retensi audit aktif: {{ $retentionDays }} hari</h2>
                        <p class="mt-1 text-sm leading-6 text-amber-800 dark:text-amber-200">Audit log tidak dihapus manual per baris. Super Admin hanya dapat membersihkan log yang sudah melewati masa simpan, dan tindakan ini tetap dicatat sebagai audit log baru.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('audit-logs.purge-expired') }}">
                    @csrf
                    <button class="button-secondary whitespace-nowrap border-amber-300 bg-white text-amber-800 hover:bg-amber-100 dark:border-amber-900 dark:bg-slate-900 dark:text-amber-200 dark:hover:bg-amber-950">
                        <i data-lucide="archive-x" class="size-4"></i>
                        Bersihkan Log Kedaluwarsa
                    </button>
                </form>
            </div>
        </section>
    @endif

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
