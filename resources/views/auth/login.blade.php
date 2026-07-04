<x-guest-layout>
    <div class="mb-7">
        <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-blue-600">
            <span class="h-px w-5 bg-blue-600"></span>
            Portal Administrasi
        </span>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Selamat datang kembali</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Masukkan akun administrator untuk melanjutkan ke pusat kendali.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false, loading: false }" @submit="loading = true">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
            <div class="relative">
                <i data-lucide="mail" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-slate-400"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       placeholder="nama@perusahaan.com"
                       class="control-field w-full py-3 pl-10 pr-4">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-5">
            <div class="mb-2 flex items-center justify-between gap-3">
                <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="rounded text-xs font-semibold text-blue-600 transition hover:text-blue-700">
                        Lupa password?
                    </a>
                @endif
            </div>
            <div class="relative">
                <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-slate-400"></i>
                <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password"
                       placeholder="Masukkan password"
                       class="control-field w-full py-3 pl-10 pr-12">
                <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-2 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                    <span x-show="!showPassword"><i data-lucide="eye" class="size-4.5"></i></span>
                    <span x-cloak x-show="showPassword"><i data-lucide="eye-off" class="size-4.5"></i></span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="mt-5 inline-flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" name="remember"
                   class="size-4.5 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
            <span>Ingat saya di perangkat ini</span>
        </label>

        <button type="submit" class="button-primary mt-7 w-full py-3" :disabled="loading">
            <span x-show="!loading" class="inline-flex items-center gap-2">
                Masuk ke Dashboard
                <i data-lucide="arrow-right" class="size-4"></i>
            </span>
            <span x-cloak x-show="loading" class="inline-flex items-center gap-2">
                <span class="size-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                Memverifikasi...
            </span>
        </button>

        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-slate-400">
            <i data-lucide="shield-check" class="size-4 text-emerald-500"></i>
            Akses dilindungi dan aktivitas tercatat
        </div>
    </form>
</x-guest-layout>
