<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilih Paket Umroh - {{ config('app.name', 'Mantau Umroh') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900">
    <header class="bg-slate-950 text-white">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Mantau Umroh" class="size-11 rounded-xl object-contain ring-1 ring-white/15">
                <span class="text-lg font-extrabold">Mantau Umroh</span>
            </a>
            <a href="{{ route('public-registration.create') }}" class="rounded-full border border-white/25 px-4 py-2 text-sm font-bold">Ubah Biodata</a>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:py-14">
        <div class="mx-auto mb-8 grid max-w-3xl grid-cols-2 gap-3 text-sm font-bold">
            <div class="rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-teal-700"><span class="mr-2">✓</span> Biodata Selesai</div>
            <div class="rounded-2xl bg-teal-600 px-4 py-3 text-white"><span class="mr-2">2</span> Pilih Paket</div>
        </div>

        <div class="mb-8 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-700">Langkah terakhir</p>
                <h1 class="mt-2 text-3xl font-extrabold">Pilih Paket Perjalanan</h1>
                <p class="mt-2 text-slate-600">Biodata atas nama <strong>{{ $biodata['full_name'] }}</strong> sudah tersimpan sementara.</p>
            </div>
            <a href="{{ route('public-registration.create') }}" class="button-secondary">Periksa Biodata</a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($packages as $package)
                <article class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{{ $package->branch?->name }}</p><h2 class="mt-2 text-xl font-extrabold">{{ $package->program_name }}</h2></div>
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-extrabold text-teal-700">{{ $package->duration_days }} hari</span>
                    </div>
                    <dl class="mt-5 grid gap-3 text-sm text-slate-600">
                        <div class="flex gap-2"><i data-lucide="calendar-days" class="size-4 shrink-0 text-teal-700"></i>{{ $package->departure_date->translatedFormat('d M Y') }} – {{ $package->return_date->translatedFormat('d M Y') }}</div>
                        <div class="flex gap-2"><i data-lucide="plane" class="size-4 shrink-0 text-teal-700"></i>{{ $package->airline ?: 'Maskapai menyusul' }} {{ $package->flight_number }}</div>
                        <div class="flex gap-2"><i data-lucide="hotel" class="size-4 shrink-0 text-teal-700"></i>{{ $package->hotels->pluck('name')->take(2)->implode(', ') ?: 'Hotel menyusul' }}</div>
                        <div class="flex gap-2"><i data-lucide="users" class="size-4 shrink-0 text-teal-700"></i>{{ $package->remaining_quota === null ? 'Kuota fleksibel' : $package->remaining_quota.' kursi tersisa' }}</div>
                    </dl>
                    <div class="mt-auto border-t border-slate-100 pt-5">
                        <p class="mb-4 text-xl font-extrabold">{{ $package->price ? 'Rp '.number_format($package->price, 0, ',', '.') : 'Hubungi admin' }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('packages.show', $package) }}" target="_blank" class="button-secondary justify-center">Lihat Detail</a>
                            <form method="POST" action="{{ route('public-registration.complete') }}"
                                  data-confirm-title="Pilih Paket Umroh"
                                  data-confirm="Daftarkan {{ $biodata['full_name'] }} pada paket {{ $package->program_name }}?">
                                @csrf
                                <input type="hidden" name="departure_id" value="{{ $package->id }}">
                                <button @disabled($package->remaining_quota === 0) class="button-primary w-full justify-center disabled:cursor-not-allowed disabled:opacity-50">{{ $package->remaining_quota === 0 ? 'Kuota Penuh' : 'Pilih Paket' }}</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-slate-600">Belum ada paket keberangkatan yang tersedia.</div>
            @endforelse
        </div>
    </main>
</body>
</html>
