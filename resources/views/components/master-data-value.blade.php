@props(['record', 'column', 'label' => null, 'canManage' => false])

@php
    $value = match ($column) {
        'activation_pin' => $record->activationPin(),
        'active_group' => $record->groupMemberships?->firstWhere('status', 'active')?->group?->name,
        default => data_get($record, $column),
    };
    $categoryLabels = [
        'ibadah' => 'Tempat Ibadah',
        'hotel' => 'Hotel',
        'titik_kumpul' => 'Titik Kumpul',
        'kesehatan' => 'Kesehatan',
        'transportasi' => 'Transportasi',
        'belanja' => 'Belanja',
        'lainnya' => 'Lainnya',
    ];
    $cityLabels = [
        'makkah' => 'Makkah',
        'madinah' => 'Madinah',
        'jeddah' => 'Jeddah',
        'other' => 'Lainnya',
    ];
@endphp

@if ($column === 'photo_path')
    @php $displayName = $record->full_name ?? $record->name ?? $label ?? 'Data'; @endphp
    @if ($value)
        <img src="{{ asset('storage/'.$value) }}" alt="Foto {{ $displayName }}"
             class="size-11 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-700">
    @else
        <span class="grid size-11 place-items-center rounded-xl bg-gradient-to-br from-slate-100 to-slate-50 text-sm font-bold text-slate-500 ring-1 ring-slate-200/70 dark:from-slate-800 dark:to-slate-900 dark:text-slate-300 dark:ring-slate-700">
            {{ str($displayName)->substr(0, 2)->upper() }}
        </span>
    @endif
@elseif ($column === 'activation_pin')
    @if ($canManage && $value)
        <span class="font-mono text-sm font-bold tracking-[0.18em] text-blue-700 dark:text-blue-300">
            {{ substr($value, 0, 3) }} {{ substr($value, 3) }}
        </span>
    @elseif ($record->activation_pin_used_at)
        <x-status-badge value="completed" label="Sudah digunakan" />
    @else
        <span class="text-slate-400">Belum dibuat</span>
    @endif
@elseif (str_starts_with($column, 'is_'))
    <x-status-badge :value="$value ? 'yes' : 'no'" />
@elseif ($column === 'status')
    <x-status-badge :value="$value" />
@elseif ($column === 'category')
    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
        {{ $categoryLabels[$value] ?? (filled($value) ? $value : '—') }}
    </span>
@elseif ($column === 'city')
    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
        {{ $cityLabels[$value] ?? (filled($value) ? $value : '—') }}
    </span>
@elseif ($value instanceof \Carbon\CarbonInterface)
    <span class="text-slate-700 dark:text-slate-200">{{ $value->translatedFormat('d M Y') }}</span>
@else
    <span class="break-words text-slate-700 dark:text-slate-200">{{ filled($value) ? $value : '—' }}</span>
@endif
