@extends('portal.layout')
@section('title', $package->program_name)
@section('content')
    <a href="{{ route('portal.packages.index') }}" class="text-sm font-bold text-teal-700">Kembali ke daftar paket</a>

    <section class="mt-5 overflow-hidden rounded-[1.35rem] bg-[#071827] p-6 text-white sm:p-8">
        <div class="grid gap-7 lg:grid-cols-[1fr_auto] lg:items-end">
            <div>
                <p class="text-sm font-bold uppercase tracking-[.18em] text-teal-300">{{ $package->branch?->name }}</p>
                <h1 class="mt-3 text-3xl font-extrabold sm:text-5xl">{{ $package->program_name }}</h1>
                <p class="mt-4 max-w-3xl leading-7 text-slate-300">{{ $package->description ?: 'Detail paket sedang dilengkapi oleh cabang.' }}</p>
            </div>
            <div class="rounded-2xl bg-white/10 p-5">
                <p class="text-xs text-slate-400">Harga paket</p>
                <p class="mt-1 text-2xl font-extrabold">{{ $package->price ? 'Rp '.number_format($package->price, 0, ',', '.') : 'Hubungi cabang' }}</p>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <section class="travel-panel p-6">
                <h2 class="text-xl font-extrabold">Informasi Perjalanan</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <span class="travel-chip"><i data-lucide="calendar-days" class="size-4"></i>{{ $package->departure_date->translatedFormat('d M Y') }} - {{ $package->return_date->translatedFormat('d M Y') }}</span>
                    <span class="travel-chip"><i data-lucide="clock" class="size-4"></i>{{ $package->duration_days }} hari</span>
                    <span class="travel-chip"><i data-lucide="plane" class="size-4"></i>{{ $package->airline ?: 'Maskapai menyusul' }} {{ $package->flight_number }}</span>
                    <span class="travel-chip"><i data-lucide="map-pin" class="size-4"></i>{{ $package->departure_airport ?: 'Kota keberangkatan menyusul' }}</span>
                    <span class="travel-chip"><i data-lucide="hotel" class="size-4"></i>{{ $package->hotels->pluck('name')->join(' & ') ?: 'Hotel menyusul' }}</span>
                    <span class="travel-chip"><i data-lucide="users" class="size-4"></i>{{ $package->remaining_quota === null ? 'Kuota fleksibel' : $package->remaining_quota.' kursi tersisa' }}</span>
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
                <h2 class="text-xl font-extrabold">Syarat dan Fasilitas</h2>
                @php
                    $facilities = collect(preg_split('/\r\n|\r|\n/', (string) $package->facilities))->map(fn ($item) => trim($item))->filter()->values();
                    $requirements = collect(preg_split('/\r\n|\r|\n/', (string) $package->requirements))->map(fn ($item) => trim($item))->filter()->values();
                    if ($facilities->isEmpty()) {
                        $facilities = collect(['Pendamping perjalanan sesuai rombongan.', 'Informasi hotel, jadwal, dan rombongan tersedia di portal/aplikasi.', 'Ketentuan fasilitas mengikuti paket yang dipilih.']);
                    }
                    if ($requirements->isEmpty()) {
                        $requirements = collect(['Paspor masih berlaku sesuai ketentuan.', 'KTP dan KK untuk verifikasi identitas.', 'Pembayaran dilakukan melalui kantor cabang.']);
                    }
                @endphp
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <h3 class="font-extrabold">Fasilitas</h3>
                        <div class="mt-3 grid gap-3 text-sm leading-7 text-slate-600">
                            @foreach ($facilities as $item)
                                <p class="flex gap-2"><i data-lucide="check-circle-2" class="mt-1 size-4 shrink-0 text-teal-700"></i><span>{{ $item }}</span></p>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h3 class="font-extrabold">Persyaratan</h3>
                        <div class="mt-3 grid gap-3 text-sm leading-7 text-slate-600">
                            @foreach ($requirements as $item)
                                <p class="flex gap-2"><i data-lucide="file-check-2" class="mt-1 size-4 shrink-0 text-teal-700"></i><span>{{ $item }}</span></p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="travel-panel h-fit p-6">
            <p class="text-sm font-bold uppercase tracking-[.15em] text-teal-700">Lanjutkan Pendaftaran</p>
            <h2 class="mt-2 text-2xl font-extrabold">Paket ini sesuai?</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">Setelah memilih, sistem membuat draft pendaftaran. Lengkapi biodata dan dokumen secara bertahap sebelum dikirim ke admin cabang.</p>
            <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                <p class="font-extrabold text-slate-800">{{ $package->branch?->name }}</p>
                <p>{{ $package->branch?->address ?: 'Alamat cabang menyusul.' }}</p>
            </div>
            @if ($package->remaining_quota === 0)
                <div class="mt-5 rounded-xl bg-amber-50 p-4 text-sm font-bold text-amber-800">Kuota paket sudah penuh.</div>
            @else
                <form method="POST" action="{{ route('portal.packages.select', $package) }}" class="mt-5">
                    @csrf
                    <button class="button-primary w-full">Pilih Paket Ini</button>
                </form>
            @endif
        </aside>
    </div>
@endsection
