@php
    $price = $package->price ? 'Rp '.number_format($package->price, 0, ',', '.') : 'Hubungi admin';
@endphp

<article class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{{ $package->branch?->name }}</p>
            <h3 class="mt-2 text-xl font-extrabold text-slate-950">{{ $package->program_name }}</h3>
        </div>
        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-extrabold text-teal-700">{{ $package->duration_days }} hari</span>
    </div>

    <dl class="mt-5 grid gap-3 text-sm text-slate-600">
        <div class="flex items-center gap-2"><i data-lucide="calendar-days" class="size-4 text-teal-700"></i>{{ $package->departure_date->translatedFormat('d M Y') }} - {{ $package->return_date->translatedFormat('d M Y') }}</div>
        <div class="flex items-center gap-2"><i data-lucide="plane" class="size-4 text-teal-700"></i>{{ $package->airline ?: 'Maskapai menyusul' }} {{ $package->flight_number }}</div>
        <div class="flex items-center gap-2"><i data-lucide="hotel" class="size-4 text-teal-700"></i>{{ $package->hotels->pluck('name')->take(2)->implode(', ') ?: 'Hotel menyusul' }}</div>
        <div class="flex items-center gap-2"><i data-lucide="users" class="size-4 text-teal-700"></i>{{ $package->remaining_quota === null ? 'Kuota fleksibel' : $package->remaining_quota.' kursi tersisa' }}</div>
    </dl>

    <div class="mt-6 flex items-center justify-between gap-3 border-t border-slate-100 pt-5">
        <p class="text-lg font-extrabold text-slate-950">{{ $price }}</p>
        <a href="{{ route('packages.show', $package) }}" class="button-primary">{{ $package->remaining_quota === 0 ? 'Lihat Paket' : 'Detail & Daftar' }}</a>
    </div>
</article>
