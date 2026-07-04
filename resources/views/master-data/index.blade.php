<x-app-layout>
    @php $canManage = auth()->user()->can($definition['permission']); @endphp
    <x-slot:title>{{ $definition['label'] }}</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Master Data / {{ $definition['label'] }}</nav>
                <h1 class="text-2xl font-bold">{{ $definition['label'] }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $canManage ? 'Kelola' : 'Pantau' }} data {{ str($definition['label'])->lower() }} secara terpusat.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('master-data.create', $resource) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    <span class="text-lg leading-none">+</span> Tambah {{ $definition['label'] }}
                </a>
            @endif
        </div>
    </x-slot:header>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" class="grid gap-3 border-b border-slate-200 p-4 dark:border-slate-800 md:grid-cols-[minmax(220px,1fr)_180px_180px_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Cari {{ str($definition['label'])->lower() }}..."
                   class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950">
            @if (auth()->user()->hasRole('super-admin') && $resource !== 'branches')
                <select name="branch_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <option value="">Semua cabang</option>
                    @foreach ($options['branches'] as $id => $name)
                        <option value="{{ $id }}" @selected((string) request('branch_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            @else
                <span></span>
            @endif
            @if (in_array($resource, ['pilgrims', 'departures']))
                @php
                    $statuses = $resource === 'pilgrims'
                        ? ['registered' => 'Terdaftar', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Batal']
                        : ['draft' => 'Draft', 'scheduled' => 'Terjadwal', 'departed' => 'Berangkat', 'completed' => 'Selesai', 'cancelled' => 'Batal'];
                @endphp
                <select name="status" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status => $label)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            @else
                <span></span>
            @endif
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white dark:bg-slate-700">Terapkan</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/60">
                    <tr>
                        @foreach ($definition['columns'] as $key => $label)
                            <th class="whitespace-nowrap px-5 py-3">
                                @if (in_array($key, $definition['sort'], true))
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc']) }}" class="hover:text-blue-600">
                                        {{ $label }} {{ $sort === $key ? ($direction === 'asc' ? '↑' : '↓') : '' }}
                                    </a>
                                @else
                                    {{ $label }}
                                @endif
                            </th>
                        @endforeach
                        @if ($resource === 'pilgrims' && $canManage)
                            <th class="px-5 py-3">PIN Aktivasi</th>
                        @endif
                        @if ($canManage)<th class="px-5 py-3 text-right">Aksi</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($records as $record)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                            @foreach ($definition['columns'] as $key => $label)
                                @php
                                    $value = $key === 'activation_pin' ? $record->activationPin() : data_get($record, $key);
                                @endphp
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($key === 'photo_path')
                                        @php
                                            $displayName = $record->full_name ?? $record->name ?? $definition['label'];
                                        @endphp
                                        @if ($value)
                                            <img src="{{ asset('storage/'.$value) }}" alt="{{ $displayName }}" class="size-11 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                                        @else
                                            <span class="grid size-11 place-items-center rounded-xl bg-slate-100 text-sm font-bold text-slate-500 dark:bg-slate-800">
                                                {{ str($displayName)->substr(0, 2)->upper() }}
                                            </span>
                                        @endif
                                    @elseif ($key === 'activation_pin')
                                        @if ($canManage && $value)
                                            <span class="font-mono text-base font-bold tracking-widest text-blue-700">{{ substr($value, 0, 3) }} {{ substr($value, 3) }}</span>
                                        @elseif ($record->activation_pin_used_at)
                                            <x-status-badge value="completed" label="Sudah digunakan" />
                                        @else
                                            <span class="text-slate-400">Belum dibuat</span>
                                        @endif
                                    @elseif (str_starts_with($key, 'is_'))
                                        <x-status-badge :value="$value ? 'yes' : 'no'" />
                                    @elseif ($key === 'status')
                                        <x-status-badge :value="$value" />
                                    @elseif ($value instanceof \Carbon\CarbonInterface)
                                        {{ $value->format('d M Y') }}
                                    @else
                                        {{ $value ?: '—' }}
                                    @endif
                                </td>
                            @endforeach
                            @if ($canManage)
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                @if ($resource === 'groups')
                                    <a href="{{ route('groups.members.index', $record) }}" class="mr-3 font-medium text-emerald-600 hover:text-emerald-800">Anggota</a>
                                @endif
                                @if ($resource === 'pilgrims')
                                    <form method="POST" action="{{ route('master-data.pilgrims.regenerate-pin', $record) }}" class="mr-3 inline"
                                          data-confirm-title="Buat Ulang PIN"
                                          data-confirm="PIN lama akan langsung dibatalkan. Lanjutkan membuat PIN aktivasi baru?">
                                        @csrf
                                        <button class="font-medium text-violet-600 hover:text-violet-800">PIN Baru</button>
                                    </form>
                                @endif
                                <a href="{{ route('master-data.edit', [$resource, $record->id]) }}" class="font-medium text-blue-600 hover:text-blue-800">Edit</a>
                                <form method="POST" action="{{ route('master-data.destroy', [$resource, $record->id]) }}" class="ml-3 inline"
                                      data-confirm-title="Hapus {{ $definition['label'] }}"
                                      data-confirm="Data yang dihapus tidak akan tampil pada daftar. Apakah Anda yakin ingin melanjutkan?">
                                    @csrf @method('DELETE')
                                    <button class="font-medium text-red-600 hover:text-red-800">Hapus</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($definition['columns']) + ($canManage ? 1 : 0) }}">
                                <x-empty-state icon="database" title="Belum ada {{ str($definition['label'])->lower() }}"
                                               description="Ubah filter pencarian atau tambahkan data baru." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $records->links() }}</div>
    </section>
</x-app-layout>
