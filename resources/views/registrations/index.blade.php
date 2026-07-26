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
            'unpaid' => 'Belum Bayar',
            'pending_branch_payment' => 'Menunggu Pembayaran',
            'down_payment' => 'DP',
            'paid' => 'Lunas',
            'verified' => 'Lunas',
            'cancelled' => 'Dibatalkan',
        ];
    @endphp

    <section class="mb-5 rounded-2xl border border-teal-200 bg-teal-50 p-5 text-sm leading-6 text-teal-950 dark:border-teal-900 dark:bg-teal-950/30 dark:text-teal-100">
        <div class="flex items-start gap-3">
            <i data-lucide="clipboard-list" class="mt-0.5 size-5 shrink-0 text-teal-700 dark:text-teal-300"></i>
            <div>
                <p class="font-bold">Pendaftaran Jamaah adalah area calon jamaah.</p>
                <p class="mt-1">Pilihan paket dari portal jamaah masuk ke halaman ini sebagai proses pendaftaran. Data baru menjadi Data Master Jamaah setelah Admin Cabang menyetujui biodata, mencatat pembayaran lunas, dan memasukkan jamaah ke rombongan.</p>
            </div>
        </div>
    </section>

    <section class="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5" aria-label="Alur verifikasi pendaftaran">
        @foreach ([
            ['Menunggu Verifikasi', 'Periksa biodata dan dokumen jamaah.', 'clipboard-list', 'bg-blue-50 text-blue-700'],
            ['Perlu Perbaikan', 'Tulis catatan agar jamaah memperbaiki data.', 'circle-alert', 'bg-amber-50 text-amber-700'],
            ['Menunggu Pembayaran', 'Data sudah benar, pembayaran diproses di cabang.', 'wallet', 'bg-cyan-50 text-cyan-700'],
            ['Lunas', 'Pembayaran selesai dan siap ditempatkan.', 'circle-check', 'bg-emerald-50 text-emerald-700'],
            ['Masuk Rombongan', 'Jamaah resmi menjadi data operasional.', 'users-round', 'bg-violet-50 text-violet-700'],
        ] as $step)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="grid size-10 place-items-center rounded-2xl {{ $step[3] }} dark:bg-slate-800">
                    <i data-lucide="{{ $step[2] }}" class="size-4.5"></i>
                </span>
                <h2 class="mt-3 text-sm font-extrabold">{{ $step[0] }}</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $step[1] }}</p>
            </article>
        @endforeach
    </section>

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
                                    <form method="POST" action="{{ route('registrations.update', $registration) }}" class="grid min-w-72 gap-3">
                                        @csrf @method('PATCH')
                                        @php
                                            $matchingGroups = $groups->where('departure_id', $registration->departure_id);
                                            $activeMembership = $registration->user?->pilgrim?->groupMemberships
                                                ?->firstWhere('status', 'active');
                                            $statusTone = match ($registration->status) {
                                                'submitted' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                                'revision_requested' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                                'approved' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
                                                'in_group' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                                'rejected', 'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
                                                default => 'bg-slate-50 text-slate-700 ring-slate-200',
                                            };
                                            $paymentTone = match ($registration->payment_status) {
                                                'paid', 'verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                                'down_payment' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                                'pending_branch_payment' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
                                                'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
                                                default => 'bg-slate-50 text-slate-700 ring-slate-200',
                                            };
                                        @endphp

                                        <div class="flex flex-wrap gap-2">
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $statusTone }}">{{ $statusOptions[$registration->status] ?? ucfirst($registration->status) }}</span>
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $paymentTone }}">{{ $paymentOptions[$registration->payment_status] ?? str($registration->payment_status)->replace('_', ' ')->title() }}</span>
                                        </div>

                                        @if (in_array($registration->status, ['submitted', 'revision_requested'], true))
                                            <textarea name="revision_notes" rows="2" class="control-field text-xs" placeholder="Catatan perbaikan untuk jamaah">{{ $registration->revision_notes }}</textarea>
                                            <div class="grid gap-2">
                                                <button name="action" value="approve_biodata" class="button-primary min-h-9 justify-center py-2 text-xs">
                                                    <i data-lucide="circle-check" class="size-4"></i>
                                                    Setujui Biodata
                                                </button>
                                                <button name="action" value="request_revision" class="button-secondary min-h-9 justify-center border-amber-200 py-2 text-xs text-amber-700 hover:bg-amber-50">
                                                    <i data-lucide="circle-alert" class="size-4"></i>
                                                    Minta Perbaikan
                                                </button>
                                            </div>
                                        @elseif ($registration->status === 'approved')
                                            <p class="rounded-xl bg-cyan-50 p-3 text-xs leading-5 text-cyan-800">Biodata sudah disetujui. Catat pembayaran di bawah, lalu masukkan jamaah ke rombongan setelah lunas.</p>
                                            <div class="grid gap-2 sm:grid-cols-2">
                                                <button name="action" value="record_down_payment" class="button-secondary min-h-9 justify-center py-2 text-xs">
                                                    <i data-lucide="wallet" class="size-4"></i>
                                                    Catat DP
                                                </button>
                                                <button name="action" value="record_paid" class="button-primary min-h-9 justify-center py-2 text-xs">
                                                    <i data-lucide="circle-check" class="size-4"></i>
                                                    Catat Lunas
                                                </button>
                                            </div>
                                            <select name="group_id" class="control-field text-xs">
                                                <option value="">Pilih rombongan setelah pembayaran lunas</option>
                                                @foreach ($matchingGroups as $group)
                                                    <option value="{{ $group->id }}" @selected((int) $activeMembership?->group_id === (int) $group->id)>
                                                        {{ $group->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button name="action" value="move_to_group" class="button-primary min-h-9 justify-center py-2 text-xs">
                                                <i data-lucide="users-round" class="size-4"></i>
                                                Masukkan ke Rombongan
                                            </button>
                                        @elseif ($registration->status === 'in_group')
                                            <div class="rounded-xl bg-emerald-50 p-3 text-xs leading-5 text-emerald-800">
                                                Jamaah sudah resmi masuk rombongan{{ $activeMembership?->group?->name ? ' '.$activeMembership->group->name : '' }}. Lanjutkan ke pengelolaan PIN aktivasi di menu Rombongan.
                                            </div>
                                        @else
                                            <p class="rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-600">Pendaftaran berada pada status akhir atau belum siap diproses. Gunakan pengaturan lanjutan jika perlu perubahan manual.</p>
                                        @endif

                                        @if ($matchingGroups->isEmpty())
                                            <p class="text-[11px] leading-4 text-amber-600">Buat rombongan untuk paket ini sebelum memasukkan jamaah.</p>
                                        @endif

                                        <details class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs dark:border-slate-800 dark:bg-slate-900">
                                            <summary class="cursor-pointer font-bold text-slate-600">Pengaturan lanjutan</summary>
                                            <div class="mt-3 grid gap-2">
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
                                                <button class="button-secondary min-h-9 justify-center py-2 text-xs">Simpan Manual</button>
                                            </div>
                                        </details>
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
