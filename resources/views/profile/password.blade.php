<x-app-layout>
    <x-slot:title>Ganti Password</x-slot:title>
    <x-slot:header>
        <div>
            <nav class="mb-2 text-sm text-slate-500">Pengaturan / Password</nav>
            <h1 class="text-2xl font-bold">Ganti Password</h1>
            <p class="mt-1 text-sm text-slate-500">Gunakan password kuat dan berbeda dari layanan lain.</p>
        </div>
    </x-slot:header>

    <section class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf @method('PUT')
            <label class="block"><span class="mb-1.5 block text-sm font-medium">Password Saat Ini</span><input type="password" name="current_password" autocomplete="current-password" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">@error('current_password', 'updatePassword')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
            <label class="block"><span class="mb-1.5 block text-sm font-medium">Password Baru</span><input type="password" name="password" autocomplete="new-password" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">@error('password', 'updatePassword')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
            <label class="block"><span class="mb-1.5 block text-sm font-medium">Konfirmasi Password Baru</span><input type="password" name="password_confirmation" autocomplete="new-password" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950"></label>
            <div class="flex justify-end border-t border-slate-100 pt-5 dark:border-slate-800"><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Perbarui Password</button></div>
        </form>
    </section>
</x-app-layout>
