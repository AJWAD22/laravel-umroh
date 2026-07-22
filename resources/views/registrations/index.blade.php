<x-app-layout>
    <x-slot:title>Registrasi Paket Umroh</x-slot:title>
    <x-slot:header>
        <nav class="mb-2 text-sm text-slate-500">Paket Publik / Registrasi Jamaah</nav>
        <h1 class="text-2xl font-bold">Registrasi Paket Umroh</h1>
        <p class="mt-1 text-sm text-slate-500">Tindak lanjuti biodata jamaah yang mendaftar dari landing page.</p>
    </x-slot:header>

    <section class="surface-card overflow-hidden">
        <form method="GET" class="grid gap-3 border-b border-slate-200 p-5 dark:border-slate-800 md:grid-cols-4">
            <input name="search" value="{{ request('search') }}" placeholder="Nama, telepon, atau NIK" class="control-field w-full">
            <select name="departure_id" class="control-field w-full">
                <option value="">Semua paket</option>
                @foreach ($departures as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('departure_id') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="status" class="control-field w-full">
                <option value="">Semua status</option>
                @foreach (['submitted' => 'Baru Masuk', 'contacted' => 'Sudah Dihubungi', 'approved' => 'Disetujui', 'cancelled' => 'Dibatalkan'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="button-secondary justify-center"><i data-lucide="list-filter" class="size-4"></i>Terapkan Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-5 py-3.5">Jamaah</th>
                        <th class="px-5 py-3.5">Paket</th>
                        <th class="px-5 py-3.5">Kontak</th>
                        <th class="px-5 py-3.5">Biodata</th>
                        <th class="px-5 py-3.5">Tanggal Daftar</th>
                        <th class="px-5 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($registrations as $registration)
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $registration->full_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $registration->branch?->name }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium">{{ $registration->departure?->program_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $registration->departure?->departure_date?->translatedFormat('d M Y') }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $registration->phone }}</td>
                            <td class="px-5 py-4 text-xs leading-5 text-slate-600">
                                <p>NIK: {{ $registration->nik ?: '-' }}</p>
                                <p>Paspor: {{ $registration->passport_number ?: '-' }}</p>
                                <p>{{ $registration->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $registration->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="px-5 py-4">
                                @if ($canManage)
                                    <form method="POST" action="{{ route('registrations.update', $registration) }}">
                                        @csrf @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="control-field min-w-40 text-xs">
                                            @foreach (['submitted' => 'Baru Masuk', 'contacted' => 'Sudah Dihubungi', 'approved' => 'Disetujui', 'cancelled' => 'Dibatalkan'] as $value => $label)
                                                <option value="{{ $value }}" @selected($registration->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-slate-800">{{ ucfirst($registration->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="clipboard-list" title="Belum ada registrasi" description="Pendaftaran dari landing page akan tampil di sini." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $registrations->links() }}</div>
    </section>
</x-app-layout>
