<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Detail paket umroh {{ $package->program_name }} dari {{ $travel['name'] }}.">
    <title>{{ $package->program_name }} - {{ $travel['name'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
@php
    $facilities = collect(preg_split('/\r\n|\r|\n/', (string) $package->facilities))->map(fn ($item) => trim($item))->filter()->values();
    $requirements = collect(preg_split('/\r\n|\r|\n/', (string) $package->requirements))->map(fn ($item) => trim($item))->filter()->values();
@endphp
<header class="bg-[#071827] text-white">
    <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-5 lg:px-8">
        <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3">
            <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Logo {{ $travel['name'] }}" class="size-11 rounded-2xl">
            <div class="min-w-0">
                <p class="truncate font-extrabold">{{ $travel['name'] }}</p>
                <p class="text-xs text-slate-400">Detail Paket Umroh</p>
            </div>
        </a>
        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ route('login') }}" class="hidden min-h-11 items-center justify-center rounded-2xl border border-white/20 px-4 text-sm font-bold sm:inline-flex">Masuk</a>
            <a href="{{ route('portal.register', ['paket' => $package->id]) }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-teal-500 px-4 text-sm font-extrabold text-white hover:bg-teal-400">Daftar Sekarang</a>
        </div>
    </nav>
    <section class="mx-auto grid max-w-7xl gap-8 px-5 pb-14 pt-8 lg:grid-cols-[1fr_360px] lg:px-8">
        <div>
            <a href="{{ route('landing') }}#paket" class="text-sm font-bold text-teal-200">Kembali ke paket</a>
            <p class="mt-8 text-sm font-extrabold uppercase tracking-[0.16em] text-teal-300">{{ $package->branch?->name }}</p>
            <h1 class="mt-3 text-4xl font-extrabold leading-tight sm:text-6xl">{{ $package->program_name }}</h1>
            <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-300">{{ $package->description ?: 'Detail paket sedang dilengkapi oleh cabang.' }}</p>
        </div>
        <aside class="h-fit rounded-[1.15rem] border border-white/10 bg-white/10 p-6">
            <p class="text-sm text-slate-300">Harga paket</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $package->price ? 'Rp '.number_format($package->price, 0, ',', '.') : 'Hubungi cabang' }}</p>
            <dl class="mt-5 grid gap-3 text-sm text-slate-200">
                <div class="flex gap-2"><i data-lucide="calendar-days" class="size-4 text-teal-300"></i>{{ $package->departure_date?->translatedFormat('d M Y') }} - {{ $package->return_date?->translatedFormat('d M Y') }}</div>
                <div class="flex gap-2"><i data-lucide="clock" class="size-4 text-teal-300"></i>{{ $package->duration_days }} hari</div>
                <div class="flex gap-2"><i data-lucide="users" class="size-4 text-teal-300"></i>{{ $package->remaining_quota === null ? 'Kuota tersedia' : $package->remaining_quota.' kursi tersisa' }}</div>
            </dl>
        </aside>
    </section>
</header>

<main class="mx-auto max-w-7xl px-5 py-10 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <section class="travel-panel p-6">
                <h2 class="text-xl font-extrabold">Informasi Perjalanan</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <span class="travel-chip"><i data-lucide="plane" class="size-4"></i>{{ $package->airline ?: 'Maskapai menyusul' }} {{ $package->flight_number }}</span>
                    <span class="travel-chip"><i data-lucide="map-pin" class="size-4"></i>Berangkat dari {{ $package->departure_airport ?: 'kota menyusul' }}</span>
                    <span class="travel-chip"><i data-lucide="map-pinned" class="size-4"></i>Tiba di {{ $package->arrival_airport ?: 'bandara menyusul' }}</span>
                    <span class="travel-chip"><i data-lucide="building-2" class="size-4"></i>{{ $package->branch?->city ?: 'Cabang travel' }}</span>
                </div>
            </section>

            <section class="travel-panel p-6">
                <h2 class="text-xl font-extrabold">Hotel Makkah dan Madinah</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @forelse ($package->hotels as $hotel)
                        <article class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs font-bold uppercase tracking-[.14em] text-teal-700">{{ $hotel->city ?: 'Kota hotel' }}</p>
                            <h3 class="mt-2 font-extrabold">{{ $hotel->name }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $hotel->address ?: 'Alamat hotel menyusul.' }}</p>
                        </article>
                    @empty
                        <p class="text-slate-500">Informasi hotel sedang disiapkan.</p>
                    @endforelse
                </div>
            </section>

            <section class="travel-panel p-6">
                <h2 class="text-xl font-extrabold">Jadwal Perjalanan</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($package->itineraries as $item)
                        <article class="grid gap-3 rounded-2xl border border-slate-200 p-4 sm:grid-cols-[80px_1fr]">
                            <strong class="text-sm text-teal-700">Hari {{ $item->day_number }}</strong>
                            <div>
                                <h3 class="font-extrabold">{{ $item->title }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $item->city }}</p>
                                <p class="mt-2 leading-6 text-slate-600">{{ $item->description }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="text-slate-500">Jadwal perjalanan sedang disiapkan.</p>
                    @endforelse
                </div>
            </section>

            <section class="travel-panel p-6">
                <h2 class="text-xl font-extrabold">Fasilitas dan Persyaratan</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <h3 class="font-extrabold">Fasilitas</h3>
                        <div class="mt-3 grid gap-3 text-sm leading-7 text-slate-600">
                            @forelse ($facilities as $item)
                                <p class="flex gap-2"><i data-lucide="check-circle-2" class="mt-1 size-4 shrink-0 text-teal-700"></i><span>{{ $item }}</span></p>
                            @empty
                                <p>Fasilitas mengikuti ketentuan paket yang diterbitkan cabang.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h3 class="font-extrabold">Persyaratan</h3>
                        <div class="mt-3 grid gap-3 text-sm leading-7 text-slate-600">
                            @forelse ($requirements as $item)
                                <p class="flex gap-2"><i data-lucide="file-check-2" class="mt-1 size-4 shrink-0 text-teal-700"></i><span>{{ $item }}</span></p>
                            @empty
                                <p>Dokumen utama disiapkan setelah akun dibuat dan biodata diisi.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="travel-panel h-fit p-6">
            <p class="text-sm font-bold uppercase tracking-[.15em] text-teal-700">Mulai Pendaftaran</p>
            <h2 class="mt-2 text-2xl font-extrabold">Buat akun untuk memilih paket ini.</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">Form akun hanya meminta nama, WhatsApp, email, dan password. Biodata lengkap diisi setelah paket dipilih.</p>
            @if ($package->remaining_quota === 0)
                <div class="mt-5 rounded-xl bg-amber-50 p-4 text-sm font-bold text-amber-800">Kuota paket sudah penuh.</div>
            @else
                <a href="{{ route('portal.register', ['paket' => $package->id]) }}" class="button-primary mt-5 w-full">Daftar untuk Paket Ini</a>
                <a href="{{ route('login') }}" class="button-secondary mt-3 w-full">Sudah Punya Akun</a>
            @endif
            <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                <p class="font-extrabold text-slate-800">{{ $package->branch?->name }}</p>
                <p>{{ $package->branch?->address ?: 'Alamat cabang menyusul.' }}</p>
            </div>
        </aside>
    </div>
</main>
</body>
</html>
