@extends('portal.layout')
@section('title', 'Beranda Jamaah')
@section('content')
    <section class="overflow-hidden rounded-[2rem] bg-[#071827] p-6 text-white shadow-xl sm:p-8">
        <div class="grid gap-7 lg:grid-cols-[1fr_auto] lg:items-center"><div><p class="text-sm font-bold uppercase tracking-[.18em] text-teal-300">Assalamu’alaikum</p><h1 class="mt-3 text-3xl font-extrabold sm:text-4xl">{{ auth()->user()->name }}</h1><p class="mt-3 max-w-2xl leading-7 text-slate-300">Kelola paket pilihan, biodata, status verifikasi, dan informasi pembayaran Anda dari satu portal.</p></div><a href="{{ route('portal.packages.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-teal-400 px-6 font-extrabold text-slate-950 hover:bg-teal-300">Lihat Paket Umroh</a></div>
    </section>

    @if ($registrations->isEmpty())
        <section class="mt-6 grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
            <div class="travel-panel p-6 sm:p-8"><span class="grid size-12 place-items-center rounded-2xl bg-teal-50 text-teal-700"><i data-lucide="plane" class="size-5"></i></span><h2 class="mt-5 text-2xl font-extrabold">Belum memilih paket</h2><p class="mt-2 max-w-xl leading-7 text-slate-600">Bandingkan harga, jadwal, hotel, maskapai, dan itinerary. Biodata lengkap akan diminta setelah paket dipilih.</p><a href="{{ route('portal.packages.index') }}" class="button-primary mt-6">Pilih Paket Perjalanan</a></div>
            <div class="travel-panel p-6"><h2 class="font-extrabold">Alur Pendaftaran</h2><ol class="mt-5 space-y-4 text-sm">@foreach ([['1','Pilih paket perjalanan'],['2','Isi dan periksa biodata'],['3','Bayar di kantor cabang'],['4','Admin memverifikasi pendaftaran']] as $step)<li class="flex items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-slate-900 text-xs font-bold text-white">{{ $step[0] }}</span><span class="font-semibold text-slate-700">{{ $step[1] }}</span></li>@endforeach</ol></div>
        </section>
    @else
        <div class="mt-8 flex items-end justify-between"><div><p class="text-sm font-bold uppercase tracking-[.16em] text-teal-700">Pendaftaran Anda</p><h2 class="mt-2 text-2xl font-extrabold">Status Perjalanan</h2></div><a href="{{ route('portal.packages.index') }}" class="text-sm font-bold text-teal-700">Lihat paket lain</a></div>
        <div class="mt-5 grid gap-5">
            @foreach ($registrations as $registration)
                @php
                    $status = match ($registration->status) { 'submitted' => ['Menunggu Verifikasi','bg-amber-50 text-amber-800'], 'contacted' => ['Dihubungi Admin','bg-blue-50 text-blue-700'], 'approved' => ['Terdaftar','bg-emerald-50 text-emerald-700'], 'cancelled' => ['Dibatalkan','bg-red-50 text-red-700'], default => [ucfirst($registration->status),'bg-slate-100 text-slate-700'] };
                @endphp
                <article class="travel-panel overflow-hidden"><div class="grid lg:grid-cols-[1fr_360px]"><div class="p-6 sm:p-7"><div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-teal-700">{{ $registration->branch?->name }}</p><h3 class="mt-2 text-2xl font-extrabold">{{ $registration->departure?->program_name }}</h3></div><span class="rounded-full px-3 py-1.5 text-xs font-extrabold {{ $status[1] }}">{{ $status[0] }}</span></div><div class="mt-5 grid gap-3 text-sm text-slate-600 sm:grid-cols-2"><p><strong class="block text-xs uppercase text-slate-400">Jadwal</strong>{{ $registration->departure?->departure_date?->translatedFormat('d M Y') }} – {{ $registration->departure?->return_date?->translatedFormat('d M Y') }}</p><p><strong class="block text-xs uppercase text-slate-400">Pendaftar</strong>{{ $registration->full_name }}</p></div></div>
                    <aside class="border-t border-slate-200 bg-slate-50 p-6 lg:border-l lg:border-t-0"><p class="text-xs font-bold uppercase tracking-[.14em] text-amber-700">Pembayaran di Cabang</p><h4 class="mt-2 font-extrabold">{{ $registration->payment_status === 'verified' ? 'Pembayaran terverifikasi' : 'Silakan kunjungi kantor cabang' }}</h4><p class="mt-3 text-sm leading-6 text-slate-600">{{ $registration->branch?->address ?: 'Hubungi admin cabang untuk mendapatkan alamat dan jadwal pelayanan.' }}</p>@if ($registration->branch?->phone)<a href="https://wa.me/{{ preg_replace('/\D+/', '', $registration->branch->phone) }}" target="_blank" rel="noopener" class="button-secondary mt-4 w-full">Hubungi Cabang</a>@endif</aside></div></article>
            @endforeach
        </div>
    @endif
@endsection
