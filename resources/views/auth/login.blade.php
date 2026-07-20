<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold tracking-tight text-slate-950">Login Admin</h2>
        <p class="mt-1 text-sm text-slate-500">Masuk untuk mengelola data Mantau Umroh.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false, loading: false }" @submit="loading = true">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="Masukkan email"
                   class="control-field w-full px-4 py-3">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-5">
            <div class="mb-2 flex items-center justify-between gap-3">
                <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                        Lupa password?
                    </a>
                @endif
            </div>

            <div class="relative">
                <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password"
                       placeholder="Masukkan password"
                       class="control-field w-full px-4 py-3 pr-12">
                <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-2 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                    <span x-show="!showPassword"><i data-lucide="eye" class="size-4.5"></i></span>
                    <span x-cloak x-show="showPassword"><i data-lucide="eye-off" class="size-4.5"></i></span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="mt-5 inline-flex cursor-pointer items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" name="remember"
                   class="size-4 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
            <span>Ingat saya</span>
        </label>

        <button type="submit" class="button-primary mt-6 w-full py-3" :disabled="loading">
            <span x-show="!loading">Masuk</span>
            <span x-cloak x-show="loading" class="inline-flex items-center gap-2">
                <span class="size-4 animate-spin rounded-full border-2 border-white/35 border-t-white"></span>
                Memproses...
            </span>
        </button>
    </form>
</x-guest-layout>
