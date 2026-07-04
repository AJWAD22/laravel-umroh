<x-app-layout>
    <x-slot:title>Histori Tracking</x-slot:title>
    <x-slot:header>
        <div>
            <nav class="mb-2 text-sm text-slate-500">Monitoring / Histori Tracking</nav>
            <h1 class="text-2xl font-bold">Histori Perjalanan Jamaah</h1>
            <p class="mt-1 text-sm text-slate-500">Seluruh titik pada tanggal terpilih ditampilkan tanpa batas jarak.</p>
        </div>
    </x-slot:header>

    <section class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[minmax(240px,1fr)_200px_auto]">
        <select id="tracking-pilgrim" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">Pilih jamaah</option>
            @foreach ($pilgrims as $pilgrim)
                <option value="{{ $pilgrim->id }}">{{ $pilgrim->full_name }} · {{ $pilgrim->registration_number }} ({{ $pilgrim->branch->name }})</option>
            @endforeach
        </select>
        <input id="tracking-date" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}"
               class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
        <button id="tracking-load" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Tampilkan Histori</button>
    </section>

    <section class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['id' => 'tracking-total-points', 'label' => 'Total Titik', 'suffix' => ''],
            ['id' => 'tracking-distance', 'label' => 'Total Jarak', 'suffix' => ' km'],
            ['id' => 'tracking-start', 'label' => 'Mulai', 'suffix' => ''],
            ['id' => 'tracking-end', 'label' => 'Selesai', 'suffix' => ''],
        ] as $summary)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $summary['label'] }}</p>
                <p id="{{ $summary['id'] }}" data-suffix="{{ $summary['suffix'] }}" class="mt-1 truncate text-xl font-bold">—</p>
            </div>
        @endforeach
    </section>

    <section class="grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 xl:grid-cols-[minmax(0,2fr)_380px]">
        <div class="relative min-h-[620px]">
            <div id="tracking-map" data-endpoint="{{ route('monitoring.tracking.data') }}" class="h-[620px] w-full bg-slate-100"></div>
            <div id="tracking-loading" class="absolute inset-0 z-[500] hidden place-items-center bg-white/60 backdrop-blur-sm">
                <x-loading-state label="Memuat seluruh titik perjalanan..." class="rounded-2xl bg-white px-5 py-3 shadow-lg dark:bg-slate-900" />
            </div>
            <div id="tracking-empty" class="pointer-events-none absolute inset-0 z-[400] grid place-items-center">
                <div class="rounded-2xl bg-white/95 px-6 py-4 text-center shadow-lg">
                    <p class="font-semibold">Pilih jamaah dan tanggal</p>
                    <p class="mt-1 text-sm text-slate-500">Jalur perjalanan akan tampil di sini.</p>
                </div>
            </div>
        </div>

        <aside class="border-t border-slate-200 dark:border-slate-800 xl:border-l xl:border-t-0">
            <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                <h2 class="font-semibold">Timeline Perjalanan</h2>
                <p id="tracking-person" class="mt-1 text-sm text-slate-500">Belum ada jamaah dipilih.</p>
            </div>
            <div id="tracking-timeline" class="max-h-[555px] overflow-y-auto p-4">
                <p class="py-10 text-center text-sm text-slate-500">Timeline belum tersedia.</p>
            </div>
        </aside>
    </section>
</x-app-layout>
