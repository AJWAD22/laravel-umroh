<x-app-layout>
    <x-slot:title>Anggota {{ $group->name }}</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Data Master / Rombongan / Pembagian Jamaah</nav>
                <h1 class="text-2xl font-bold">{{ $group->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $group->branch->name }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" data-group-staff-open class="button-primary">
                    <i data-lucide="user-round-cog" class="size-4"></i>
                    Tentukan Petugas
                </button>
                <a href="{{ route('master-data.index', 'groups') }}" class="button-secondary">Kembali</a>
            </div>
        </div>
    </x-slot:header>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Anggota Aktif', 'value' => $group->members()->where('status', 'active')->count()],
            ['label' => 'Kapasitas', 'value' => $group->capacity ?: 'Tanpa batas'],
            ['label' => 'Tour Leader', 'value' => $group->tourLeader?->full_name ?: 'Belum ditentukan'],
            ['label' => 'Muthawwif', 'value' => $group->muthawwif?->full_name ?: 'Belum ditentukan'],
        ] as $summary)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-slate-500">{{ $summary['label'] }}</p>
                <p class="mt-2 truncate text-lg font-bold">{{ $summary['value'] }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                <h2 class="font-semibold">Anggota Rombongan</h2>
                <form method="GET" class="mt-3 flex gap-2">
                    <input name="member_search" value="{{ request('member_search') }}" placeholder="Cari anggota..."
                           class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <button class="rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white">Cari</button>
                </form>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($members as $member)
                    <div class="flex items-center gap-3 px-5 py-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-50 font-semibold text-blue-700">
                            {{ str($member->pilgrim->full_name)->substr(0, 2)->upper() }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ $member->pilgrim->full_name }}</p>
                            <p class="text-xs text-slate-500">{{ $member->pilgrim->registration_number }}</p>
                        </div>
                        <form method="POST" action="{{ route('groups.members.destroy', [$group, $member]) }}" class="ml-auto"
                              data-confirm-title="Keluarkan Jamaah"
                              data-confirm="Jamaah akan dikeluarkan dari rombongan ini. Lanjutkan?">
                            @csrf @method('DELETE')
                            <button class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Keluarkan</button>
                        </form>
                    </div>
                @empty
                    <x-empty-state icon="users" title="Rombongan belum memiliki anggota"
                                   description="Tambahkan jamaah yang tersedia ke dalam rombongan ini." />
                @endforelse
            </div>
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $members->links() }}</div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                <h2 class="font-semibold">Jamaah Tersedia</h2>
                <form method="GET" class="mt-3 flex gap-2">
                    <input name="available_search" value="{{ request('available_search') }}" placeholder="Cari jamaah tersedia..."
                           class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <button class="rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white">Cari</button>
                </form>
            </div>

            <form method="POST" action="{{ route('groups.members.store', $group) }}">
                @csrf
                <div class="max-h-[480px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                    @forelse ($availablePilgrims as $pilgrim)
                        <label class="flex cursor-pointer items-center gap-3 px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <input type="checkbox" name="pilgrim_ids[]" value="{{ $pilgrim->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold">{{ $pilgrim->full_name }}</span>
                                <span class="block text-xs text-slate-500">{{ $pilgrim->registration_number }}</span>
                            </span>
                        </label>
                    @empty
                        <x-empty-state icon="user-round-check" title="Tidak ada jamaah tersedia"
                                       description="Semua jamaah aktif sudah ditempatkan di rombongan lain atau tidak cocok dengan filter pencarian." />
                    @endforelse
                </div>
                @if ($availablePilgrims->isNotEmpty())
                    <div class="border-t border-slate-200 p-4 text-right dark:border-slate-800">
                        <button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Tambahkan Jamaah</button>
                    </div>
                @endif
            </form>
        </section>
    </div>

    <dialog data-group-staff-dialog class="m-auto w-[calc(100%-2rem)] max-w-xl rounded-3xl bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60 dark:bg-slate-900 dark:text-white">
        <form method="POST" action="{{ route('groups.staff.update', $group) }}">
            @csrf
            @method('PATCH')
            <div class="flex items-start justify-between border-b border-slate-200 p-6 dark:border-slate-800">
                <div>
                    <h2 class="text-lg font-bold">Tentukan Petugas Rombongan</h2>
                    <p class="mt-1 text-sm text-slate-500">Petugas terpilih dapat melihat jamaah dan lokasi rombongan ini.</p>
                </div>
                <button type="button" data-group-staff-close class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Tutup">
                    <i data-lucide="x" class="size-5"></i>
                </button>
            </div>
            <div class="grid gap-5 p-6">
                <label>
                    <span class="mb-1.5 block text-sm font-semibold">Tour Leader</span>
                    <select name="tour_leader_id" class="control-field w-full">
                        <option value="">Belum ditentukan</option>
                        @foreach ($tourLeaders as $id => $name)
                            <option value="{{ $id }}" @selected((string) $group->tour_leader_id === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @if ($tourLeaders->isEmpty())<span class="mt-1.5 block text-xs text-amber-600">Belum ada Tour Leader aktif di cabang ini.</span>@endif
                </label>
                <label>
                    <span class="mb-1.5 block text-sm font-semibold">Muthawwif</span>
                    <select name="muthawwif_id" class="control-field w-full">
                        <option value="">Belum ditentukan</option>
                        @foreach ($muthawwifs as $id => $name)
                            <option value="{{ $id }}" @selected((string) $group->muthawwif_id === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @if ($muthawwifs->isEmpty())<span class="mt-1.5 block text-xs text-amber-600">Belum ada Muthawwif aktif di cabang ini.</span>@endif
                </label>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/50">
                <button type="button" data-group-staff-close class="button-secondary">Batal</button>
                <button class="button-primary">Simpan Petugas</button>
            </div>
        </form>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dialog = document.querySelector('[data-group-staff-dialog]');
            document.querySelector('[data-group-staff-open]')?.addEventListener('click', () => dialog?.showModal());
            document.querySelectorAll('[data-group-staff-close]').forEach((button) => {
                button.addEventListener('click', () => dialog?.close());
            });
        });
    </script>
</x-app-layout>
