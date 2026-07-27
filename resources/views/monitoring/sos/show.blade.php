@php
    $statusLabel = ['new' => 'Baru', 'handling' => 'Sedang ditangani', 'acknowledged' => 'Sudah diterima', 'assigned' => 'Ditugaskan', 'on_the_way' => 'Menuju lokasi', 'arrived' => 'Sudah tiba', 'resolved' => 'Selesai', 'cancelled' => 'Dibatalkan', 'false_alarm' => 'Salah tekan'];
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
    $phone = preg_replace('/\D+/', '', $report->pilgrim?->phone ?? '');
    $waPhone = str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone;
@endphp

<x-app-layout>
    <x-slot:title>Detail SOS</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Monitoring / SOS Jamaah / Detail</nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">SOS {{ $report->pilgrim?->full_name }}</h1>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass[$report->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabel[$report->status] ?? $report->status }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Buka posisi terakhir, hubungi jamaah, lalu tutup laporan setelah kondisi aman.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($waPhone)
                    <a href="https://wa.me/{{ $waPhone }}" target="_blank" rel="noopener" class="button-secondary border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-300">
                        <i data-lucide="message-circle" class="size-4"></i>
                        WhatsApp
                    </a>
                @endif
                <a href="{{ route('monitoring.sos.index') }}" class="button-secondary">
                    <i data-lucide="arrow-left" class="size-4"></i>
                    Kembali
                </a>
            </div>
        </div>
    </x-slot:header>

    <section class="mb-4 grid gap-3 md:grid-cols-4">
        @foreach ([
            ['label' => 'Waktu SOS', 'value' => $report->reported_at?->format('d M Y H:i:s') ?? '-'],
            ['label' => 'Akurasi GPS', 'value' => $report->accuracy ? number_format($report->accuracy, 0).' m' : '-'],
            ['label' => 'Ditangani Oleh', 'value' => $report->handler?->name ?? '-'],
            ['label' => 'Status', 'value' => $statusLabel[$report->status] ?? $report->status],
        ] as $item)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $item['label'] }}</p>
                <strong class="mt-2 block text-lg">{{ $item['value'] }}</strong>
            </article>
        @endforeach
    </section>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_390px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div id="sos-detail-map" class="h-[560px]" data-lat="{{ $report->latitude }}" data-lng="{{ $report->longitude }}" data-name="{{ $report->pilgrim?->full_name }}"></div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-bold">Informasi Jamaah</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Nama</dt><dd class="font-semibold">{{ $report->pilgrim?->full_name }}</dd></div>
                    <div><dt class="text-slate-500">No. Registrasi</dt><dd>{{ $report->pilgrim?->registration_number }}</dd></div>
                    <div><dt class="text-slate-500">WhatsApp</dt><dd>{{ $report->pilgrim?->phone ?: '-' }}</dd></div>
                    <div><dt class="text-slate-500">Cabang</dt><dd>{{ $report->pilgrim?->branch?->name ?? '-' }}</dd></div>
                    <div><dt class="text-slate-500">Rombongan</dt><dd>{{ $report->group?->name ?? '-' }} {{ $report->group?->code ? '('.$report->group->code.')' : '' }}</dd></div>
                    <div><dt class="text-slate-500">Koordinat SOS</dt><dd class="font-mono text-xs">{{ $report->latitude }}, {{ $report->longitude }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-bold">Catatan SOS</h2>
                <p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm leading-6 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $report->message ?: 'Tidak ada pesan tambahan dari jamaah.' }}</p>
                @if($report->resolution_notes)
                    <p class="mt-3 text-xs font-bold uppercase tracking-wide text-slate-500">Catatan Penyelesaian</p>
                    <p class="mt-2 rounded-xl bg-emerald-50 p-3 text-sm leading-6 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100">{{ $report->resolution_notes }}</p>
                @endif
            </div>

            @if($report->status !== 'resolved')
                <form method="POST" action="{{ route('monitoring.sos.resolve', $report) }}" class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm dark:border-red-900 dark:bg-red-950/40">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-lg font-bold text-red-950 dark:text-red-100">Tutup Laporan SOS</h2>
                    <p class="mt-1 text-sm leading-6 text-red-800 dark:text-red-200">Gunakan setelah jamaah dipastikan aman. Catatan wajib diisi untuk riwayat penanganan.</p>
                    <label class="mt-4 block text-sm font-semibold text-red-900 dark:text-red-100">Catatan penyelesaian</label>
                    <textarea name="resolution_notes" rows="4" required class="mt-2 w-full rounded-xl border-red-200 text-sm dark:border-red-900 dark:bg-slate-950" placeholder="Contoh: Jamaah sudah ditemukan bersama Tour Leader di titik kumpul hotel.">{{ old('resolution_notes') }}</textarea>
                    @error('resolution_notes')
                        <p class="mt-2 text-xs font-semibold text-red-700">{{ $message }}</p>
                    @enderror
                    <button class="mt-3 w-full rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Tandai Jamaah Aman</button>
                </form>
            @endif
        </aside>
    </div>
</x-app-layout>
