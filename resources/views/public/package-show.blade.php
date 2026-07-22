<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $package->program_name }} - {{ config('app.name', 'Mantau Umroh') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900">
    <main>
        <section class="bg-slate-950 text-white">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Mantau Umroh" class="size-11 rounded-xl object-contain ring-1 ring-white/15">
                    <span class="text-lg font-extrabold">Mantau Umroh</span>
                </a>
                <a href="{{ route('landing') }}#paket" class="rounded-full border border-white/25 px-4 py-2 text-sm font-bold text-white/90 transition hover:bg-white hover:text-slate-950">Paket Lain</a>
            </nav>
            <div class="mx-auto max-w-7xl px-5 pb-12 pt-8">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-100">{{ $package->branch?->name }}</p>
                <h1 class="mt-3 max-w-4xl text-4xl font-extrabold leading-tight sm:text-5xl">{{ $package->program_name }}</h1>
                <p class="mt-4 max-w-3xl text-lg leading-8 text-slate-200">{{ $package->description ?: 'Paket perjalanan umroh dengan jadwal, hotel, dan pendampingan rombongan yang terhubung ke sistem monitoring jamaah.' }}</p>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-6 px-5 py-10 lg:grid-cols-[1fr_420px]">
            <div class="space-y-6">
                @if (session('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-semibold text-emerald-800">{{ session('success') }}</div>
                @endif

                <section class="travel-panel p-6">
                    <h2 class="text-2xl font-extrabold">Ringkasan Paket</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="travel-chip"><i data-lucide="calendar-days" class="size-4"></i>{{ $package->departure_date->translatedFormat('d M Y') }} - {{ $package->return_date->translatedFormat('d M Y') }}</div>
                        <div class="travel-chip"><i data-lucide="clock" class="size-4"></i>{{ $package->duration_days }} hari</div>
                        <div class="travel-chip"><i data-lucide="plane" class="size-4"></i>{{ $package->airline ?: 'Maskapai menyusul' }} {{ $package->flight_number }}</div>
                        <div class="travel-chip"><i data-lucide="wallet" class="size-4"></i>{{ $package->price ? 'Rp '.number_format($package->price, 0, ',', '.') : 'Hubungi admin' }}</div>
                        <div class="travel-chip"><i data-lucide="users" class="size-4"></i>{{ $package->remaining_quota === null ? 'Kuota fleksibel' : $package->remaining_quota.' kursi tersisa' }}</div>
                    </div>
                </section>

                <section class="travel-panel p-6">
                    <h2 class="text-2xl font-extrabold">Hotel</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @forelse ($package->hotels as $hotel)
                            <article class="rounded-xl border border-slate-200 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{{ ucfirst($hotel->city) }}</p>
                                <h3 class="mt-1 font-extrabold">{{ $hotel->name }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $hotel->address ?: 'Alamat hotel menyusul.' }}</p>
                            </article>
                        @empty
                            <p class="text-slate-600">Hotel paket akan diinformasikan oleh admin cabang.</p>
                        @endforelse
                    </div>
                </section>

                <section class="travel-panel p-6">
                    <h2 class="text-2xl font-extrabold">Jadwal Perjalanan</h2>
                    <div class="mt-5 space-y-4">
                        @forelse ($package->itineraries as $item)
                            <article class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[88px_1fr]">
                                <div class="text-sm font-extrabold text-teal-700">Hari {{ $item->day_number }}</div>
                                <div>
                                    <h3 class="font-extrabold">{{ $item->title }}</h3>
                                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ $item->city }}</p>
                                    <p class="mt-2 leading-7 text-slate-600">{{ $item->description }}</p>
                                </div>
                            </article>
                        @empty
                            <p class="text-slate-600">Jadwal harian paket akan dilengkapi oleh admin cabang.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="travel-panel h-fit p-6">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-teal-700">Tertarik paket ini?</p>
                <h2 class="mt-2 text-2xl font-extrabold">Mulai Registrasi Jamaah</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">Isi biodata jamaah terlebih dahulu, kemudian pilih paket perjalanan pada langkah berikutnya.</p>
                @if ($package->remaining_quota === 0)
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Kuota paket ini sudah penuh. Silakan pilih paket keberangkatan lain.</div>
                @else
                    <a href="{{ route('public-registration.create') }}" class="button-primary mt-5 w-full justify-center">Isi Biodata & Daftar</a>
                @endif
            </aside>
        </section>
    </main>
</body>
</html>
