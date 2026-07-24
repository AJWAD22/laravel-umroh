@extends('portal.layout')
@section('title', 'Beranda Jamaah')
@section('content')
    <section class="overflow-hidden rounded-[1.35rem] bg-[#071827] p-6 text-white shadow-xl sm:p-8">
        <div class="grid gap-7 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-[.18em] text-teal-300">Assalamu'alaikum</p>
                <h1 class="mt-3 text-3xl font-extrabold sm:text-4xl">{{ auth()->user()->name }}</h1>
                <p class="mt-3 max-w-2xl leading-7 text-slate-300">Lihat tahap pendaftaran, paket pilihan, biodata, verifikasi cabang, dan pembayaran dari satu tempat.</p>
            </div>
            <a href="{{ route('portal.packages.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-teal-400 px-6 font-extrabold text-slate-950 hover:bg-teal-300">Pilih Paket Umroh</a>
        </div>
    </section>

    @if ($registrations->isEmpty())
        <section class="mt-6 grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
            <div class="travel-panel p-6 sm:p-8">
                <span class="grid size-12 place-items-center rounded-2xl bg-teal-50 text-teal-700"><i data-lucide="plane" class="size-5"></i></span>
                <h2 class="mt-5 text-2xl font-extrabold">Belum memilih paket</h2>
                <p class="mt-2 max-w-xl leading-7 text-slate-600">Pilih paket terlebih dahulu. Biodata, dokumen, dan verifikasi cabang dilakukan setelah paket dipilih.</p>
                <a href="{{ route('portal.packages.index') }}" class="button-primary mt-6">Pilih Paket Perjalanan</a>
            </div>
            <div class="travel-panel p-6">
                <h2 class="font-extrabold">Tahapan Pendaftaran</h2>
                <ol class="mt-5 space-y-4 text-sm">
                    @foreach (['Pilih Paket', 'Isi Biodata', 'Verifikasi', 'Pembayaran', 'Masuk Rombongan'] as $index => $step)
                        <li class="flex items-center gap-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-slate-900 text-xs font-bold text-white">{{ $index + 1 }}</span>
                            <span class="font-semibold text-slate-700">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @else
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-[.16em] text-teal-700">Pendaftaran Anda</p>
                <h2 class="mt-2 text-2xl font-extrabold">Status Perjalanan</h2>
            </div>
            <a href="{{ route('portal.packages.index') }}" class="text-sm font-bold text-teal-700">Lihat paket lain</a>
        </div>

        <div class="mt-5 grid gap-5">
            @foreach ($registrations as $registration)
                @php
                    $statusMap = [
                        'draft' => ['Draft', 'bg-slate-100 text-slate-700', 1],
                        'submitted' => ['Menunggu Verifikasi', 'bg-amber-50 text-amber-800', 2],
                        'revision_requested' => ['Perlu Perbaikan', 'bg-orange-50 text-orange-800', 2],
                        'approved' => ['Menunggu Pembayaran', 'bg-blue-50 text-blue-700', 3],
                        'in_group' => ['Masuk Rombongan', 'bg-emerald-50 text-emerald-700', 5],
                        'rejected' => ['Ditolak', 'bg-red-50 text-red-700', 0],
                        'cancelled' => ['Dibatalkan', 'bg-red-50 text-red-700', 0],
                    ];
                    $paymentMap = [
                        'unpaid' => 'Belum pembayaran',
                        'pending_branch_payment' => 'Menunggu pembayaran cabang',
                        'down_payment' => 'DP',
                        'paid' => 'Lunas',
                        'verified' => 'Lunas',
                        'cancelled' => 'Dibatalkan',
                    ];
                    [$statusLabel, $statusClass, $progress] = $statusMap[$registration->status] ?? [str($registration->status)->replace('_', ' ')->title(), 'bg-slate-100 text-slate-700', 1];
                    $steps = ['Pilih Paket', 'Isi Biodata', 'Verifikasi', 'Pembayaran', 'Masuk Rombongan'];
                    $biodataComplete = filled($registration->nik) && filled($registration->gender) && filled($registration->birth_date) && filled($registration->address) && filled($registration->emergency_contact_name) && filled($registration->emergency_contact_phone);
                    $continueRoute = in_array($registration->status, ['draft', 'revision_requested'], true)
                        ? route('portal.biodata.edit')
                        : route('portal.packages.show', $registration->departure);
                @endphp
                <article class="travel-panel overflow-hidden">
                    <div class="grid lg:grid-cols-[1fr_360px]">
                        <div class="p-6 sm:p-7">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[.16em] text-teal-700">{{ $registration->branch?->name ?: 'Cabang belum ditentukan' }}</p>
                                    <h3 class="mt-2 text-2xl font-extrabold">{{ $registration->departure?->program_name }}</h3>
                                </div>
                                <span class="rounded-full px-3 py-1.5 text-xs font-extrabold {{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>

                            <div class="mt-6 grid gap-2 sm:grid-cols-5">
                                @foreach ($steps as $index => $step)
                                    <div class="rounded-2xl px-3 py-3 text-xs font-extrabold {{ $progress >= $index + 1 ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $step }}</div>
                                @endforeach
                            </div>

                            <div class="mt-6 grid gap-4 text-sm text-slate-600 sm:grid-cols-2">
                                <p><strong class="block text-xs uppercase text-slate-400">Paket dipilih</strong>{{ $registration->departure?->program_name ?: '-' }}</p>
                                <p><strong class="block text-xs uppercase text-slate-400">Kelengkapan biodata</strong>{{ $biodataComplete ? 'Lengkap' : 'Belum lengkap' }}</p>
                                <p><strong class="block text-xs uppercase text-slate-400">Status verifikasi</strong>{{ $statusLabel }}</p>
                                <p><strong class="block text-xs uppercase text-slate-400">Status pembayaran</strong>{{ $paymentMap[$registration->payment_status] ?? str($registration->payment_status)->replace('_', ' ')->title() }}</p>
                            </div>

                            @if ($registration->revision_notes)
                                <div class="mt-5 rounded-2xl border border-orange-200 bg-orange-50 p-4 text-sm font-semibold leading-6 text-orange-900">{{ $registration->revision_notes }}</div>
                            @endif

                            <a href="{{ $continueRoute }}" class="button-primary mt-6">Lanjutkan Pendaftaran</a>
                        </div>
                        <aside class="border-t border-slate-200 bg-slate-50 p-6 lg:border-l lg:border-t-0">
                            <p class="text-xs font-bold uppercase tracking-[.14em] text-amber-700">Cabang Pelayanan</p>
                            <h4 class="mt-2 font-extrabold">{{ $registration->branch?->name ?: 'Hubungi admin travel' }}</h4>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $registration->branch?->address ?: 'Alamat cabang akan tampil setelah data cabang dilengkapi.' }}</p>
                            @if ($registration->branch?->phone)
                                <a href="https://wa.me/{{ preg_replace('/\D+/', '', $registration->branch->phone) }}" target="_blank" rel="noopener" class="button-secondary mt-4 w-full">Hubungi Cabang</a>
                            @endif
                        </aside>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
