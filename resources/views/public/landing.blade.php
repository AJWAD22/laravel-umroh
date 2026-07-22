<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paket Umroh - {{ config('app.name', 'Mantau Umroh') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900">
    <main>
        <section class="relative isolate overflow-hidden bg-slate-950 text-white">
            <div class="absolute inset-0 bg-[url('/images/umrah-monitor-logo.png')] bg-center bg-no-repeat opacity-10"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_25%,rgba(20,184,166,.32),transparent_34%),linear-gradient(135deg,#071022_0%,#0f3b5f_48%,#0d9488_100%)]"></div>
            <nav class="relative mx-auto flex max-w-7xl items-center justify-between px-5 py-5">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Mantau Umroh" class="size-11 rounded-xl object-contain ring-1 ring-white/15">
                    <span class="text-lg font-extrabold">Mantau Umroh</span>
                </a>
                <a href="{{ route('login') }}" class="rounded-full border border-white/25 px-4 py-2 text-sm font-bold text-white/90 transition hover:bg-white hover:text-slate-950">Login Admin</a>
            </nav>

            <div class="relative mx-auto grid min-h-[560px] max-w-7xl content-center gap-10 px-5 pb-14 pt-12 lg:grid-cols-[1.08fr_.92fr] lg:items-center">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-100">Paket keberangkatan umroh</p>
                    <h1 class="mt-4 max-w-3xl text-4xl font-extrabold leading-tight sm:text-6xl">Pilih paket, registrasi, lalu perjalanan jamaah terpantau.</h1>
                    <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-100">Lihat jadwal keberangkatan, hotel, pesawat, durasi hari, dan agenda perjalanan dari cabang resmi.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="#paket" class="button-primary bg-none shadow-none">Lihat Paket</a>
                        <a href="{{ route('login') }}" class="button-secondary border-white/30 bg-white/10 text-white hover:bg-white/20">Portal Admin</a>
                    </div>
                </div>
                <div class="rounded-[2rem] border border-white/15 bg-white/10 p-5 shadow-2xl backdrop-blur">
                    <div class="grid gap-3">
                        <div class="rounded-2xl bg-white p-5 text-slate-950">
                            <p class="text-sm font-bold text-teal-700">Tracking rombongan</p>
                            <p class="mt-2 text-3xl font-extrabold">{{ $packages->count() }} paket aktif</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-white/90 p-4 text-slate-950">
                                <p class="text-xs font-bold uppercase text-slate-500">Hotel</p>
                                <p class="mt-1 font-extrabold">Makkah & Madinah</p>
                            </div>
                            <div class="rounded-2xl bg-white/90 p-4 text-slate-950">
                                <p class="text-xs font-bold uppercase text-slate-500">Mobile</p>
                                <p class="mt-1 font-extrabold">Titik kumpul sync</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="paket" class="mx-auto max-w-7xl px-5 py-14">
            <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-700">Keberangkatan tersedia</p>
                    <h2 class="mt-2 text-3xl font-extrabold text-slate-950">Paket Umroh</h2>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($packages as $package)
                    @include('public.partials.package-card', ['package' => $package])
                @empty
                    <div class="rounded-2xl border border-slate-200 p-8 text-slate-600">Belum ada paket keberangkatan yang dibuka untuk publik.</div>
                @endforelse
            </div>
        </section>
    </main>
</body>
</html>
