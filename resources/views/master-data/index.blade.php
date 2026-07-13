<x-app-layout>
    @php
        $canManage = auth()->user()->can($definition['permission']);
        $hasFilters = request()->filled('search') || request()->filled('branch_id') || request()->filled('status');
        $resourceIcons = [
            'branches' => 'building-2',
            'branch-admins' => 'shield-check',
            'pilgrims' => 'users',
            'tour-leaders' => 'user-round-check',
            'muthawwifs' => 'book-open',
            'hotels' => 'building-2',
            'checkpoints' => 'map-pinned',
            'departures' => 'plane',
            'groups' => 'users-round',
        ];
        $resourceIcon = $resourceIcons[$resource] ?? 'database';
        $sectionLabel = match (true) {
            in_array($resource, ['branch-admins', 'pilgrims', 'tour-leaders', 'muthawwifs', 'groups'], true) => 'Data Master',
            in_array($resource, ['departures', 'hotels', 'checkpoints'], true) => 'Data Pendukung Sistem',
            $resource === 'branches' => 'Organisasi',
            default => 'Data',
        };
    @endphp

    <x-slot:title>{{ $definition['label'] }}</x-slot:title>

    <x-slot:header>
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-3.5">
                <span class="hidden size-11 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 dark:bg-blue-950/50 dark:text-blue-300 dark:ring-blue-900 sm:grid">
                    <i data-lucide="{{ $resourceIcon }}" class="size-5"></i>
                </span>
                <div class="min-w-0">
                    <nav class="mb-1 flex items-center gap-1.5 text-xs font-medium text-slate-500">
                        <span>{{ $sectionLabel }}</span>
                        <i data-lucide="chevron-right" class="size-3.5"></i>
                        <span class="truncate text-slate-700 dark:text-slate-300">{{ $definition['label'] }}</span>
                    </nav>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-[1.7rem]">{{ $definition['label'] }}</h1>
                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        {{ $canManage ? 'Kelola' : 'Pantau' }} data {{ str($definition['label'])->lower() }} secara terpusat.
                    </p>
                </div>
            </div>

            @if ($canManage)
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @if ($resource === 'pilgrims')
                        <a href="{{ route('master-data.template', $resource) }}" class="button-secondary shrink-0">
                            <i data-lucide="download" class="size-4.5"></i>
                            Template Excel
                        </a>
                        <form method="POST" action="{{ route('master-data.import', $resource) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="button-secondary shrink-0 cursor-pointer">
                                <i data-lucide="upload" class="size-4.5"></i>
                                Import Jamaah
                                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="sr-only" onchange="this.form.submit()">
                            </label>
                        </form>
                    @endif
                    <a href="{{ route('master-data.create', $resource) }}" class="button-primary shrink-0">
                        <i data-lucide="plus" class="size-4.5"></i>
                        Tambah {{ $definition['label'] }}
                    </a>
                </div>
            @endif
        </div>
    </x-slot:header>

    <section class="surface-card overflow-hidden">
        <div class="border-b border-slate-200/80 p-4 dark:border-slate-800 sm:p-5">
            <form method="GET" class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <label class="relative min-w-0 flex-1">
                    <span class="sr-only">Cari {{ str($definition['label'])->lower() }}</span>
                    <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}"
                           placeholder="Cari {{ str($definition['label'])->lower() }}..."
                           class="control-field w-full pl-10 lg:max-w-xl">
                </label>

                <div class="flex flex-col gap-3 sm:flex-row">
                    @if (auth()->user()->hasRole('super-admin') && $resource !== 'branches')
                        <select name="branch_id" class="control-field min-w-44">
                            <option value="">Semua cabang</option>
                            @foreach ($options['branches'] as $id => $name)
                                <option value="{{ $id }}" @selected((string) request('branch_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if (in_array($resource, ['pilgrims', 'departures'], true))
                        @php
                            $statuses = $resource === 'pilgrims'
                                ? ['registered' => 'Terdaftar', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Batal']
                                : ['draft' => 'Draft', 'scheduled' => 'Terjadwal', 'departed' => 'Berangkat', 'completed' => 'Selesai', 'cancelled' => 'Batal'];
                        @endphp
                        <select name="status" class="control-field min-w-40">
                            <option value="">Semua status</option>
                            @foreach ($statuses as $status => $label)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif

                    <button class="button-secondary">
                        <i data-lucide="list-filter" class="size-4"></i>
                        Terapkan
                    </button>

                    @if ($hasFilters)
                        <a href="{{ route('master-data.index', $resource) }}" class="button-secondary px-3" title="Hapus filter" aria-label="Hapus semua filter">
                            <i data-lucide="rotate-ccw" class="size-4"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 text-xs text-slate-500 dark:border-slate-800">
            <p>
                @if ($records->total())
                    Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $records->firstItem() }}–{{ $records->lastItem() }}</span>
                    dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $records->total() }}</span> data
                @else
                    Tidak ada data ditampilkan
                @endif
            </p>
            <p class="hidden sm:block">Data terbaru tersinkron otomatis</p>
        </div>

        <div class="hidden overflow-x-auto xl:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.06em] text-slate-500 dark:bg-slate-800/40 dark:text-slate-400">
                    <tr>
                        @foreach ($definition['columns'] as $key => $label)
                            <th class="{{ $key === 'photo_path' ? 'w-20' : '' }} px-5 py-3.5">
                                @if (in_array($key, $definition['sort'], true))
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc']) }}"
                                       class="inline-flex items-center gap-1.5 transition hover:text-blue-600">
                                        {{ $label }}
                                        <i data-lucide="{{ $sort === $key ? ($direction === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrow-up-down' }}"
                                           class="size-3.5 {{ $sort === $key ? 'text-blue-600' : 'text-slate-300 dark:text-slate-600' }}"></i>
                                    </a>
                                @else
                                    {{ $label }}
                                @endif
                            </th>
                        @endforeach
                        @if ($canManage)
                            <th class="w-32 px-5 py-3.5 text-right">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($records as $record)
                        <tr class="group transition hover:bg-blue-50/35 dark:hover:bg-blue-950/10">
                            @foreach ($definition['columns'] as $key => $label)
                                <td class="max-w-56 px-5 py-4 align-middle">
                                    <x-master-data-value :record="$record" :column="$key" :label="$definition['label']" :can-manage="$canManage" />
                                </td>
                            @endforeach
                            @if ($canManage)
                                <td class="px-5 py-4">
                                    @include('master-data.partials.actions')
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($definition['columns']) + ($canManage ? 1 : 0) }}">
                                <x-empty-state icon="database" title="Belum ada {{ str($definition['label'])->lower() }}"
                                               description="{{ $hasFilters ? 'Coba ubah atau hapus filter pencarian.' : 'Data baru yang ditambahkan akan tampil di sini.' }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800 xl:hidden">
            @forelse ($records as $record)
                @php
                    $displayName = $record->full_name ?? $record->name ?? $record->program_name ?? $record->code ?? $definition['label'];
                    $primaryNumber = $record->employee_number ?? $record->registration_number ?? $record->code ?? null;
                @endphp
                <article class="p-4 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/20 sm:p-5">
                    <div class="flex items-start gap-3">
                        @if (array_key_exists('photo_path', $definition['columns']))
                            <x-master-data-value :record="$record" column="photo_path" :label="$definition['label']" :can-manage="$canManage" />
                        @else
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-300">
                                <i data-lucide="{{ $resourceIcon }}" class="size-5"></i>
                            </span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate font-semibold text-slate-900 dark:text-white">{{ $displayName }}</h2>
                            @if ($primaryNumber)
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $primaryNumber }}</p>
                            @endif
                        </div>
                        @if ($canManage)
                            @include('master-data.partials.actions')
                        @endif
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3">
                        @foreach ($definition['columns'] as $key => $label)
                            @continue($key === 'photo_path' || in_array($key, ['full_name', 'name', 'program_name', 'code', 'employee_number', 'registration_number'], true))
                            <div class="{{ in_array($key, ['address', 'user.email'], true) ? 'col-span-2' : '' }} min-w-0">
                                <dt class="mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</dt>
                                <dd class="text-sm">
                                    <x-master-data-value :record="$record" :column="$key" :label="$definition['label']" :can-manage="$canManage" />
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </article>
            @empty
                <x-empty-state icon="database" title="Belum ada {{ str($definition['label'])->lower() }}"
                               description="{{ $hasFilters ? 'Coba ubah atau hapus filter pencarian.' : 'Data baru yang ditambahkan akan tampil di sini.' }}" />
            @endforelse
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800 sm:px-5">{{ $records->links() }}</div>
        @endif
    </section>
</x-app-layout>
