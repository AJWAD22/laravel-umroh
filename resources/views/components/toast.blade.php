@php
    $statusMessages = [
        'profile-updated' => 'Profil berhasil diperbarui.',
        'password-updated' => 'Password berhasil diperbarui.',
    ];
    $status = session('status');
    $message = session('error') ?? session('success') ?? ($status ? ($statusMessages[$status] ?? null) : null);
    $type = session('error') ? 'error' : 'success';
@endphp

@if ($message)
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-2 opacity-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-end="translate-y-2 opacity-0"
         class="fixed bottom-5 right-5 z-[100] flex max-w-sm items-start gap-3 rounded-2xl border bg-white p-4 shadow-2xl dark:bg-slate-900 {{ $type === 'error' ? 'border-red-200 dark:border-red-900' : 'border-emerald-200 dark:border-emerald-900' }}"
         role="status">
        <span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $type === 'error' ? 'bg-red-50 text-red-600 dark:bg-red-950' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950' }}">
            <i data-lucide="{{ $type === 'error' ? 'circle-alert' : 'circle-check' }}" class="size-5"></i>
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold">{{ $type === 'error' ? 'Terjadi kesalahan' : 'Berhasil' }}</p>
            <p class="mt-0.5 text-sm text-slate-500">{{ $message }}</p>
        </div>
        <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-700" aria-label="Tutup notifikasi">
            <i data-lucide="x" class="size-4"></i>
        </button>
    </div>
@endif
