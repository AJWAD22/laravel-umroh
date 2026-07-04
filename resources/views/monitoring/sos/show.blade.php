<x-app-layout>
    <x-slot:title>Detail SOS {{ $sosReport->pilgrim->full_name }}</x-slot:title>
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <nav class="mb-2 text-sm text-slate-500">Monitoring / SOS / Detail</nav>
                <h1 class="text-2xl font-bold">Detail Laporan SOS</h1>
                <p class="mt-1 text-sm text-slate-500">Dilaporkan {{ $sosReport->reported_at->diffForHumans() }}.</p>
            </div>
            <a href="{{ route('monitoring.sos.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold">Kembali</a>
        </div>
    </x-slot:header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_420px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div id="sos-detail-map" data-latitude="{{ $sosReport->latitude }}" data-longitude="{{ $sosReport->longitude }}" data-name="{{ $sosReport->pilgrim->full_name }}"
                 class="h-[560px] w-full bg-slate-100"></div>
            <div class="border-t border-slate-200 px-5 py-4 font-mono text-sm dark:border-slate-800">
                {{ $sosReport->latitude }}, {{ $sosReport->longitude }}
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-4">
                    @if ($sosReport->pilgrim->photo_path)
                        <img src="{{ asset('storage/'.$sosReport->pilgrim->photo_path) }}" alt="{{ $sosReport->pilgrim->full_name }}" class="size-14 rounded-2xl object-cover">
                    @else
                        <span class="grid size-14 place-items-center rounded-2xl bg-red-600 text-lg font-bold text-white">{{ str($sosReport->pilgrim->full_name)->substr(0, 2)->upper() }}</span>
                    @endif
                    <div>
                        <h2 class="font-bold">{{ $sosReport->pilgrim->full_name }}</h2>
                        <p class="text-sm text-slate-500">{{ $sosReport->pilgrim->registration_number }} · {{ $sosReport->pilgrim->phone ?: '-' }}</p>
                    </div>
                </div>
                <dl class="mt-5 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Cabang</dt><dd class="font-semibold">{{ $sosReport->branch->name }}</dd></div>
                    <div><dt class="text-slate-500">Rombongan</dt><dd class="font-semibold">{{ $sosReport->group?->name ?? '-' }}</dd></div>
                    <div><dt class="text-slate-500">Tour Leader</dt><dd class="font-semibold">{{ $sosReport->group?->tourLeader?->full_name ?? '-' }}</dd></div>
                    <div><dt class="text-slate-500">Muthawwif</dt><dd class="font-semibold">{{ $sosReport->group?->muthawwif?->full_name ?? '-' }}</dd></div>
                    <div><dt class="text-slate-500">Pesan</dt><dd class="font-semibold">{{ $sosReport->message ?: 'Tidak ada pesan tambahan.' }}</dd></div>
                    @if ($sosReport->handler)
                        <div><dt class="text-slate-500">Ditangani oleh</dt><dd class="font-semibold">{{ $sosReport->handler->name }}</dd></div>
                    @endif
                    @if ($sosReport->resolved_at)
                        <div><dt class="text-slate-500">Selesai</dt><dd class="font-semibold">{{ $sosReport->resolved_at->format('d M Y H:i') }}</dd></div>
                    @endif
                </dl>
            </section>

            @can('update', $sosReport)
            @if ($sosReport->status !== 'resolved')
                <form method="POST" action="{{ route('monitoring.sos.resolve', $sosReport) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    @csrf @method('PATCH')
                    <label class="text-sm font-semibold text-emerald-900">Catatan penyelesaian</label>
                    <textarea name="resolution_notes" rows="3" placeholder="Contoh: Jamaah telah dijemput Tour Leader."
                              class="mt-2 w-full rounded-xl border-emerald-300 bg-white text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('resolution_notes') }}</textarea>
                    @error('resolution_notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <button class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Tandai Selesai</button>
                </form>
            @elseif ($sosReport->status === 'resolved')
                <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800">
                    <p class="font-semibold">Laporan telah selesai</p>
                    <p class="mt-1">{{ $sosReport->resolution_notes ?: 'Tidak ada catatan penyelesaian.' }}</p>
                </section>
            @endif
            @endcan
        </aside>
    </div>
</x-app-layout>
