<div class="flex items-center justify-end gap-1">
    @php($canManage = auth()->user()->can($definition['permission']))

    @if ($canManage && $resource === 'groups')
        <a href="{{ route('groups.members.index', $record) }}" class="icon-action text-emerald-600 hover:text-emerald-700"
           title="Kelola anggota" aria-label="Kelola anggota {{ $record->name }}">
            <i data-lucide="users-round" class="size-4"></i>
        </a>
    @endif

    @if ($canManage && $resource === 'departures' && auth()->user()->hasRole('admin-cabang'))
        <form method="POST" action="{{ route('departures.prepare-group', $record) }}">
            @csrf
            <button class="icon-action text-emerald-600 hover:text-emerald-700"
                    title="Rombongan & PIN" aria-label="Kelola rombongan dan PIN {{ $record->program_name }}">
                <i data-lucide="key-round" class="size-4"></i>
            </button>
        </form>
    @endif

    @if ($canManage)
        <a href="{{ route('master-data.edit', [$resource, $record->id]) }}"
           class="icon-action text-blue-600 hover:text-blue-700" title="Edit"
           aria-label="Edit {{ $definition['label'] }}">
            <i data-lucide="pencil" class="size-4"></i>
        </a>

        <form method="POST" action="{{ route('master-data.destroy', [$resource, $record->id]) }}"
              data-confirm-title="Hapus {{ $definition['label'] }}"
              data-confirm="Data yang dihapus tidak akan tampil pada daftar. Apakah Anda yakin ingin melanjutkan?">
            @csrf
            @method('DELETE')
            <button class="icon-action text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/40"
                    title="Hapus" aria-label="Hapus {{ $definition['label'] }}">
                <i data-lucide="trash-2" class="size-4"></i>
            </button>
        </form>
    @else
        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500 dark:bg-slate-800 dark:text-slate-300">Lihat saja</span>
    @endif
</div>
