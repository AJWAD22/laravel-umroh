<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Masuk · {{ config('app.name', 'Mantau Umroh') }}</title>
        <link rel="icon" type="image/png" media="(prefers-color-scheme: light)" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
        <link rel="icon" type="image/png" media="(prefers-color-scheme: dark)" href="{{ asset('images/mantau-umroh-icon-dark.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <main class="grid min-h-screen bg-slate-50 lg:grid-cols-[minmax(420px,0.9fr)_minmax(540px,1.1fr)]">
            <section class="relative hidden overflow-hidden bg-[#07142d] px-10 py-9 text-white lg:flex lg:flex-col xl:px-16 xl:py-12">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_10%,_rgba(37,99,235,0.38),_transparent_32%),radial-gradient(circle_at_90%_85%,_rgba(6,182,212,0.20),_transparent_35%)]"></div>
                <div class="absolute inset-0 opacity-[0.055] [background-image:linear-gradient(rgba(255,255,255,.7)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.7)_1px,transparent_1px)] [background-size:44px_44px]"></div>
                <div class="absolute -right-28 top-1/3 size-80 rounded-full border border-white/10"></div>
                <div class="absolute -right-14 top-[38%] size-52 rounded-full border border-white/10"></div>

                <div class="relative flex items-center gap-3">
                    <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Logo Mantau Umroh"
                         class="size-11 rounded-xl object-contain shadow-xl shadow-blue-950 ring-1 ring-white/15">
                    <div>
                        <p class="font-bold tracking-tight">Mantau Umroh</p>
                        <p class="mt-0.5 text-[11px] tracking-wide text-blue-200/70">MONITORING • AMAN • TERKONEKSI</p>
                    </div>
                </div>

                <div class="relative my-auto max-w-xl py-14">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1.5 text-xs font-semibold text-emerald-200">
                        <span class="size-1.5 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,.9)]"></span>
                        Sistem monitoring operasional
                    </span>
                    <h1 class="mt-7 max-w-lg text-4xl font-bold leading-[1.15] tracking-tight xl:text-5xl">
                        Kendali perjalanan umroh dalam satu sistem.
                    </h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-slate-300 xl:text-lg">
                        Pantau jamaah, koordinasikan petugas, dan tangani kondisi darurat melalui pusat kendali yang aman dan terintegrasi.
                    </p>

                    <div class="mt-9 grid max-w-lg gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur">
                            <i data-lucide="map-pinned" class="size-5 text-cyan-300"></i>
                            <p class="mt-3 text-sm font-semibold">Monitoring</p>
                            <p class="mt-1 text-xs leading-5 text-slate-400">Posisi dan aktivitas jamaah</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur">
                            <i data-lucide="shield-check" class="size-5 text-emerald-300"></i>
                            <p class="mt-3 text-sm font-semibold">Terproteksi</p>
                            <p class="mt-1 text-xs leading-5 text-slate-400">Akses sesuai kewenangan</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur">
                            <i data-lucide="siren" class="size-5 text-red-300"></i>
                            <p class="mt-3 text-sm font-semibold">Respons SOS</p>
                            <p class="mt-1 text-xs leading-5 text-slate-400">Penanganan lebih terarah</p>
                        </div>
                    </div>
                </div>

                <div class="relative flex items-center justify-between text-xs text-slate-500">
                    <span>© {{ date('Y') }} Mantau Umroh</span>
                    <span class="flex items-center gap-1.5"><i data-lucide="lock-keyhole" class="size-3.5"></i> Koneksi aman</span>
                </div>
            </section>

            <section class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-8 lg:px-12">
                <div class="absolute inset-x-0 top-0 h-64 bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.08),_transparent_65%)] lg:hidden"></div>
                <div class="relative w-full max-w-[440px]">
                    <div class="mb-8 text-center lg:hidden">
                        <img src="{{ asset('images/mantau-umroh-icon-light.png') }}" alt="Logo Mantau Umroh"
                             class="mx-auto size-16 rounded-2xl object-contain shadow-lg ring-1 ring-slate-200">
                        <p class="mt-3 text-lg font-bold tracking-tight">Mantau Umroh</p>
                        <p class="mt-1 text-xs font-medium tracking-wider text-slate-500">MONITORING • AMAN • TERKONEKSI</p>
                    </div>

                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.08)] sm:p-9 lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none">
                        {{ $slot }}
                    </div>

                    <p class="mt-7 text-center text-xs leading-5 text-slate-400">
                        Dengan masuk, Anda menyetujui kebijakan keamanan dan penggunaan sistem.
                    </p>
                </div>
            </section>
        </main>
    </body>
</html>
