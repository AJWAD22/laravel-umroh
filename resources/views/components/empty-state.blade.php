@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'description' => 'Data akan ditampilkan di sini setelah tersedia.'])

<div {{ $attributes->class('flex flex-col items-center justify-center px-6 py-14 text-center') }}>
    <div class="mb-4 grid size-14 place-items-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
        <i data-lucide="{{ $icon }}" class="size-7"></i>
    </div>
    <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</p>
    <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @if (trim((string) $slot))
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
