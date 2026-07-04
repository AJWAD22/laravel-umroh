<x-app-layout>
    <x-slot:title>Profil</x-slot:title>
    <x-slot:header>
        <div>
            <nav class="mb-2 text-sm text-slate-500">Pengaturan / Profil</nav>
            <h1 class="text-2xl font-bold">Profil Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Perbarui identitas dan informasi kontak akun.</p>
        </div>
    </x-slot:header>

    <div class="grid gap-6 xl:grid-cols-[300px_minmax(0,1fr)]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @if ($user->photo_path)
                <img src="{{ asset('storage/'.$user->photo_path) }}" alt="{{ $user->name }}" class="mx-auto size-24 rounded-3xl object-cover">
            @else
                <span class="mx-auto grid size-24 place-items-center rounded-3xl bg-blue-600 text-3xl font-bold text-white">{{ str($user->name)->substr(0, 2)->upper() }}</span>
            @endif
            <h2 class="mt-4 font-bold">{{ $user->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $user->getRoleNames()->first() }}</p>
            <div class="mt-5 rounded-xl bg-slate-50 p-3 text-left text-sm dark:bg-slate-800">
                <p class="text-xs uppercase tracking-wide text-slate-400">Cakupan Akses</p>
                <p class="mt-1 font-semibold">{{ $user->branch?->name ?? 'Nasional' }}</p>
            </div>
        </aside>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('PATCH')
                <div class="grid gap-5 md:grid-cols-2">
                    <label><span class="mb-1.5 block text-sm font-medium">Nama Lengkap</span><input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">@error('name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label><span class="mb-1.5 block text-sm font-medium">Email</span><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">@error('email')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label><span class="mb-1.5 block text-sm font-medium">Nomor Telepon</span><input name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">@error('phone_number')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label><span class="mb-1.5 block text-sm font-medium">Cabang</span><input value="{{ $user->branch?->name ?? 'Nasional' }}" disabled class="w-full rounded-xl border-slate-200 bg-slate-100 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800"></label>
                    <label class="md:col-span-2"><span class="mb-1.5 block text-sm font-medium">Foto Profil</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-300 p-2 text-sm dark:border-slate-700 dark:bg-slate-950"><span class="mt-1 block text-xs text-slate-500">JPG, PNG, atau WebP. Maksimal 2 MB.</span>@error('photo')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                </div>
                <div class="flex justify-end border-t border-slate-100 pt-5 dark:border-slate-800"><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan Profil</button></div>
            </form>
        </section>
    </div>
</x-app-layout>
