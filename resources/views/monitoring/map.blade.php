<x-app-layout>
    <x-slot:title>Pusat Monitoring</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Monitoring / Pusat Kendali</nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold tracking-tight">Monitoring Perjalanan</h1>
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800">
                        <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                        Pembaruan otomatis
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Pantau jamaah, petugas, titik tujuan, dan kondisi darurat dalam satu layar.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('monitoring.sos.index') }}" class="button-secondary border-red-200 text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300"><i data-lucide="siren" class="size-4"></i> Pusat SOS</a>
                <a href="{{ route('master-data.index', 'checkpoints') }}" class="button-secondary"><i data-lucide="map-pinned" class="size-4"></i> Kelola Titik</a>
                <a href="{{ route('monitoring.tracking.index') }}" class="button-primary"><i data-lucide="map" class="size-4"></i> Riwayat Tracking</a>
            </div>
        </div>
    </x-slot:header>

    <section class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-6" aria-label="Ringkasan monitoring">
        @foreach ([
            ['id' => 'monitoring-total', 'label' => 'Jamaah Terpantau', 'help' => 'Memiliki lokasi', 'dot' => 'bg-blue-500', 'number' => 'text-blue-600 dark:text-blue-400'],
            ['id' => 'monitoring-online', 'label' => 'GPS Aktif', 'help' => 'Lokasi terbaru', 'dot' => 'bg-emerald-500', 'number' => 'text-emerald-600 dark:text-emerald-400'],
            ['id' => 'monitoring-offline', 'label' => 'Perlu Diperiksa', 'help' => 'GPS terlambat', 'dot' => 'bg-slate-500', 'number' => 'text-slate-600 dark:text-slate-400'],
            ['id' => 'monitoring-sos', 'label' => 'SOS Aktif', 'help' => 'Prioritas respons', 'dot' => 'bg-red-500', 'number' => 'text-red-600 dark:text-red-400'],
            ['id' => 'monitoring-staff', 'label' => 'Petugas di Peta', 'help' => 'TL & Muthawwif', 'dot' => 'bg-cyan-500', 'number' => 'text-cyan-600 dark:text-cyan-400'],
            ['id' => 'monitoring-checkpoints', 'label' => 'Titik Tujuan', 'help' => 'Sesuai cakupan', 'dot' => 'bg-amber-500', 'number' => 'text-amber-600 dark:text-amber-400'],
        ] as $stat)
            <article class="surface-card p-4">
                <div class="flex items-start justify-between gap-2">
                    <div><p class="text-[11px] font-bold uppercase tracking-[.12em] text-slate-500">{{ $stat['label'] }}</p><p class="mt-1 text-xs text-slate-400">{{ $stat['help'] }}</p></div>
                    <span class="size-2.5 rounded-full {{ $stat['dot'] }}"></span>
                </div>
                <p id="{{ $stat['id'] }}" class="mt-3 text-3xl font-extrabold {{ $stat['number'] }}">0</p>
            </article>
        @endforeach
    </section>

    <section class="mb-5 grid gap-3 lg:grid-cols-4">
        @foreach ([
            ['1', 'Pilih paket', 'Gunakan filter Paket Perjalanan agar titik tujuan dan rombongan sesuai jadwal.'],
            ['2', 'Pilih rombongan', 'Jika rombongan dipilih, titik paket dan titik khusus rombongan ikut tampil.'],
            ['3', 'Pastikan aktivasi', 'Lokasi jamaah muncul setelah PIN dipakai dan aplikasi mengirim GPS.'],
            ['4', 'Tindak lanjuti', 'GPS terlambat dan SOS menjadi prioritas pemeriksaan petugas.'],
        ] as $guide)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-extrabold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">Langkah {{ $guide[0] }}</span>
                <h2 class="mt-3 text-sm font-extrabold text-slate-950 dark:text-white">{{ $guide[1] }}</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $guide[2] }}</p>
            </article>
        @endforeach
    </section>

    <section class="surface-card overflow-hidden">
        <div class="border-b border-slate-200 p-4 dark:border-slate-800">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div><h2 class="font-extrabold">Filter Operasional</h2><p class="text-xs text-slate-500">Pilih perjalanan agar peta tidak terlalu ramai.</p></div>
                <button id="monitoring-reset" type="button" class="button-secondary min-h-9 px-3 py-2 text-xs"><i data-lucide="rotate-ccw" class="size-4"></i> Reset</button>
            </div>
            <div id="monitoring-filters" class="grid gap-3 md:grid-cols-2 2xl:grid-cols-12">
                <label class="grid gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300">
                    Cabang
                    @if ($canFilterBranches)
                        <select id="monitoring-branch" class="control-field">
                            <option value="">Semua cabang</option>
                            @foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                        </select>
                    @else
                        <input type="hidden" id="monitoring-branch" value="{{ auth()->user()->branch_id }}">
                        <span class="flex min-h-11 items-center rounded-2xl bg-slate-100 px-3 text-sm font-semibold dark:bg-slate-800">{{ auth()->user()->branch?->name }}</span>
                    @endif
                </label>

                <label class="grid gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 2xl:col-span-3">
                    Paket Perjalanan
                    <select id="monitoring-departure" class="control-field">
                        <option value="">Semua perjalanan aktif</option>
                        @foreach ($departures as $departure)
                            <option value="{{ $departure->id }}" data-branch="{{ $departure->branch_id }}">{{ $departure->program_name }} · {{ $departure->departure_date->format('d/m/Y') }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 2xl:col-span-2">
                    Rombongan
                    <select id="monitoring-group" class="control-field">
                        <option value="">Semua rombongan</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" data-branch="{{ $group->branch_id }}" data-departure="{{ $group->departure_id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 2xl:col-span-2">
                    Kondisi Jamaah
                    <select id="monitoring-status" class="control-field">
                        <option value="">Semua kondisi</option>
                        <option value="online">GPS aktif</option>
                        <option value="offline">GPS terlambat</option>
                        <option value="sos">SOS aktif</option>
                    </select>
                </label>

                <label class="grid gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 2xl:col-span-2">
                    Cari Jamaah
                    <span class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></i><input id="monitoring-search" type="search" class="control-field w-full pl-9" placeholder="Nama/no. registrasi"></span>
                </label>

                <label class="grid gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 2xl:col-span-2">
                    Interval Data
                    <select id="monitoring-refresh" class="control-field">
                        <option value="5000">Setiap 5 detik</option>
                        <option value="10000" selected>Setiap 10 detik</option>
                        <option value="30000">Setiap 30 detik</option>
                        <option value="0">Manual</option>
                    </select>
                </label>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                <div class="flex flex-wrap gap-4 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <label class="inline-flex cursor-pointer items-center gap-2"><input id="monitoring-show-staff" type="checkbox" checked class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"> Petugas</label>
                    <label class="inline-flex cursor-pointer items-center gap-2"><input id="monitoring-show-checkpoints" type="checkbox" checked class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"> Titik tujuan & geofence</label>
                </div>
                <button id="monitoring-reload" type="button" class="button-primary min-h-9 px-4 py-2 text-xs"><i data-lucide="rotate-ccw" class="size-4"></i> Perbarui Sekarang</button>
            </div>
        </div>

        <div class="grid 2xl:grid-cols-[minmax(0,1fr)_360px]">
            <aside class="order-2 border-t border-slate-200 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-950/30 2xl:order-2 2xl:border-l 2xl:border-t-0" aria-label="Daftar jamaah terpantau">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                    <div><h3 class="text-sm font-extrabold">Daftar Jamaah</h3><p id="monitoring-list-caption" class="text-xs text-slate-500">Menunggu data lokasi</p></div>
                    <span id="monitoring-connection" class="rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-800">Menghubungkan</span>
                </div>
                <div id="monitoring-list" class="max-h-[360px] overflow-y-auto 2xl:max-h-[720px]"></div>
                <div id="monitoring-empty" class="hidden p-6 text-center">
                    <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800"><i data-lucide="users" class="size-5"></i></span>
                    <p class="mt-3 text-sm font-bold">Belum ada lokasi</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Lokasi muncul setelah aplikasi jamaah aktif dan mengirim GPS.</p>
                    <div class="mt-5 rounded-2xl bg-white p-4 text-left text-xs leading-5 text-slate-600 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-800">
                        <p class="font-bold text-slate-800 dark:text-slate-100">Checklist sebelum tracking tampil:</p>
                        <ul class="mt-2 space-y-1.5">
                            <li class="flex gap-2"><i data-lucide="circle-check" class="mt-0.5 size-3.5 shrink-0 text-emerald-600"></i> Jamaah sudah lunas dan masuk rombongan.</li>
                            <li class="flex gap-2"><i data-lucide="circle-check" class="mt-0.5 size-3.5 shrink-0 text-emerald-600"></i> PIN aktivasi sudah dibuat oleh Admin Cabang.</li>
                            <li class="flex gap-2"><i data-lucide="circle-check" class="mt-0.5 size-3.5 shrink-0 text-emerald-600"></i> Jamaah sudah login aplikasi dan mengizinkan lokasi.</li>
                            <li class="flex gap-2"><i data-lucide="circle-check" class="mt-0.5 size-3.5 shrink-0 text-emerald-600"></i> Filter paket atau rombongan sesuai data jamaah.</li>
                        </ul>
                    </div>
                    <div class="mt-4 grid gap-2">
                        <a href="{{ route('master-data.index', 'groups') }}" class="button-secondary min-h-10 justify-center text-xs">
                            <i data-lucide="key-round" class="size-4"></i>
                            Cek Rombongan & PIN
                        </a>
                        <a href="{{ route('master-data.index', 'checkpoints') }}" class="button-secondary min-h-10 justify-center text-xs">
                            <i data-lucide="map-pinned" class="size-4"></i>
                            Cek Titik Tujuan
                        </a>
                    </div>
                </div>
            </aside>

            <div class="relative order-1 min-w-0 2xl:order-1">
                <div id="monitoring-map" data-endpoint="{{ route('monitoring.map.data') }}" class="h-[560px] w-full bg-slate-100 lg:h-[650px] 2xl:h-[720px]" aria-label="Peta monitoring jamaah dan petugas"></div>
                <div id="monitoring-loading" class="pointer-events-none absolute inset-0 z-[500] hidden place-items-center bg-white/50 backdrop-blur-sm"><x-loading-state label="Memuat data operasional..." class="rounded-2xl bg-white px-5 py-3 shadow-lg dark:bg-slate-900" /></div>
                <aside id="monitoring-detail" class="absolute inset-y-4 right-4 z-[600] hidden w-[calc(100%-2rem)] max-w-sm overflow-y-auto rounded-2xl border border-slate-200 bg-white/95 shadow-2xl backdrop-blur dark:border-slate-700 dark:bg-slate-900/95"><div id="monitoring-detail-content"></div></aside>
                <div class="absolute bottom-4 left-4 z-[500] hidden rounded-2xl bg-white/95 p-3 text-[11px] font-semibold shadow-lg backdrop-blur sm:block dark:bg-slate-900/95">
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2"><span><i class="mr-1 inline-block size-2.5 rounded-full bg-emerald-500"></i> GPS aktif</span><span><i class="mr-1 inline-block size-2.5 rounded-full bg-slate-500"></i> GPS terlambat</span><span><i class="mr-1 inline-block size-2.5 rounded-full bg-red-500"></i> SOS</span><span><i class="mr-1 inline-block size-2.5 rounded-full bg-cyan-500"></i> Petugas</span><span><i class="mr-1 inline-block size-2.5 rounded bg-amber-500"></i> Titik tujuan</span><span><i class="mr-1 inline-block size-2.5 rounded border border-amber-400"></i> Radius geofence</span></div>
                </div>
            </div>
        </div>

        <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-slate-800"><span id="monitoring-updated">Belum diperbarui</span><span>Peta © OpenStreetMap contributors</span></footer>
    </section>
</x-app-layout>
