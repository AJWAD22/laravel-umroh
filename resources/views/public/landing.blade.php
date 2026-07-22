<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Informasi paket dan registrasi perjalanan umroh {{ $travel['name'] }}.">
    <title>{{ $travel['name'] }} - Paket dan Registrasi Umroh</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
    @php
        $whatsapp = preg_replace('/\D+/', '', (string) $travel['whatsapp']);
        $featured = $packages->first();
    @endphp

    <header class="relative isolate overflow-hidden bg-[#061321] text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_78%_18%,rgba(20,184,166,.3),transparent_29%),radial-gradient(circle_at_15%_75%,rgba(37,99,235,.22),transparent_28%)]"></div>
        <div class="absolute inset-0 opacity-[.07]" style="background-image:linear-gradient(rgba(255,255,255,.7) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.7) 1px,transparent 1px);background-size:54px 54px"></div>

        <nav class="relative mx-auto flex max-w-7xl items-center justify-between px-5 py-5 lg:px-8">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Logo {{ $travel['name'] }}" class="size-11 rounded-xl object-contain ring-1 ring-white/15">
                <div><p class="font-extrabold leading-tight">{{ $travel['name'] }}</p><p class="text-[11px] text-teal-100/70">Umrah Travel & Monitoring</p></div>
            </a>
            <div class="hidden items-center gap-7 text-sm font-semibold text-slate-200 lg:flex">
                <a href="#paket" class="transition hover:text-teal-300">Paket</a>
                <a href="#layanan" class="transition hover:text-teal-300">Layanan</a>
                <a href="#tentang" class="transition hover:text-teal-300">Tentang Travel</a>
                <a href="#kontak" class="transition hover:text-teal-300">Kontak</a>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}" class="hidden rounded-full border border-white/20 px-4 py-2 text-sm font-bold text-white/90 transition hover:bg-white/10 sm:inline-flex">Portal Admin</a>
                <a href="{{ route('public-registration.create') }}" class="rounded-full bg-teal-400 px-4 py-2.5 text-sm font-extrabold text-slate-950 shadow-lg shadow-teal-950/20 transition hover:bg-teal-300">Daftar Umroh</a>
            </div>
        </nav>

        <div class="relative mx-auto grid min-h-[650px] max-w-7xl gap-12 px-5 pb-20 pt-14 lg:grid-cols-[1.08fr_.92fr] lg:items-center lg:px-8 lg:pt-10">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-teal-300/20 bg-teal-300/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[.16em] text-teal-200">
                    <span class="size-2 rounded-full bg-teal-300"></span> Informasi & registrasi resmi travel
                </div>
                <h1 class="mt-6 max-w-3xl text-4xl font-extrabold leading-[1.08] tracking-tight sm:text-6xl">Ibadah lebih tenang dengan perjalanan yang <span class="text-teal-300">terencana dan terpantau.</span></h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">{{ $travel['tagline'] }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('public-registration.create') }}" class="button-primary justify-center bg-teal-400 px-6 py-3.5 text-slate-950 shadow-xl shadow-teal-950/30 hover:bg-teal-300">Mulai Registrasi <i data-lucide="arrow-right" class="size-4"></i></a>
                    <a href="#paket" class="button-secondary justify-center border-white/20 bg-white/10 px-6 py-3.5 text-white hover:bg-white/15">Lihat Paket Perjalanan</a>
                </div>
                <div class="mt-10 grid max-w-xl grid-cols-3 gap-3 border-t border-white/10 pt-6">
                    <div><p class="text-2xl font-extrabold">{{ $packages->count() }}</p><p class="mt-1 text-xs text-slate-400">Paket tersedia</p></div>
                    <div><p class="text-2xl font-extrabold">{{ $packages->pluck('branch_id')->unique()->count() }}</p><p class="mt-1 text-xs text-slate-400">Cabang keberangkatan</p></div>
                    <div><p class="text-2xl font-extrabold">24/7</p><p class="mt-1 text-xs text-slate-400">Monitoring perjalanan</p></div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-8 rounded-full bg-teal-400/10 blur-3xl"></div>
                <div class="relative overflow-hidden rounded-[2rem] border border-white/15 bg-white/10 p-3 shadow-2xl backdrop-blur-xl">
                    <div class="rounded-[1.45rem] bg-white p-5 text-slate-950 sm:p-6">
                        @if ($featured)
                            <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-teal-700">Keberangkatan terdekat</p><h2 class="mt-2 text-2xl font-extrabold">{{ $featured->program_name }}</h2></div><span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">{{ $featured->duration_days }} hari</span></div>
                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl bg-slate-50 p-4"><i data-lucide="calendar-days" class="size-5 text-teal-700"></i><p class="mt-3 text-xs text-slate-500">Keberangkatan</p><p class="mt-1 font-bold">{{ $featured->departure_date->translatedFormat('d M Y') }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-4"><i data-lucide="plane" class="size-5 text-teal-700"></i><p class="mt-3 text-xs text-slate-500">Maskapai</p><p class="mt-1 font-bold">{{ $featured->airline ?: 'Segera diumumkan' }}</p></div>
                                <div class="rounded-2xl bg-slate-50 p-4 sm:col-span-2"><i data-lucide="hotel" class="size-5 text-teal-700"></i><p class="mt-3 text-xs text-slate-500">Akomodasi</p><p class="mt-1 font-bold">{{ $featured->hotels->pluck('name')->take(2)->join(' & ') ?: 'Hotel paket sedang disiapkan' }}</p></div>
                            </div>
                            <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-5"><div><p class="text-xs text-slate-500">Mulai dari</p><p class="text-xl font-extrabold">{{ $featured->price ? 'Rp '.number_format($featured->price, 0, ',', '.') : 'Hubungi kami' }}</p></div><a href="{{ route('packages.show', $featured) }}" class="button-primary">Detail Paket</a></div>
                        @else
                            <div class="grid min-h-80 place-items-center text-center"><div><i data-lucide="calendar-search" class="mx-auto size-12 text-teal-600"></i><h2 class="mt-4 text-xl font-extrabold">Jadwal sedang disiapkan</h2><p class="mt-2 text-sm text-slate-500">Hubungi tim kami untuk informasi keberangkatan berikutnya.</p></div></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section id="paket" class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
            <div class="mx-auto max-w-2xl text-center"><p class="text-sm font-bold uppercase tracking-[.18em] text-teal-700">Pilihan keberangkatan</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Paket Umroh yang Transparan</h2><p class="mt-4 leading-7 text-slate-600">Bandingkan jadwal, durasi, maskapai, hotel, harga, dan sisa kuota sebelum melakukan registrasi.</p></div>
            <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($packages as $package)
                    @include('public.partials.package-card', ['package' => $package])
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 p-10 text-center text-slate-600 md:col-span-2 xl:col-span-3">Belum ada paket yang dibuka untuk publik.</div>
                @endforelse
            </div>
        </section>

        <section id="layanan" class="bg-slate-50 py-20">
            <div class="mx-auto max-w-7xl px-5 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-[.82fr_1.18fr] lg:items-center">
                    <div><p class="text-sm font-bold uppercase tracking-[.18em] text-teal-700">Pendampingan perjalanan</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Satu sistem dari pendaftaran hingga kepulangan.</h2><p class="mt-5 leading-7 text-slate-600">Jamaah mendapatkan informasi perjalanan yang jelas, sementara petugas dibantu dengan monitoring rombongan dan penanganan keadaan darurat.</p></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['icon' => 'map-pinned', 'title' => 'Monitoring Rombongan', 'text' => 'Posisi terbaru jamaah dapat dipantau oleh petugas yang bertanggung jawab.'],
                            ['icon' => 'shield-alert', 'title' => 'SOS & Geofence', 'text' => 'Peringatan darurat dan zona aman membantu petugas merespons lebih cepat.'],
                            ['icon' => 'calendar-check-2', 'title' => 'Jadwal Terstruktur', 'text' => 'Paket, hotel, penerbangan, dan agenda harian tersedia dalam satu alur.'],
                            ['icon' => 'headphones', 'title' => 'Pendampingan Petugas', 'text' => 'Tour Leader dan Muthawwif terhubung langsung dengan jamaah dalam rombongan.'],
                        ] as $service)
                            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"><span class="grid size-11 place-items-center rounded-2xl bg-teal-50 text-teal-700"><i data-lucide="{{ $service['icon'] }}" class="size-5"></i></span><h3 class="mt-5 text-lg font-extrabold">{{ $service['title'] }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $service['text'] }}</p></article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
            <div class="mx-auto max-w-2xl text-center"><p class="text-sm font-bold uppercase tracking-[.18em] text-teal-700">Cara mendaftar</p><h2 class="mt-3 text-3xl font-extrabold">Registrasi mudah dalam tiga langkah</h2></div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['01', 'Isi Biodata', 'Lengkapi identitas dan kontak jamaah melalui formulir yang aman.'],
                    ['02', 'Pilih Paket', 'Bandingkan paket yang tersedia lalu pilih keberangkatan yang sesuai.'],
                    ['03', 'Konfirmasi Admin', 'Admin cabang menghubungi jamaah untuk dokumen, pembayaran, dan proses berikutnya.'],
                ] as $step)
                    <article class="relative overflow-hidden rounded-3xl bg-[#071827] p-7 text-white"><span class="text-5xl font-extrabold text-teal-300/20">{{ $step[0] }}</span><h3 class="mt-5 text-xl font-extrabold">{{ $step[1] }}</h3><p class="mt-3 text-sm leading-6 text-slate-300">{{ $step[2] }}</p></article>
                @endforeach
            </div>
        </section>

        <section id="tentang" class="bg-[#071827] py-20 text-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 lg:grid-cols-[1.12fr_.88fr] lg:items-start lg:px-8">
                <div><p class="text-sm font-bold uppercase tracking-[.18em] text-teal-300">Tentang travel</p><h2 class="mt-3 text-3xl font-extrabold sm:text-4xl">{{ $travel['name'] }}</h2><p class="mt-5 max-w-3xl text-lg leading-8 text-slate-300">{{ $travel['about'] }}</p>
                    @if ($travel['license'])<div class="mt-6 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-4 py-2 text-sm font-bold text-emerald-200"><i data-lucide="badge-check" class="size-4"></i>Izin PPIU: {{ $travel['license'] }}</div>@endif
                </div>
                <div id="kontak" class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <h3 class="text-xl font-extrabold">Informasi & Konsultasi</h3>
                    <dl class="mt-5 grid gap-4 text-sm">
                        @if ($travel['address'])<div class="flex gap-3"><i data-lucide="map-pin" class="mt-0.5 size-5 shrink-0 text-teal-300"></i><div><dt class="text-slate-400">Kantor pusat</dt><dd class="mt-1 leading-6">{{ $travel['address'] }}</dd></div></div>@endif
                        @if ($travel['phone'])<div class="flex gap-3"><i data-lucide="phone" class="mt-0.5 size-5 shrink-0 text-teal-300"></i><div><dt class="text-slate-400">Telepon</dt><dd class="mt-1"><a href="tel:{{ $travel['phone'] }}" class="font-bold hover:text-teal-300">{{ $travel['phone'] }}</a></dd></div></div>@endif
                        @if ($travel['email'])<div class="flex gap-3"><i data-lucide="mail" class="mt-0.5 size-5 shrink-0 text-teal-300"></i><div><dt class="text-slate-400">Email</dt><dd class="mt-1"><a href="mailto:{{ $travel['email'] }}" class="font-bold hover:text-teal-300">{{ $travel['email'] }}</a></dd></div></div>@endif
                        @if ($travel['office_hours'])<div class="flex gap-3"><i data-lucide="clock-3" class="mt-0.5 size-5 shrink-0 text-teal-300"></i><div><dt class="text-slate-400">Jam layanan</dt><dd class="mt-1 font-bold">{{ $travel['office_hours'] }}</dd></div></div>@endif
                    </dl>
                    @if ($whatsapp)<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-400 px-5 py-3 font-extrabold text-emerald-950 hover:bg-emerald-300"><i data-lucide="message-circle" class="size-5"></i>Konsultasi via WhatsApp</a>@endif
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-5xl px-5 py-20 text-center"><p class="text-sm font-bold uppercase tracking-[.18em] text-teal-700">Siap merencanakan perjalanan?</p><h2 class="mt-3 text-3xl font-extrabold sm:text-4xl">Mulai dari biodata, kemudian pilih paket terbaik Anda.</h2><p class="mx-auto mt-4 max-w-2xl leading-7 text-slate-600">Registrasi awal tidak langsung menjadi transaksi. Tim cabang akan menghubungi Anda untuk verifikasi dan penjelasan lanjutan.</p><a href="{{ route('public-registration.create') }}" class="button-primary mt-7 inline-flex px-7 py-3.5">Mulai Registrasi <i data-lucide="arrow-right" class="size-4"></i></a></section>
    </main>

    <footer class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between lg:px-8"><div class="flex items-center gap-3"><img src="{{ asset('images/mantau-umroh-icon-light.png') }}" alt="" class="size-9 rounded-lg"><div><p class="font-bold text-slate-800">{{ $travel['name'] }}</p><p>Sistem informasi dan monitoring jamaah umroh</p></div></div><p>© {{ now()->year }} {{ $travel['name'] }}. Semua hak dilindungi.</p></div>
    </footer>
</body>
</html>
