<x-app-layout>
    <x-slot:title>Pendaftaran Jamaah</x-slot:title>
    <x-slot:header>
        <nav class="mb-2 text-sm text-slate-500">Paket Publik / Pendaftaran Jamaah</nav>
        <h1 class="text-2xl font-bold">Pendaftaran Jamaah</h1>
        <p class="mt-1 text-sm text-slate-500">Verifikasi paket, biodata, dokumen, pembayaran cabang, dan rombongan jamaah.</p>
    </x-slot:header>

    @php
        $statusOptions = [
            'draft' => 'Draft',
            'submitted' => 'Menunggu Verifikasi',
            'revision_requested' => 'Perlu Perbaikan',
            'approved' => 'Menunggu Pembayaran',
            'in_group' => 'Masuk Rombongan',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
        ];
        $paymentOptions = [
            'unpaid' => 'Belum Pembayaran',
            'pending_branch_payment' => 'Menunggu Pembayaran',
            'down_payment' => 'DP',
            'paid' => 'Lunas',
            'verified' => 'Lunas',
            'cancelled' => 'Dibatalkan',
        ];
    @endphp

    <section class="surface-card overflow-hidden">
        <form method="GET" class="grid gap-3 border-b border-slate-200 p-5 dark:border-slate-800 md:grid-cols-5">
            <input name="search" value="{{ request('search') }}" placeholder="Nama atau telepon" class="control-field w-full">
            <select name="departure_id" class="control-field w-full">
                <option value="">Semua paket</option>
                @foreach ($departures as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('departure_id') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="payment_status" class="control-field w-full">
                <option value="">Semua pembayaran</option>
                @foreach ($paymentOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="control-field w-full">
                <option value="">Semua status</option>
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="button-secondary justify-center"><i data-lucide="list-filter" class="size-4"></i>Terapkan Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-5 py-3.5">Jamaah</th>
                        <th class="px-5 py-3.5">Paket & Cabang</th>
                        <th class="px-5 py-3.5">Biodata</th>
                        <th class="px-5 py-3.5">Dokumen</th>
                        <th class="px-5 py-3.5">Kesehatan</th>
                        <th class="px-5 py-3.5">Status Admin Cabang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($registrations as $registration)
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $registration->full_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $registration->phone }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $registration->created_at->translatedFormat('d M Y H:i') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium">{{ $registration->departure?->program_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $registration->departure?->departure_date?->translatedFormat('d M Y') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $registration->branch?->name }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs leading-5 text-slate-600">
                                <p>NIK: {{ $registration->maskedNik() }}</p>
                                <p>Paspor: {{ $registration->maskedPassportNumber() }}</p>
                                <p>Gender: {{ $registration->gender === 'male' ? 'Laki-laki' : ($registration->gender === 'female' ? 'Perempuan' : '-') }}</p>
                                <p>Darurat: {{ $registration->emergency_contact_name ?: '-' }} {{ $registration->emergency_contact_phone ? '('.$registration->emergency_contact_phone.')' : '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs leading-6">
                                @foreach ([
                                    'photo_path' => 'Foto',
                                    'identity_document_path' => 'Identitas',
                                    'passport_document_path' => 'Paspor',
                                ] as $column => $label)
                                    @if ($registration->{$column})
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($registration->{$column}) }}" target="_blank" class="block font-bold text-teal-700">{{ $label }}</a>
                                    @else
                                        <p class="text-slate-400">{{ $label }} belum ada</p>
                                    @endif
                                @endforeach
                                @if ($registration->document_notes)
                                    <p class="mt-2 text-slate-500">{{ $registration->document_notes }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs leading-6 text-slate-600">
                                {{ $registration->health_notes ?: 'Tidak ada catatan khusus.' }}
                            </td>
                            <td class="px-5 py-4">
                                @if ($canManage)
                                    <form method="POST" action="{{ route('registrations.update', $registration) }}" class="grid min-w-60 gap-2">
                                        @csrf @method('PATCH')
                                        <select name="status" class="control-field text-xs">
                                            @foreach ($statusOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($registration->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select name="payment_status" class="control-field text-xs">
                                            @foreach ($paymentOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($registration->payment_status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @php
                                            $matchingGroups = $groups->where('departure_id', $registration->departure_id);
                                            $activeMembership = $registration->user?->pilgrim?->groupMemberships
                                                ?->firstWhere('status', 'active');
                                        @endphp
                                        <select name="group_id" class="control-field text-xs">
                                            <option value="">Pilih rombongan saat status Masuk Rombongan</option>
                                            @foreach ($matchingGroups as $group)
                                                <option value="{{ $group->id }}" @selected((int) $activeMembership?->group_id === (int) $group->id)>
                                                    {{ $group->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <textarea name="revision_notes" rows="2" class="control-field text-xs" placeholder="Catatan perbaikan untuk jamaah">{{ $registration->revision_notes }}</textarea>
                                        @if ($matchingGroups->isEmpty())
                                            <p class="text-[11px] leading-4 text-amber-600">Buat rombongan untuk paket ini sebelum memasukkan jamaah.</p>
                                        @endif
                                        <button class="button-primary min-h-9 py-2 text-xs">Simpan Status</button>
                                    </form>
                                @else
                                    <div class="grid gap-2">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-slate-800">{{ $statusOptions[$registration->status] ?? ucfirst($registration->status) }}</span>
                                        <span class="text-xs text-slate-500">{{ $paymentOptions[$registration->payment_status] ?? str($registration->payment_status)->replace('_', ' ')->title() }}</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="clipboard-list" title="Belum ada pendaftaran" description="Pendaftaran dari portal jamaah akan tampil di sini." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $registrations->links() }}</div>
    </section>
</x-app-layout>
