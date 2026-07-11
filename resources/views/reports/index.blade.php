@php
    $reportLabels = ['all' => 'Gabungan', 'pilgrims' => 'Jamaah', 'tracking' => 'Tracking', 'sos' => 'SOS'];
    $statuses = match($type) {
        'pilgrims' => ['registered' => 'Terdaftar', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Batal'],
        'sos' => ['new' => 'Baru', 'handling' => 'Ditangani', 'resolved' => 'Selesai'],
        default => [],
    };
    $downloadQuery = array_filter([
        'date_from' => $filters['date_from'],
        'date_to' => $filters['date_to'],
        'branch_id' => $filters['branch_id'] ?? null,
        'status' => $filters['status'] ?? null,
    ], fn ($value) => filled($value));
@endphp

<x-app-layout>
    <x-slot:title>{{ $title }}</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Laporan / {{ $reportLabels[$type] }}</nav>
                <h1 class="text-2xl font-bold">{{ $title }}</h1>
                <p class="mt-1 text-sm text-slate-500">Preview menampilkan maksimal 100 baris; file export memuat seluruh hasil filter.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.download', ['type' => $type, 'format' => 'pdf', ...$downloadQuery]) }}" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Export PDF</a>
                <a href="{{ route('reports.download', ['type' => $type, 'format' => 'xlsx', ...$downloadQuery]) }}" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Export Excel</a>
            </div>
        </div>
    </x-slot:header>

    <nav class="mb-4 flex gap-2 overflow-x-auto pb-1">
        @foreach ($reportLabels as $reportType => $label)
            <a href="{{ route('reports.index', $reportType) }}" class="whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold {{ $type === $reportType ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 shadow-sm dark:bg-slate-900' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <form method="GET" class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-5">
        <label><span class="mb-1 block text-xs font-medium text-slate-500">Dari tanggal</span><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950"></label>
        <label><span class="mb-1 block text-xs font-medium text-slate-500">Sampai tanggal</span><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950"></label>
        @if ($canFilterBranches)
            <label><span class="mb-1 block text-xs font-medium text-slate-500">Cabang</span><select name="branch_id" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="">Semua cabang</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)($filters['branch_id'] ?? '') === (string)$branch->id)>{{ $branch->name }}</option>@endforeach</select></label>
        @else
            <span></span>
        @endif
        @if ($statuses)
            <label><span class="mb-1 block text-xs font-medium text-slate-500">Status</span><select name="status" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="">Semua status</option>@foreach($statuses as $status => $label)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $label }}</option>@endforeach</select></label>
        @else
            <span></span>
        @endif
        <button class="self-end rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Tampilkan</button>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"><strong>{{ number_format($rows->count()) }}</strong> data ditemukan</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/60"><tr>@foreach($headings as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($previewRows as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">@foreach($row as $cell)<td class="whitespace-nowrap px-4 py-3">{{ $cell }}</td>@endforeach</tr>
                    @empty
                        <tr><td colspan="{{ count($headings) }}"><x-empty-state icon="book-open" title="Tidak ada data laporan"
                                                                               description="Ubah periode atau filter untuk melihat data lainnya." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
