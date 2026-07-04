@props(['label' => 'Memuat data...'])

<div {{ $attributes->class('flex items-center justify-center gap-3 px-6 py-12 text-sm text-slate-500') }}>
    <span class="size-5 animate-spin rounded-full border-2 border-slate-200 border-t-blue-600 dark:border-slate-700"></span>
    <span>{{ $label }}</span>
</div>
