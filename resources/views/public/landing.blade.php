<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Paket umroh, legalitas travel, dan pendaftaran jamaah {{ $travel['name'] }}.">
    <title>{{ $travel['name'] }} - Travel Umroh Terpantau</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
@php
    $whatsapp = preg_replace('/\D+/', '', (string) $travel['whatsapp']);
    $whatsappUrl = $whatsapp ? 'https://wa.me/'.$whatsapp : '#kontak';
    $legalBadge = $travel['license'] ? 'PPIU '.$travel['license'] : 'Legalitas dapat dikonfirmasi';
@endphp
<body class="bg-white font-sans text-slate-900 antialiased">
<header
    id="beranda"
    class="relative isolate min-h-screen overflow-hidden bg-[#061521] text-white"
    x-data="{ scrolled: false }"
    x-init="scrolled = window.scrollY > 24; window.addEventListener('scroll', () => scrolled = window.scrollY > 24)"
>
    <img
        src="https://images.unsplash.com/photo-1564769625905-50e93615e769?auto=format&fit=crop&w=1800&q=82"
        alt="Suasana Masjidil Haram"
        class="absolute inset-0 h-full w-full object-cover"
    >
    <div class="absolute inset-0 bg-[#061521]/70"></div>
    <div class="absolute inset-x-0 bottom-0 h-44 bg-gradient-to-t from-white to-transparent"></div>

    <nav
        class="fixed inset-x-0 top-0 z-50 transition duration-300"
        :class="scrolled ? 'bg-white/95 text-slate-900 shadow-sm backdrop-blur' : 'bg-transparent text-white'"
    >
        <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-5 lg:px-8">
            <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('images/mantau-umroh-icon-light.png') }}" alt="Logo {{ $travel['name'] }}" class="size-11 rounded-2xl bg-white p-1.5 shadow-sm">
                <div class="min-w-0">
                    <p class="truncate text-base font-extrabold sm:text-lg">{{ $travel['name'] }}</p>
                    <p class="hidden text-xs font-semibold opacity-75 sm:block">Travel Umroh & Portal Jamaah</p>
                </div>
            </a>

            <div class="hidden items-center gap-7 text-sm font-bold lg:flex">
                <a href="#beranda" class="hover:text-teal-500">Beranda</a>
                <a href="#paket" class="hover:text-teal-500">Paket Umroh</a>
                <a href="#tentang" class="hover:text-teal-500">Tentang Kami</a>
                <a href="#fasilitas" class="hover:text-teal-500">Fasilitas</a>
                <a href="#kontak" class="hover:text-teal-500">Kontak</a>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @if (auth()->user()?->portalAccount)
                    <a href="{{ route('portal.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-teal-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-teal-950/10">Portal Saya</a>
                @else
                    <a href="{{ route('login') }}" class="hidden min-h-11 items-center justify-center rounded-2xl border border-current/20 px-4 text-sm font-bold sm:inline-flex">Masuk</a>
                    <a href="{{ route('portal.register') }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-teal-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-teal-950/10 hover:bg-teal-400">Daftar Sekarang</a>
                @endif
            </div>
        </div>
    </nav>

    <div class="relative mx-auto flex min-h-screen max-w-7xl items-end px-5 pb-24 pt-32 lg:px-8">
        <div class="max-w-4xl">
            <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.16em] text-teal-100 backdrop-blur">
                {{ $legalBadge }} - Pendamping berpengalaman
            </p>
            <h1 class="mt-6 max-w-4xl text-4xl font-extrabold leading-tight sm:text-6xl lg:text-7xl">
                Perjalanan Ibadah yang Aman, Nyaman, dan Terpantau
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-100 sm:text-xl">
                Temukan paket umroh terbaik dengan jadwal, hotel, penerbangan, dan pendamping perjalanan yang jelas.
            </p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="#paket" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-teal-500 px-7 text-base font-extrabold text-white shadow-xl shadow-black/20 hover:bg-teal-400">
                    Lihat Paket Umroh
                </a>
                <a href="{{ $whatsappUrl }}" target="{{ $whatsapp ? '_blank' : '_self' }}" rel="noopener" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-white/25 bg-white/10 px-7 text-base font-extrabold text-white backdrop-blur hover:bg-white/15">
                    Konsultasi via WhatsApp
                </a>
            </div>
            <div class="mt-10 grid max-w-4xl gap-3 text-sm font-semibold text-slate-100 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([$legalBadge, 'Pendamping berpengalaman', 'Monitoring jamaah selama perjalanan', 'Pembayaran melalui kantor cabang'] as $item)
                    <div class="flex items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                        <i data-lucide="check-circle-2" class="size-4 shrink-0 text-teal-300"></i>
                        <span>{{ $item }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</header>

<main>
    <section id="fasilitas" class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-teal-700">Keunggulan Travel</p>
            <h2 class="mt-3 text-3xl font-extrabold text-[#071827] sm:text-4xl">Informasi jelas sejak memilih paket sampai berangkat.</h2>
        </div>
        <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
            @foreach ([['badge-check','Travel resmi dan terpercaya'],['hotel','Hotel dan penerbangan transparan'],['users','Pendamping perjalanan profesional'],['heart-handshake','Sistem monitoring dan bantuan SOS']] as $feature)
                <article class="rounded-[1.15rem] border border-slate-200 bg-white p-6 shadow-[0_16px_42px_rgba(15,23,42,0.06)]">
                    <span class="grid size-12 place-items-center rounded-2xl bg-teal-50 text-teal-700"><i data-lucide="{{ $feature[0] }}" class="size-5"></i></span>
                    <h3 class="mt-5 text-lg font-extrabold text-[#071827]">{{ $feature[1] }}</h3>
                    <p class="mt-3 leading-7 text-slate-600">Dibuat sederhana agar calon jamaah dan keluarga mudah memahami layanan utama.</p>
                </article>
            @endforeach
        </div>
    </section>

    <section id="paket" class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-teal-700">Paket Keberangkatan</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-[#071827] sm:text-4xl">Pilih paket, lalu buat akun untuk melanjutkan pendaftaran.</h2>
                </div>
                <a href="{{ route('portal.register') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-[#071827] px-6 text-sm font-extrabold text-white hover:bg-[#123047]">Buat Akun Jamaah</a>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @forelse ($packages as $package)
                    <article class="overflow-hidden rounded-[1.15rem] border border-slate-200 bg-white shadow-[0_18px_48px_rgba(15,23,42,0.07)]">
                        <img src="{{ $package['image'] }}" alt="Foto {{ $package['name'] }}" class="h-56 w-full object-cover">
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-extrabold text-teal-700">{{ $package['departure_date'] }}</p>
                                    <h3 class="mt-2 text-2xl font-extrabold text-[#071827]">{{ $package['name'] }}</h3>
                                </div>
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-extrabold text-amber-700">{{ $package['quota'] }}</span>
                            </div>
                            <dl class="mt-6 grid gap-3 text-sm text-slate-600">
                                <div class="flex gap-2"><i data-lucide="calendar-days" class="size-4 text-teal-700"></i>{{ $package['duration'] }}</div>
                                <div class="flex gap-2"><i data-lucide="hotel" class="size-4 text-teal-700"></i>{{ $package['hotel_class'] }} - {{ $package['makkah_hotel'] }}</div>
                                <div class="flex gap-2"><i data-lucide="map-pin" class="size-4 text-teal-700"></i>{{ $package['madinah_hotel'] }}</div>
                                <div class="flex gap-2"><i data-lucide="plane" class="size-4 text-teal-700"></i>{{ $package['airline'] }}</div>
                                <div class="flex gap-2"><i data-lucide="building-2" class="size-4 text-teal-700"></i>Berangkat dari {{ $package['departure_city'] }}</div>
                            </dl>
                            <div class="mt-6 border-t border-slate-100 pt-5">
                                <p class="text-sm text-slate-500">Harga mulai</p>
                                <p class="mt-1 text-2xl font-extrabold text-[#071827]">{{ $package['price'] }}</p>
                                <a href="{{ $package['url'] }}" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-2xl bg-teal-600 px-5 text-sm font-extrabold text-white hover:bg-teal-500">Lihat Detail</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="rounded-[1.15rem] border border-dashed border-slate-300 bg-white p-8 shadow-sm lg:col-span-3">
                        <div class="grid gap-6 lg:grid-cols-[1fr_0.9fr] lg:items-center">
                            <div>
                                <span class="grid size-12 place-items-center rounded-2xl bg-teal-50 text-teal-700"><i data-lucide="plane" class="size-5"></i></span>
                                <h3 class="mt-5 text-2xl font-extrabold text-[#071827]">Paket keberangkatan sedang disiapkan.</h3>
                                <p class="mt-3 max-w-2xl leading-7 text-slate-600">Jadwal paket terbaru belum dibuka untuk pendaftaran online. Tim kami tetap dapat membantu konsultasi jadwal, estimasi biaya, dan persyaratan keberangkatan melalui WhatsApp.</p>
                                <a href="{{ $whatsappUrl }}" target="{{ $whatsapp ? '_blank' : '_self' }}" rel="noopener" class="mt-6 inline-flex min-h-12 items-center justify-center rounded-2xl bg-teal-600 px-6 text-sm font-extrabold text-white hover:bg-teal-500">Konsultasi Jadwal</a>
                            </div>
                            <div class="rounded-[1.15rem] bg-slate-50 p-5">
                                <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-teal-700">Informasi yang bisa ditanyakan</p>
                                <ul class="mt-4 space-y-3 text-sm font-semibold text-slate-700">
                                    <li class="flex gap-3"><i data-lucide="check-circle-2" class="mt-0.5 size-4 shrink-0 text-teal-700"></i>Estimasi jadwal keberangkatan.</li>
                                    <li class="flex gap-3"><i data-lucide="check-circle-2" class="mt-0.5 size-4 shrink-0 text-teal-700"></i>Persyaratan dokumen jamaah.</li>
                                    <li class="flex gap-3"><i data-lucide="check-circle-2" class="mt-0.5 size-4 shrink-0 text-teal-700"></i>Kantor cabang pelayanan terdekat.</li>
                                </ul>
                            </div>
                        </div>
                    </article>
                @endforelse
            </div>
        </div>
    </section>

    <section id="alur" class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-teal-700">Alur Pendaftaran</p>
                <h2 class="mt-3 text-3xl font-extrabold text-[#071827] sm:text-4xl">Landing page hanya pintu masuk pendaftaran.</h2>
                <p class="mt-5 text-lg leading-8 text-slate-600">Calon jamaah membuat akun lebih dulu, lalu memilih paket dan melengkapi data di portal jamaah.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([['01','Buat akun jamaah'],['02','Pilih paket perjalanan'],['03','Lengkapi biodata dan dokumen'],['04','Lakukan pembayaran di kantor cabang']] as $step)
                    <article class="rounded-[1.15rem] bg-[#071827] p-6 text-white">
                        <p class="text-4xl font-extrabold text-amber-300/40">{{ $step[0] }}</p>
                        <h3 class="mt-5 text-lg font-extrabold">{{ $step[1] }}</h3>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#071827] py-20 text-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 lg:grid-cols-[1fr_1fr] lg:items-center lg:px-8">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-teal-300">Monitoring Jamaah</p>
                <h2 class="mt-3 text-3xl font-extrabold sm:text-4xl">Petugas lebih mudah membantu jamaah selama perjalanan.</h2>
                <p class="mt-5 text-lg leading-8 text-slate-300">Informasi perjalanan disatukan dalam aplikasi agar koordinasi rombongan tetap rapi.</p>
            </div>
            <div class="grid gap-4">
                @foreach (['Lokasi jamaah dapat dipantau petugas.', 'Titik kumpul dan tujuan perjalanan tersedia di aplikasi.', 'Tombol SOS untuk keadaan darurat.', 'Informasi jadwal, hotel, dan rombongan dalam satu aplikasi.'] as $monitoring)
                    <div class="flex gap-3 rounded-[1.15rem] border border-white/10 bg-white/5 p-5">
                        <i data-lucide="shield-check" class="mt-1 size-5 shrink-0 text-teal-300"></i>
                        <p class="leading-7 text-slate-200">{{ $monitoring }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="tentang" class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[1.05fr_.95fr] lg:items-center">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-teal-700">Tentang Travel</p>
                <h2 class="mt-3 text-3xl font-extrabold text-[#071827] sm:text-4xl">{{ $travel['name'] }}</h2>
                <p class="mt-5 text-lg leading-8 text-slate-600">
                    {{ $travel['about'] ?: 'Travel umroh yang membantu calon jamaah memilih paket, memahami alur pendaftaran, dan mendapatkan pendampingan perjalanan dengan informasi yang jelas.' }}
                </p>
                @if ($travel['tagline'])
                    <p class="mt-5 rounded-[1.15rem] border border-teal-100 bg-teal-50 p-5 font-semibold leading-7 text-teal-900">{{ $travel['tagline'] }}</p>
                @endif
                <div class="mt-7 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-[1.15rem] border border-slate-200 bg-white p-5">
                        <p class="text-sm text-slate-500">Nomor izin PPIU</p>
                        <p class="mt-2 font-extrabold text-[#071827]">{{ $travel['license'] ?: 'Konfirmasi melalui kantor' }}</p>
                    </div>
                    <div class="rounded-[1.15rem] border border-slate-200 bg-white p-5">
                        <p class="text-sm text-slate-500">Paket aktif</p>
                        <p class="mt-2 font-extrabold text-[#071827]">{{ number_format($stats['public_packages']) }} paket</p>
                    </div>
                    <div class="rounded-[1.15rem] border border-slate-200 bg-white p-5">
                        <p class="text-sm text-slate-500">Cabang aktif</p>
                        <p class="mt-2 font-extrabold text-[#071827]">{{ number_format($stats['active_branches']) }} cabang</p>
                    </div>
                </div>
            </div>
            <img
                src="https://images.unsplash.com/photo-1580418827493-f2b22c0a76cb?auto=format&fit=crop&w=1000&q=80"
                alt="Tim pendamping perjalanan umroh"
                class="aspect-[4/3] w-full rounded-[1.25rem] object-cover shadow-[0_20px_50px_rgba(15,23,42,0.12)]"
            >
        </div>
    </section>

    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr]">
                <div>
                    <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-teal-700">Layanan Jamaah</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-[#071827] sm:text-4xl">Pendampingan dibuat jelas dari awal.</h2>
                    <p class="mt-5 leading-8 text-slate-600">Calon jamaah mendapat informasi paket, cabang pelayanan, alur biodata, dan status pendaftaran secara bertahap sehingga proses tidak membingungkan.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([['clipboard-list','Pendaftaran bertahap','Akun dibuat lebih dulu, lalu biodata dan dokumen dilengkapi setelah memilih paket.'],['building-2','Verifikasi cabang','Admin cabang memeriksa biodata, dokumen, dan pembayaran secara langsung.'],['users','Rombongan jelas','Jamaah yang diterima ditempatkan ke rombongan sesuai paket dan cabang.'],['shield-check','Bantuan perjalanan','Aplikasi membantu jadwal, titik kumpul, monitoring, dan SOS saat perjalanan.']] as $service)
                        <article class="rounded-[1.15rem] border border-slate-200 bg-white p-5 shadow-sm">
                            <span class="grid size-11 place-items-center rounded-2xl bg-teal-50 text-teal-700"><i data-lucide="{{ $service[0] }}" class="size-5"></i></span>
                            <h3 class="mt-4 font-extrabold text-[#071827]">{{ $service[1] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $service[2] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-5 py-20 lg:px-8">
        <div class="text-center">
            <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-teal-700">FAQ</p>
            <h2 class="mt-3 text-3xl font-extrabold text-[#071827] sm:text-4xl">Pertanyaan yang sering diajukan</h2>
        </div>
        <div class="mt-10 divide-y divide-slate-200 rounded-[1.15rem] border border-slate-200 bg-white">
            @foreach ([
                ['Bagaimana cara mendaftar?', 'Klik Daftar Sekarang, buat akun jamaah, pilih paket, lalu lengkapi biodata dan dokumen di portal.'],
                ['Dokumen apa yang diperlukan?', 'Umumnya KTP, KK, paspor, foto, dan dokumen pendukung sesuai ketentuan paket. Petugas cabang akan memverifikasi kembali.'],
                ['Apakah pembayaran dilakukan secara daring?', 'Alur sistem mengarahkan pembayaran melalui kantor cabang agar calon jamaah mendapatkan verifikasi langsung.'],
                ['Bagaimana memilih kantor cabang?', 'Pilih cabang yang paling mudah dijangkau ketika membuat akun atau saat menghubungi admin travel.'],
                ['Apakah paket bisa diganti?', 'Perubahan paket mengikuti ketersediaan kuota dan persetujuan petugas cabang.'],
                ['Bagaimana menggunakan aplikasi Mantau Umroh?', 'Setelah terdaftar dalam rombongan, jamaah dapat menggunakan aplikasi untuk melihat jadwal, hotel, rombongan, dan bantuan SOS.'],
            ] as $faq)
                <details class="group p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-extrabold text-[#071827]">
                        {{ $faq[0] }}
                        <i data-lucide="chevron-down" class="size-5 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 leading-7 text-slate-600">{{ $faq[1] }}</p>
                </details>
            @endforeach
        </div>
    </section>

    <section id="kontak" class="bg-[#071827] py-20 text-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 lg:grid-cols-[1fr_1fr] lg:px-8">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-[0.16em] text-teal-300">Kontak</p>
                <h2 class="mt-3 text-3xl font-extrabold sm:text-4xl">Konsultasikan rencana keberangkatan di kantor cabang.</h2>
                <div class="mt-7 space-y-4 leading-7 text-slate-300">
                    @if ($travel['address'])<p><strong class="block text-white">Kantor pusat</strong>{{ $travel['address'] }}</p>@endif
                    @if ($travel['phone'])<p><strong class="block text-white">Telepon</strong>{{ $travel['phone'] }}</p>@endif
                    @if ($travel['email'])<p><strong class="block text-white">Email</strong>{{ $travel['email'] }}</p>@endif
                    @if ($travel['office_hours'])<p><strong class="block text-white">Jam pelayanan</strong>{{ $travel['office_hours'] }}</p>@endif
                </div>
                <a href="{{ $whatsappUrl }}" target="{{ $whatsapp ? '_blank' : '_self' }}" rel="noopener" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-2xl bg-teal-500 px-7 font-extrabold text-white hover:bg-teal-400">Hubungi WhatsApp</a>
            </div>
            <div class="rounded-[1.15rem] border border-white/10 bg-white/5 p-6">
                <h3 class="text-xl font-extrabold">Daftar Cabang</h3>
                <div class="mt-5 grid gap-4">
                    @forelse ($branches as $branch)
                        @php
                            $branchPhone = preg_replace('/\D+/', '', (string) $branch->phone);
                            if (str_starts_with($branchPhone, '0')) {
                                $branchPhone = '62'.substr($branchPhone, 1);
                            } elseif (str_starts_with($branchPhone, '8')) {
                                $branchPhone = '62'.$branchPhone;
                            }
                        @endphp
                        <div class="rounded-2xl bg-white/[0.08] p-4">
                            <p class="font-extrabold">{{ $branch->name }}</p>
                            <p class="mt-1 text-sm text-slate-300">{{ $branch->city ?: $branch->address }}</p>
                            @if ($branch->phone)
                                <a href="https://wa.me/{{ $branchPhone }}" target="_blank" rel="noopener" class="mt-2 inline-flex text-sm font-bold text-teal-200 hover:text-teal-100">WhatsApp {{ $branch->phone }}</a>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-2xl bg-white/[0.08] p-4 text-sm leading-7 text-slate-300">Data cabang belum tersedia. Lengkapi dari pengaturan cabang.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="bg-[#04101a] px-5 py-8 text-sm text-slate-300">
    <div class="mx-auto flex max-w-7xl flex-col gap-4 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <p>&copy; {{ now()->year }} {{ $travel['name'] }}. Travel umroh dan portal jamaah.</p>
        <div class="flex flex-wrap gap-4">
            <a href="#" class="hover:text-white">Kebijakan Privasi</a>
            <a href="#" class="hover:text-white">Syarat dan Ketentuan</a>
            @if ($travel['website'])<a href="{{ $travel['website'] }}" class="hover:text-white">Website Travel</a>@endif
        </div>
    </div>
</footer>
</body>
</html>
