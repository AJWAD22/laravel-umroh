<div class="flex items-center justify-end gap-1">
    @if ($resource === 'groups')
        <a href="{{ route('groups.members.index', $record) }}" class="icon-action text-emerald-600 hover:text-emerald-700"
           title="Kelola anggota" aria-label="Kelola anggota {{ $record->name }}">
            <i data-lucide="users-round" class="size-4"></i>
        </a>
        <form method="POST" action="{{ route('groups.reset-pins', $record) }}"
              class="flex items-center gap-1"
              data-confirm-title="Reset PIN Rombongan"
              data-confirm="Semua PIN jamaah aktif di rombongan ini akan dibuat ulang dan perangkat lama dicabut. Lanjutkan?">
            @csrf
            <input name="reason" required minlength="8" maxlength="255"
                   placeholder="Alasan reset"
                   class="control-field h-9 w-32 text-xs md:w-40"
                   aria-label="Alasan reset PIN rombongan {{ $record->name }}">
            <button class="icon-action text-violet-600 hover:text-violet-700" title="Reset PIN rombongan"
                    aria-label="Reset PIN jamaah rombongan {{ $record->name }}">
                <i data-lucide="key-round" class="size-4"></i>
            </button>
        </form>
    @endif

    @if ($resource === 'pilgrims')
        <form method="POST" action="{{ route('master-data.pilgrims.regenerate-pin', $record) }}"
              class="flex items-center gap-1"
              data-confirm-title="Buat Ulang PIN"
              data-confirm="PIN lama akan langsung dibatalkan. Lanjutkan membuat PIN aktivasi baru?">
            @csrf
            <input name="reason" required minlength="8" maxlength="255"
                   placeholder="Alasan reset"
                   class="control-field h-9 w-32 text-xs md:w-40"
                   aria-label="Alasan buat ulang PIN {{ $record->full_name }}">
            <button class="icon-action text-violet-600 hover:text-violet-700" title="Buat PIN baru"
                    aria-label="Buat PIN baru untuk {{ $record->full_name }}">
                <i data-lucide="key-round" class="size-4"></i>
            </button>
        </form>
    @endif

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
</div>
