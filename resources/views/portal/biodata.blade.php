@extends('portal.layout')
@section('title', 'Lengkapi Biodata')
@section('content')
    @php
        $value = fn (string $field, mixed $fallback = '') => old($field, $registration->{$field} ?? $fallback);
        $dateValue = fn (string $field) => old($field) ?: optional($registration->{$field})->format('Y-m-d');
    @endphp
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 grid grid-cols-3 gap-2 text-xs font-bold sm:text-sm">
            <div class="rounded-2xl bg-emerald-50 px-3 py-3 text-emerald-700">Akun Dibuat</div>
            <div class="rounded-2xl bg-emerald-50 px-3 py-3 text-emerald-700">Paket Dipilih</div>
            <div class="rounded-2xl bg-teal-600 px-3 py-3 text-white">Isi Biodata</div>
        </div>

        <section class="mb-5 rounded-2xl border border-teal-200 bg-teal-50 p-5">
            <p class="text-xs font-bold uppercase tracking-[.14em] text-teal-700">Paket Pilihan</p>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold">{{ $package->program_name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $package->branch?->name }} - {{ $package->departure_date->translatedFormat('d M Y') }}</p>
                </div>
                <a href="{{ route('portal.packages.index') }}" class="text-sm font-bold text-teal-700">Ganti paket</a>
            </div>
        </section>

        <section class="travel-panel overflow-hidden">
            <div class="border-b border-slate-200 p-6 sm:p-8">
                <p class="text-sm font-bold uppercase tracking-[.18em] text-teal-700">Data Pendaftaran</p>
                <h1 class="mt-2 text-3xl font-extrabold">Lengkapi Biodata Jamaah</h1>
                <p class="mt-2 leading-7 text-slate-600">Isi bertahap. Anda bisa menyimpan draft terlebih dahulu, lalu mengirim data saat sudah siap diverifikasi cabang.</p>
                @if ($registration->revision_notes)
                    <div class="mt-5 rounded-2xl border border-orange-200 bg-orange-50 p-4 text-sm font-semibold leading-6 text-orange-900">{{ $registration->revision_notes }}</div>
                @endif
            </div>

            <form method="POST" action="{{ route('portal.biodata.store') }}" enctype="multipart/form-data" class="grid gap-6 p-6 sm:p-8">
                @csrf

                <section class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <h2 class="text-lg font-extrabold">1. Identitas Jamaah</h2>
                        <p class="mt-1 text-sm text-slate-500">Data ini harus sesuai KTP dan paspor.</p>
                    </div>
                    <label class="sm:col-span-2"><span class="mb-1.5 block text-sm font-bold">Nama Lengkap sesuai Identitas</span><input name="full_name" value="{{ $value('full_name', auth()->user()->name) }}" class="control-field w-full"></label>
                    <label><span class="mb-1.5 block text-sm font-bold">NIK</span><input name="nik" value="{{ $value('nik') }}" inputmode="numeric" class="control-field w-full" maxlength="20"></label>
                    <label><span class="mb-1.5 block text-sm font-bold">Jenis Kelamin</span><select name="gender" class="control-field w-full"><option value="">Pilih</option><option value="male" @selected($value('gender') === 'male')>Laki-laki</option><option value="female" @selected($value('gender') === 'female')>Perempuan</option></select></label>
                    <label><span class="mb-1.5 block text-sm font-bold">Tanggal Lahir</span><input type="date" name="birth_date" value="{{ $dateValue('birth_date') }}" class="control-field w-full"></label>
                    <label><span class="mb-1.5 block text-sm font-bold">Nomor WhatsApp Akun</span><input disabled value="{{ $account->phone }}" class="control-field w-full bg-slate-100"></label>
                    <label><span class="mb-1.5 block text-sm font-bold">Nomor Paspor</span><input name="passport_number" value="{{ $value('passport_number') }}" class="control-field w-full"></label>
                    <label><span class="mb-1.5 block text-sm font-bold">Masa Berlaku Paspor</span><input type="date" name="passport_expired_at" value="{{ $dateValue('passport_expired_at') }}" class="control-field w-full"></label>
                </section>

                <section class="grid gap-5 border-t border-slate-200 pt-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <h2 class="text-lg font-extrabold">2. Alamat dan Kontak</h2>
                    </div>
                    <label class="sm:col-span-2"><span class="mb-1.5 block text-sm font-bold">Alamat Lengkap</span><textarea name="address" rows="3" class="control-field w-full">{{ $value('address') }}</textarea></label>
                    <label><span class="mb-1.5 block text-sm font-bold">Nama Kontak Darurat</span><input name="emergency_contact_name" value="{{ $value('emergency_contact_name') }}" class="control-field w-full"></label>
                    <label><span class="mb-1.5 block text-sm font-bold">Nomor Kontak Darurat</span><input name="emergency_contact_phone" value="{{ $value('emergency_contact_phone') }}" inputmode="tel" class="control-field w-full"></label>
                </section>

                <section class="grid gap-5 border-t border-slate-200 pt-6">
                    <div>
                        <h2 class="text-lg font-extrabold">3. Kesehatan dan Kebutuhan Khusus</h2>
                        <p class="mt-1 text-sm text-slate-500">Tuliskan kondisi penting agar pendamping perjalanan bisa membantu dengan tepat.</p>
                    </div>
                    <label><span class="mb-1.5 block text-sm font-bold">Informasi Kesehatan Penting</span><textarea name="health_notes" rows="3" class="control-field w-full" placeholder="Contoh: riwayat penyakit, alergi obat, kursi roda, atau kebutuhan pendampingan">{{ $value('health_notes') }}</textarea></label>
                    <label><span class="mb-1.5 block text-sm font-bold">Catatan Tambahan</span><textarea name="notes" rows="3" class="control-field w-full">{{ $value('notes') }}</textarea></label>
                </section>

                <section class="grid gap-5 border-t border-slate-200 pt-6 sm:grid-cols-3">
                    <div class="sm:col-span-3">
                        <h2 class="text-lg font-extrabold">4. Foto dan Dokumen</h2>
                        <p class="mt-1 text-sm text-slate-500">Unggah dokumen yang tersedia. Dokumen lain dapat disusulkan melalui Simpan Draft.</p>
                    </div>
                    <label><span class="mb-1.5 block text-sm font-bold">Foto Jamaah</span><input type="file" name="photo" accept=".jpg,.jpeg,.png" class="control-field w-full p-2"></label>
                    <label><span class="mb-1.5 block text-sm font-bold">KTP / Identitas</span><input type="file" name="identity_document" accept=".jpg,.jpeg,.png,.pdf" class="control-field w-full p-2"></label>
                    <label><span class="mb-1.5 block text-sm font-bold">Paspor</span><input type="file" name="passport_document" accept=".jpg,.jpeg,.png,.pdf" class="control-field w-full p-2"></label>
                    <label class="sm:col-span-3"><span class="mb-1.5 block text-sm font-bold">Catatan Dokumen</span><textarea name="document_notes" rows="3" class="control-field w-full" placeholder="Contoh: paspor sedang diperpanjang, foto akan disusulkan">{{ $value('document_notes') }}</textarea></label>
                    @if ($registration->photo_path || $registration->identity_document_path || $registration->passport_document_path)
                        <div class="sm:col-span-3 rounded-2xl bg-slate-50 p-4 text-sm leading-7 text-slate-600">
                            <p class="font-extrabold text-slate-800">Dokumen tersimpan:</p>
                            @if ($registration->photo_path)<p>Foto jamaah sudah diunggah.</p>@endif
                            @if ($registration->identity_document_path)<p>KTP / identitas sudah diunggah.</p>@endif
                            @if ($registration->passport_document_path)<p>Paspor sudah diunggah.</p>@endif
                        </div>
                    @endif
                </section>

                <label class="flex items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                    <input type="checkbox" name="confirmation" value="1" class="mt-1 rounded border-slate-300 text-teal-600">
                    <span>Saya menyatakan data yang dikirim benar dan siap diverifikasi oleh admin cabang. Pembayaran dilakukan langsung melalui kantor cabang travel.</span>
                </label>

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('portal.packages.show', $package) }}" class="button-secondary">Kembali</a>
                    <button name="action" value="draft" class="button-secondary">Simpan Draft</button>
                    <button name="action" value="submit" class="button-primary px-7">Kirim untuk Verifikasi</button>
                </div>
            </form>
        </section>
    </div>
@endsection
