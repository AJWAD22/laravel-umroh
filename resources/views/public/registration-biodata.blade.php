<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi Jamaah - {{ config('app.name', 'Mantau Umroh') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900">
    <header class="bg-slate-950 text-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-5 py-5">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Mantau Umroh" class="size-11 rounded-xl object-contain ring-1 ring-white/15">
                <span class="text-lg font-extrabold">Mantau Umroh</span>
            </a>
            <a href="{{ route('landing') }}" class="rounded-full border border-white/25 px-4 py-2 text-sm font-bold">Kembali</a>
        </nav>
    </header>

    <main class="mx-auto max-w-3xl px-5 py-10 sm:py-14">
        <div class="mb-8 grid grid-cols-2 gap-3 text-sm font-bold">
            <div class="rounded-2xl bg-teal-600 px-4 py-3 text-white"><span class="mr-2">1</span> Isi Biodata</div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-500"><span class="mr-2">2</span> Pilih Paket</div>
        </div>

        <section class="travel-panel overflow-hidden">
            <div class="border-b border-slate-200 p-6 sm:p-8">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-700">Langkah pertama</p>
                <h1 class="mt-2 text-3xl font-extrabold">Isi Biodata Jamaah</h1>
                <p class="mt-2 leading-7 text-slate-600">Lengkapi data diri terlebih dahulu. Setelah itu Anda akan diarahkan untuk memilih paket perjalanan.</p>
            </div>

            <form method="POST" action="{{ route('public-registration.biodata.store') }}" class="grid gap-5 p-6 sm:grid-cols-2 sm:p-8">
                @csrf
                @php $value = fn ($key) => old($key, $biodata[$key] ?? ''); @endphp
                <label class="sm:col-span-2"><span class="mb-1.5 block text-sm font-bold">Nama Lengkap</span><input required name="full_name" value="{{ $value('full_name') }}" class="control-field w-full"></label>
                <label><span class="mb-1.5 block text-sm font-bold">NIK</span><input required name="nik" inputmode="numeric" value="{{ $value('nik') }}" class="control-field w-full"></label>
                <label><span class="mb-1.5 block text-sm font-bold">Nomor Paspor <span class="font-normal text-slate-400">(jika ada)</span></span><input name="passport_number" value="{{ $value('passport_number') }}" class="control-field w-full"></label>
                <label><span class="mb-1.5 block text-sm font-bold">Jenis Kelamin</span><select required name="gender" class="control-field w-full"><option value="male" @selected($value('gender') === 'male')>Laki-laki</option><option value="female" @selected($value('gender') === 'female')>Perempuan</option></select></label>
                <label><span class="mb-1.5 block text-sm font-bold">Tanggal Lahir</span><input required type="date" name="birth_date" value="{{ $value('birth_date') }}" class="control-field w-full"></label>
                <label class="sm:col-span-2"><span class="mb-1.5 block text-sm font-bold">Nomor Telepon/WhatsApp</span><input required name="phone" inputmode="tel" value="{{ $value('phone') }}" class="control-field w-full"></label>
                <label class="sm:col-span-2"><span class="mb-1.5 block text-sm font-bold">Alamat Lengkap</span><textarea required name="address" rows="3" class="control-field w-full">{{ $value('address') }}</textarea></label>
                <label class="sm:col-span-2"><span class="mb-1.5 block text-sm font-bold">Catatan <span class="font-normal text-slate-400">(opsional)</span></span><textarea name="notes" rows="3" class="control-field w-full">{{ $value('notes') }}</textarea></label>

                @if ($errors->any())
                    <div class="sm:col-span-2 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
                @endif
                @if (session('error'))
                    <div class="sm:col-span-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-800">{{ session('error') }}</div>
                @endif

                <div class="sm:col-span-2 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('landing') }}" class="button-secondary justify-center">Batal</a>
                    <button class="button-primary justify-center px-6">Lanjut Pilih Paket <i data-lucide="arrow-right" class="size-4"></i></button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
