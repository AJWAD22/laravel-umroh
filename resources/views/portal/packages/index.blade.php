@extends('portal.layout')
@section('title', 'Paket Umroh')
@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[.18em] text-teal-700">Pilihan Perjalanan</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Pilih Paket Umroh</h1>
            <p class="mt-2 max-w-2xl text-slate-600">Pilih paket lebih dulu. Biodata dan dokumen diisi setelah paket sesuai, sehingga pendaftaran tidak terasa panjang di awal.</p>
        </div>
        <span class="travel-chip">{{ $packages->count() }} paket tersedia</span>
    </div>

    <section class="mt-6 grid gap-3 md:grid-cols-4">
        @foreach ([
            ['1', 'Pilih paket', 'Bandingkan tanggal, hotel, maskapai, harga, dan kuota.'],
            ['2', 'Isi biodata', 'Lengkapi identitas dan dokumen secara bertahap.'],
            ['3', 'Verifikasi cabang', 'Admin Cabang memeriksa data dan dokumen.'],
            ['4', 'Pembayaran', 'Pembayaran dicatat melalui kantor cabang travel.'],
        ] as $step)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="grid size-8 place-items-center rounded-xl bg-teal-50 text-sm font-extrabold text-teal-700">{{ $step[0] }}</span>
                <h2 class="mt-3 text-sm font-extrabold">{{ $step[1] }}</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $step[2] }}</p>
            </article>
        @endforeach
    </section>

    <div class="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($packages as $package)
            <article class="group flex h-full flex-col overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div class="h-2 bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-600"></div>
                <div class="flex flex-1 flex-col p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[.14em] text-teal-700">{{ $package->branch?->name }}</p>
                            <h2 class="mt-2 text-xl font-extrabold">{{ $package->program_name }}</h2>
                        </div>
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">{{ $package->remaining_quota === null ? 'Kuota fleksibel' : $package->remaining_quota.' kursi' }}</span>
                    </div>

                    <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $package->description ?: 'Detail paket sedang dilengkapi oleh cabang.' }}</p>

                    <dl class="mt-5 grid gap-3 text-sm text-slate-600">
                        <div class="flex gap-2"><i data-lucide="calendar-days" class="size-4 shrink-0 text-teal-700"></i>{{ $package->departure_date->translatedFormat('d M Y') }} - {{ $package->duration_days }} hari</div>
                        <div class="flex gap-2"><i data-lucide="plane" class="size-4 shrink-0 text-teal-700"></i>{{ $package->airline ?: 'Maskapai menyusul' }} {{ $package->flight_number }}</div>
                        <div class="flex gap-2"><i data-lucide="hotel" class="size-4 shrink-0 text-teal-700"></i>{{ $package->hotels->pluck('name')->take(2)->join(' & ') ?: 'Hotel menyusul' }}</div>
                        <div class="flex gap-2"><i data-lucide="map-pin" class="size-4 shrink-0 text-teal-700"></i>Berangkat dari {{ $package->departure_airport ?: 'kota menyusul' }}</div>
                    </dl>

                    <div class="mt-auto border-t border-slate-100 pt-5">
                        <p class="text-xs text-slate-500">Harga paket</p>
                        <p class="mt-1 text-2xl font-extrabold">{{ $package->price ? 'Rp '.number_format($package->price, 0, ',', '.') : 'Hubungi cabang' }}</p>
                        <a href="{{ route('portal.packages.show', $package) }}" class="button-primary mt-4 w-full">Lihat Detail Paket</a>
                    </div>
                </div>
            </article>
        @empty
            <div class="travel-panel p-10 text-center md:col-span-2 xl:col-span-3">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-teal-50 text-teal-700"><i data-lucide="plane" class="size-5"></i></span>
                <h2 class="mt-4 font-extrabold">Belum ada paket tersedia</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Paket akan tampil setelah Admin Cabang mempublikasikan paket dengan status Terjadwal.</p>
            </div>
        @endforelse
    </div>
@endsection
