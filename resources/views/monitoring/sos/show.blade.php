@php
    $statusLabel = ['new' => 'Baru', 'handling' => 'Sedang ditangani', 'resolved' => 'Selesai'];
@endphp

<x-app-layout>
    <x-slot:title>Detail SOS</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Monitoring / SOS / Detail</nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">SOS {{ $report->pilgrim?->full_name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Status: {{ $statusLabel[$report->status] ?? $report->status }}</p>
            </div>
            <a href="{{ route('monitoring.sos.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold">Kembali</a>
        </div>
    </x-slot:header>

    <div class="grid gap-4 lg:grid-cols-[1fr_360px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div id="sos-detail-map" class="h-[520px]" data-lat="{{ $report->latitude }}" data-lng="{{ $report->longitude }}" data-name="{{ $report->pilgrim?->full_name }}"></div>
        </section>
        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-bold">Informasi Jamaah</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Nama</dt><dd class="font-semibold">{{ $report->pilgrim?->full_name }}</dd></div>
                    <div><dt class="text-slate-500">No. Registrasi</dt><dd>{{ $report->pilgrim?->registration_number }}</dd></div>
                    <div><dt class="text-slate-500">WhatsApp</dt><dd>{{ $report->pilgrim?->phone ?: '-' }}</dd></div>
                    <div><dt class="text-slate-500">Rombongan</dt><dd>{{ $report->group?->name ?? '-' }}</dd></div>
                    <div><dt class="text-slate-500">Waktu SOS</dt><dd>{{ $report->reported_at?->format('d M Y H:i:s') }}</dd></div>
                    <div><dt class="text-slate-500">Koordinat</dt><dd>{{ $report->latitude }}, {{ $report->longitude }}</dd></div>
                </dl>
            </div>

            @if($report->status !== 'resolved')
                <form method="POST" action="{{ route('monitoring.sos.resolve', $report) }}" class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm dark:border-red-900 dark:bg-red-950/40">
                    @csrf
                    @method('PATCH')
                    <label class="block text-sm font-semibold text-red-900 dark:text-red-100">Catatan penyelesaian</label>
                    <textarea name="resolution_notes" rows="3" class="mt-2 w-full rounded-xl border-red-200 text-sm dark:border-red-900 dark:bg-slate-950" placeholder="Contoh: Jamaah sudah ditemukan bersama petugas."></textarea>
                    <button class="mt-3 w-full rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Tandai Sudah Aman</button>
                </form>
            @endif
        </aside>
    </div>
</x-app-layout>
