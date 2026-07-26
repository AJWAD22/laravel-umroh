@php
    $reportLabels = ['all' => 'Gabungan', 'pilgrims' => 'Jamaah', 'tracking' => 'Tracking', 'sos' => 'SOS'];
    $reportHelp = [
        'all' => 'Gabungan data jamaah, tracking, dan SOS dalam satu file.',
        'pilgrims' => 'Rekap data jamaah berdasarkan status dan periode pembuatan data.',
        'tracking' => 'Rekap titik GPS yang dikirim aplikasi mobile.',
        'sos' => 'Rekap laporan darurat dan status penanganannya.',
    ];
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
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Laporan / {{ $reportLabels[$type] }}</nav>
                <h1 class="text-2xl font-bold">{{ $title }}</h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                    @role('super-admin')
                        Super Admin melihat laporan agregat nasional. Detail identitas, koordinat, dan SOS operasional tetap berada di cabang.
                    @else
                        Admin Cabang melihat laporan operasional cabangnya sendiri. Preview dibatasi 100 baris; export memuat seluruh hasil filter.
                    @endrole
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('reports.download', ['type' => $type, 'format' => 'pdf', ...$downloadQuery]) }}" class="button-secondary border-red-200 text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300">
                    <i data-lucide="file-text" class="size-4"></i>
                    Export PDF
                </a>
                <a href="{{ route('reports.download', ['type' => $type, 'format' => 'xlsx', ...$downloadQuery]) }}" class="button-secondary border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-300">
                    <i data-lucide="file-spreadsheet" class="size-4"></i>
                    Export Excel
                </a>
            </div>
        </div>
    </x-slot:header>

    <section class="mb-4 grid gap-3 lg:grid-cols-4">
        @foreach ($reportLabels as $key => $label)
            <a href="{{ route('reports.index', ['type' => $key, 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to']]) }}"
               class="rounded-2xl border p-4 shadow-sm transition {{ $type === $key ? 'border-blue-300 bg-blue-50 text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100' : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-blue-50/40 dark:border-slate-800 dark:bg-slate-900' }}">
                <p class="text-sm font-extrabold">{{ $label }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $reportHelp[$key] }}</p>
            </a>
        @endforeach
    </section>

    <section class="mb-4 grid gap-3 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Periode</p>
            <strong class="mt-2 block text-lg">{{ $filters['date_from'] }} s/d {{ $filters['date_to'] }}</strong>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total Data</p>
            <strong class="mt-2 block text-lg">{{ number_format($rows->count()) }} baris</strong>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Mode Preview</p>
            <strong class="mt-2 block text-lg">{{ number_format($previewRows->count()) }} baris tampil</strong>
        </article>
    </section>

    <form method="GET" class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-5">
        <label>
            <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Dari tanggal</span>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="control-field w-full">
        </label>
        <label>
            <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Sampai tanggal</span>
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="control-field w-full">
        </label>
        @if ($canFilterBranches)
            <label>
                <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Cabang</span>
                <select name="branch_id" class="control-field w-full">
                    <option value="">Semua cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string)($filters['branch_id'] ?? '') === (string)$branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <div class="hidden md:block"></div>
        @endif
        @if ($statuses)
            <label>
                <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Status</span>
                <select name="status" class="control-field w-full">
                    <option value="">Semua status</option>
                    @foreach($statuses as $status => $label)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <div class="hidden md:block"></div>
        @endif
        <button class="button-primary self-end justify-center">
            <i data-lucide="filter" class="size-4"></i>
            Tampilkan
        </button>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-extrabold">Preview Laporan</h2>
                <p class="mt-1 text-sm text-slate-500">Gunakan tombol export untuk mengambil seluruh data sesuai filter.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ number_format($rows->count()) }} data ditemukan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/60">
                    <tr>@foreach($headings as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach</tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($previewRows as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">@foreach($row as $cell)<td class="whitespace-nowrap px-4 py-3">{{ $cell }}</td>@endforeach</tr>
                    @empty
                        <tr><td colspan="{{ count($headings) }}"><x-empty-state icon="book-open" title="Tidak ada data laporan" description="Ubah periode atau filter untuk melihat data lainnya." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
