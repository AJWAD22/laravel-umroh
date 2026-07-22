<x-app-layout>
    <x-slot:title>Pengaturan Sistem</x-slot:title>
    <x-slot:header>
        <div>
            <nav class="mb-2 text-sm text-slate-500">Pengaturan / Sistem</nav>
            <h1 class="text-2xl font-bold">Pengaturan Sistem</h1>
            <p class="mt-1 text-sm text-slate-500">Konfigurasi global aplikasi dan parameter monitoring.</p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('settings.system.update') }}" class="space-y-6">
        @csrf @method('PUT')
        @foreach ($settings as $group => $items)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-bold">{{ str($group)->headline() }}</h2>
                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    @foreach ($items as $setting)
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium">{{ $setting->label }}</span>
                            @if ($setting->type === 'textarea')
                                <textarea name="{{ $setting->key }}" rows="4"
                                          class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">{{ old($setting->key, $setting->value) }}</textarea>
                            @else
                                <input type="{{ $setting->type === 'email' ? 'email' : ($setting->type === 'integer' ? 'number' : 'text') }}"
                                       name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}"
                                       class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @endif
                            @if ($setting->description)<span class="mt-1 block text-xs text-slate-500">{{ $setting->description }}</span>@endif
                            @error($setting->key)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
        <div class="sticky bottom-4 flex justify-end"><button class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg hover:bg-blue-700">Simpan Pengaturan</button></div>
    </form>
</x-app-layout>
