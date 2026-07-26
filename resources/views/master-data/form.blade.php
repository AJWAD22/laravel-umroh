@php
    $editing = $record !== null;
    $value = function (string $key, mixed $default = null) use ($record, $resource) {
        $stored = match (true) {
            $key === 'email' && in_array($resource, ['tour-leaders', 'muthawwifs'], true)
                => data_get($record, 'user.email', $default),
            $resource === 'departures' && $key === 'hotel_ids'
                => $record?->hotels?->pluck('id')->all() ?? $default,
            $resource === 'departures' && $key === 'itinerary_plan'
                => $record?->itineraries?->map(fn ($item) => "{$item->day_number}|{$item->title}|{$item->city}|{$item->description}")->implode("\n") ?? $default,
            $resource === 'pilgrims' && $key === 'group_id'
                => data_get($record?->groupMemberships?->firstWhere('status', 'active'), 'group_id', $default),
            default => data_get($record, $key, $default),
        };

        return old($key, $stored);
    };
    $commonBranch = [['branch_id', 'Cabang', 'select', $options['branches']]];
    $automaticCodeHelp = match ($resource) {
        'pilgrims' => 'Nomor registrasi dibuat otomatis, contoh BJM-JMH-00001.',
        'tour-leaders' => 'Nomor pegawai dibuat otomatis, contoh BJM-TL-001.',
        'muthawwifs' => 'Nomor pegawai dibuat otomatis, contoh BJM-MTF-001.',
        'departures' => 'Kode dibuat dari cabang dan tahun perjalanan, contoh BJM-DEP-2026-001.',
        'groups' => 'Kode dibuat otomatis dari cabang, contoh BJM-GRP-001.',
        default => null,
    };
    $sectionLabel = match (true) {
        in_array($resource, ['branch-admins', 'pilgrims', 'tour-leaders', 'muthawwifs', 'groups'], true) => 'Data Master',
        in_array($resource, ['departures', 'hotels', 'checkpoints'], true) => 'Operasional Perjalanan',
        $resource === 'branches' => 'Organisasi',
        default => 'Data',
    };
    $hasLocationPicker = in_array($resource, ['hotels', 'checkpoints'], true);
    $departureGuide = [
        ['title' => '1. Data dasar', 'description' => 'Isi nama paket, tanggal berangkat-pulang, harga, kuota, dan kota/bandara keberangkatan.'],
        ['title' => '2. Hotel & pesawat', 'description' => 'Pilih hotel Makkah/Madinah yang sudah dibuat, lalu isi maskapai dan nomor penerbangan.'],
        ['title' => '3. Jadwal harian', 'description' => 'Tulis agenda per hari agar jamaah melihat rencana perjalanan di landing dan portal.'],
        ['title' => '4. Publikasi', 'description' => 'Gunakan status Terjadwal dan Tampil di Landing Page jika paket sudah siap dipilih.'],
    ];
    $checkpointGuide = [
        ['title' => 'Umum Cabang', 'description' => 'Kosongkan paket dan rombongan untuk titik umum cabang.'],
        ['title' => 'Khusus Paket', 'description' => 'Pilih paket agar titik terlihat untuk semua jamaah paket tersebut.'],
        ['title' => 'Khusus Rombongan', 'description' => 'Pilih rombongan untuk titik kumpul khusus satu rombongan.'],
        ['title' => 'Geofence', 'description' => 'Kategori Titik Kumpul dan Hotel dipakai sebagai radius aman tracking.'],
    ];
    $hotelGuide = [
        ['title' => '1. Nama & kota', 'description' => 'Pisahkan hotel Makkah dan Madinah agar paket mudah dibaca jamaah.'],
        ['title' => '2. Alamat', 'description' => 'Isi alamat singkat yang mudah dikenali petugas dan jamaah.'],
        ['title' => '3. Lokasi peta', 'description' => 'Pilih titik dari peta agar koordinat tidak perlu dibuat manual.'],
    ];
    $fieldHelp = [
        'departures' => [
            'program_name' => 'Nama ini tampil di landing page, portal jamaah, pendaftaran, dan rombongan.',
            'description' => 'Gunakan bahasa singkat yang menjelaskan kelas paket, durasi, dan keunggulan utama.',
            'facilities' => 'Tulis satu fasilitas per baris, misalnya visa umroh, hotel, transportasi, manasik, dan pendamping.',
            'requirements' => 'Tulis satu persyaratan per baris, misalnya paspor, KTP, KK, buku nikah, dan vaksin jika diperlukan.',
            'departure_date' => 'Tanggal ini menjadi acuan paket tampil sebagai keberangkatan aktif.',
            'return_date' => 'Durasi paket dihitung otomatis dari tanggal berangkat sampai tanggal pulang.',
            'departure_airport' => 'Contoh: Jakarta CGK, Surabaya SUB, Makassar UPG, atau Banjarmasin BDJ.',
            'arrival_airport' => 'Contoh: Jeddah JED atau Madinah MED.',
            'airline' => 'Nama maskapai tampil di landing page dan detail paket.',
            'flight_number' => 'Isi jika nomor penerbangan sudah diketahui. Bisa dikosongkan saat paket masih draft.',
            'price' => 'Harga ini tampil sebagai harga paket. Kosongkan jika harga masih harus menghubungi cabang.',
            'quota' => 'Kuota dipakai untuk menghitung sisa kursi di landing page dan portal jamaah.',
            'is_public' => 'Aktifkan hanya jika paket sudah layak dilihat calon jamaah.',
            'status' => 'Gunakan Draft untuk persiapan, Terjadwal agar paket siap dipilih, dan Selesai setelah perjalanan ditutup.',
        ],
        'hotels' => [
            'name' => 'Nama hotel akan tampil pada paket, portal jamaah, dan detail rombongan.',
            'city' => 'Pilih kota hotel agar sistem bisa membedakan hotel Makkah dan Madinah.',
            'address' => 'Alamat membantu petugas memastikan titik peta sesuai lokasi sebenarnya.',
            'geofence_radius_meters' => 'Radius aman awal untuk area hotel. Umumnya 100-300 meter, sesuaikan kondisi sekitar.',
        ],
        'checkpoints' => [
            'name' => 'Gunakan nama yang mudah dipahami jamaah, misalnya Lobi Hotel, Gate 79, atau Titik Kumpul Bus.',
            'city' => 'Kota membantu mobile dan monitoring mengelompokkan titik tujuan.',
            'address' => 'Alamat boleh singkat; koordinat utama tetap dipilih dari peta.',
            'geofence_radius_meters' => 'Radius dipakai untuk membaca apakah jamaah berada di sekitar titik kumpul atau tujuan.',
            'description' => 'Isi petunjuk praktis, misalnya bertemu di lobi 15 menit sebelum jadwal berangkat.',
            'is_active' => 'Nonaktifkan titik yang sudah tidak dipakai agar tidak muncul di mobile.',
        ],
    ];
    $fields = match ($resource) {
        'branches' => [
            ['code','Kode Cabang','text'], ['name','Nama Cabang','text'], ['city','Kota','text'], ['province','Provinsi','text'],
            ['phone','WhatsApp Cabang','text'], ['email','Email','email'], ['address','Alamat','textarea'], ['is_active','Status','boolean'],
        ],
        'branch-admins' => [...$commonBranch,
            ['name','Nama Lengkap','text'], ['email','Email','email'], ['phone_number','Telepon','text'],
            ['photo','Foto Profil','file'],
            ['password','Password','password'], ['password_confirmation','Konfirmasi Password','password'], ['is_active','Status','boolean'],
        ],
        'pilgrims' => [...$commonBranch,
            ['group_id','Rombongan','select',$options['groups']],
            ['registration_number','Nomor Registrasi','text'], ['full_name','Nama Lengkap','text'], ['nik','NIK','text'],
            ['passport_number','Nomor Paspor','text'], ['passport_expired_at','Masa Berlaku Paspor','date'],
            ['gender','Jenis Kelamin','select',['male'=>'Laki-laki','female'=>'Perempuan']], ['phone','Telepon','text'],
            ['birth_date','Tanggal Lahir','date'], ['address','Alamat','textarea'],
            ['photo','Foto Jamaah','file'],
            ['status','Status','select',['registered'=>'Terdaftar','active'=>'Aktif','completed'=>'Selesai','cancelled'=>'Batal']],
        ],
        'tour-leaders' => [...$commonBranch,
            ['employee_number','Nomor Pegawai','text'], ['full_name','Nama Lengkap','text'], ['phone','Telepon','text'],
            ['email','Email Login Aplikasi','email'], ['password','Password Aplikasi','password'],
            ['password_confirmation','Konfirmasi Password','password'], ['photo','Foto Profil','file'], ['is_active','Status','boolean'],
        ],
        'muthawwifs' => [...$commonBranch,
            ['employee_number','Nomor Pegawai','text'], ['full_name','Nama Lengkap','text'], ['phone','Telepon','text'],
            ['email','Email Login Aplikasi','email'], ['password','Password Aplikasi','password'],
            ['password_confirmation','Konfirmasi Password','password'], ['photo','Foto Profil','file'],
            ['languages','Bahasa yang Dikuasai','textarea'], ['is_active','Status','boolean'],
        ],
        'hotels' => [...$commonBranch,
            ['name','Nama Hotel','text'], ['city','Kota','select',['makkah'=>'Makkah','madinah'=>'Madinah','other'=>'Lainnya']],
            ['address','Alamat','textarea'], ['latitude','Latitude','number'], ['longitude','Longitude','number'],
            ['geofence_radius_meters','Radius Geofence (meter)','number'],
        ],
        'checkpoints' => [...$commonBranch,
            ['departure_id','Khusus Paket Perjalanan','select',$options['departures']],
            ['group_id','Khusus Rombongan','select',$options['groups']],
            ['name','Nama Tujuan','text'],
            ['category','Kategori','select',[
                'ibadah'=>'Tempat Ibadah','hotel'=>'Hotel','titik_kumpul'=>'Titik Kumpul',
                'kesehatan'=>'Kesehatan','transportasi'=>'Transportasi','belanja'=>'Belanja','lainnya'=>'Lainnya',
            ]],
            ['city','Kota','select',['makkah'=>'Makkah','madinah'=>'Madinah','jeddah'=>'Jeddah','other'=>'Lainnya']],
            ['address','Alamat','textarea'], ['latitude','Latitude','number'], ['longitude','Longitude','number'],
            ['geofence_radius_meters','Radius Geofence (meter)','number'],
            ['description','Petunjuk Singkat','textarea'], ['is_active','Status','boolean'],
        ],
        'departures' => [...$commonBranch,
            ['code','Kode Paket','text'], ['program_name','Nama Paket','text'], ['description','Deskripsi Paket','textarea'],
            ['facilities','Fasilitas Paket','textarea'], ['requirements','Persyaratan Paket','textarea'],
            ['departure_date','Tanggal Berangkat','date'],
            ['return_date','Tanggal Pulang','date'], ['departure_airport','Bandara Berangkat','text'],
            ['arrival_airport','Bandara Kedatangan','text'], ['airline','Maskapai','text'],
            ['flight_number','Nomor Penerbangan','text'], ['price','Harga Paket','number'],
            ['hotel_ids','Hotel Makkah/Madinah','multiselect',$options['hotels']],
            ['itinerary_plan','Jadwal Perjalanan per Hari','itinerary'],
            ['quota','Kuota','number'], ['is_public','Tampil di Landing Page','boolean'],
            ['status','Status','select',['draft'=>'Draft','scheduled'=>'Terjadwal','departed'=>'Berangkat','completed'=>'Selesai','cancelled'=>'Batal']],
        ],
        'groups' => [...$commonBranch,
            ['departure_id','Paket Perjalanan','select',$options['departures']],
            ['tour_leader_id','Tour Leader','select',$options['tourLeaders']],
            ['muthawwif_id','Muthawwif','select',$options['muthawwifs']],
            ['code','Kode Rombongan','text'], ['name','Nama Rombongan','text'],
            ['capacity','Kapasitas','number'], ['notes','Catatan','textarea'], ['is_active','Status','boolean'],
        ],
    };
@endphp

<x-app-layout>
    <x-slot:title>{{ $editing ? 'Edit' : 'Tambah' }} {{ $definition['label'] }}</x-slot:title>
    <x-slot:header>
        <nav class="mb-1 flex items-center gap-1.5 text-xs font-medium text-slate-500">
            <a href="{{ route('master-data.index', $resource) }}" class="hover:text-blue-600">{{ $sectionLabel }}</a>
            <i data-lucide="chevron-right" class="size-3.5"></i>
            <a href="{{ route('master-data.index', $resource) }}" class="hover:text-blue-600">{{ $definition['label'] }}</a>
            <i data-lucide="chevron-right" class="size-3.5"></i>
            <span class="text-slate-700 dark:text-slate-300">{{ $editing ? 'Edit' : 'Tambah' }}</span>
        </nav>
        <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-[1.7rem]">{{ $editing ? 'Edit' : 'Tambah' }} {{ $definition['label'] }}</h1>
        <p class="mt-1 text-sm text-slate-500">Lengkapi informasi di bawah dengan data yang benar.</p>
    </x-slot:header>

    <form method="POST" action="{{ $editing ? route('master-data.update', [$resource, $record->id]) : route('master-data.store', $resource) }}"
          enctype="multipart/form-data"
          class="surface-card overflow-hidden">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="p-5 sm:p-7">
        @if (in_array($resource, ['tour-leaders', 'muthawwifs'], true))
            <div class="mb-7 flex gap-3 rounded-2xl border border-blue-200/80 bg-blue-50/70 p-4 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-100">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white text-blue-600 shadow-sm dark:bg-blue-950 dark:text-blue-300">
                    <i data-lucide="shield-check" class="size-4.5"></i>
                </span>
                <div>
                    <p class="font-semibold">Akun login aplikasi dibuat bersama data staf</p>
                    <p class="mt-1 leading-5 text-blue-700 dark:text-blue-300">
                        Email dan password digunakan untuk masuk ke aplikasi Mantau Umroh.
                        @if ($editing && $record->user_id)
                            Kosongkan password jika tidak ingin menggantinya.
                        @elseif ($editing)
                            Data lama ini belum mempunyai akun, sehingga password wajib diisi.
                        @endif
                    </p>
                </div>
            </div>
        @endif

        @if ($resource === 'departures')
            <section class="mb-7 rounded-2xl border border-blue-200 bg-blue-50/80 p-4 text-sm leading-6 text-blue-900 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-100">
                <div class="flex gap-3">
                    <i data-lucide="plane" class="mt-0.5 size-5 shrink-0 text-blue-700 dark:text-blue-300"></i>
                    <div>
                        <p class="font-bold">Paket ini menjadi sumber data landing page dan pilihan jamaah.</p>
                        <p class="mt-1">Aktifkan <strong>Tampil di Landing Page</strong> dan pilih status <strong>Terjadwal</strong> jika paket sudah siap dipilih calon jamaah. Hotel, pesawat, harga, kuota, dan jadwal harian akan dibaca dari data ini.</p>
                    </div>
                </div>
            </section>
            <section class="mb-7 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($departureGuide as $guide)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-sm font-extrabold text-slate-950 dark:text-white">{{ $guide['title'] }}</h2>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $guide['description'] }}</p>
                    </article>
                @endforeach
            </section>
        @elseif ($resource === 'hotels')
            <section class="mb-7 rounded-2xl border border-teal-200 bg-teal-50/80 p-4 text-sm leading-6 text-teal-950 dark:border-teal-900 dark:bg-teal-950/30 dark:text-teal-100">
                <div class="flex gap-3">
                    <i data-lucide="hotel" class="mt-0.5 size-5 shrink-0 text-teal-700 dark:text-teal-300"></i>
                    <div>
                        <p class="font-bold">Pilih lokasi hotel dari peta.</p>
                        <p class="mt-1">Koordinat hotel dipakai untuk marker monitoring dan dapat dipakai sebagai titik geofence. Admin tidak perlu mengarang latitude atau longitude.</p>
                    </div>
                </div>
            </section>
            <section class="mb-7 grid gap-3 md:grid-cols-3">
                @foreach ($hotelGuide as $guide)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-sm font-extrabold text-slate-950 dark:text-white">{{ $guide['title'] }}</h2>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $guide['description'] }}</p>
                    </article>
                @endforeach
            </section>
        @elseif ($resource === 'checkpoints')
            <section class="mb-7 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-sm leading-6 text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                <div class="flex gap-3">
                    <i data-lucide="map-pinned" class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-300"></i>
                    <div>
                        <p class="font-bold">Titik ini muncul di mobile sesuai paket atau rombongan.</p>
                        <p class="mt-1">Isi paket jika titik berlaku untuk semua jamaah paket tersebut. Isi rombongan jika titik hanya berlaku untuk rombongan tertentu, misalnya titik kumpul sebelum ziarah.</p>
                    </div>
                </div>
            </section>
            <section class="mb-7 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($checkpointGuide as $guide)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-sm font-extrabold text-slate-950 dark:text-white">{{ $guide['title'] }}</h2>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $guide['description'] }}</p>
                    </article>
                @endforeach
            </section>
        @endif

        <div class="grid gap-x-6 gap-y-5 md:grid-cols-2">
            @foreach ($fields as $field)
                @php
                    [$name, $label, $type] = $field;
                    $choices = $field[3] ?? [];
                    $default = $type === 'boolean' ? true : ($name === 'geofence_radius_meters' ? 250 : null);
                    $current = $value($name, $default);
                    $isAutomaticCode = $automaticCodeHelp !== null
                        && in_array($name, ['registration_number', 'employee_number', 'code'], true);
                @endphp
                @continue($hasLocationPicker && in_array($name, ['latitude', 'longitude'], true))
                <label class="{{ $type === 'textarea' ? 'md:col-span-2' : '' }}">
                    <span class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ $label }}
                        @if ($isAutomaticCode)
                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-700 dark:bg-blue-950 dark:text-blue-300">Otomatis</span>
                        @endif
                    </span>
                    @if ($isAutomaticCode)
                        <span class="control-field flex min-h-11 w-full items-center gap-2 bg-slate-50 text-slate-700 dark:bg-slate-800/70 dark:text-slate-200">
                            <i data-lucide="wand-sparkles" class="size-4 shrink-0 text-blue-600"></i>
                            <span class="{{ $editing ? 'font-mono font-semibold' : '' }}">
                                {{ $editing ? $current : 'Dibuat setelah data disimpan' }}
                            </span>
                        </span>
                        <span class="mt-1.5 block text-xs leading-5 text-slate-500">{{ $automaticCodeHelp }}</span>
                    @elseif ($type === 'file')
                        @if ($editing && $record->photo_path)
                            <img src="{{ asset('storage/'.$record->photo_path) }}" alt="Foto {{ $definition['label'] }}" class="mb-3 size-20 rounded-2xl object-cover">
                        @endif
                        <input type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp"
                               class="control-field w-full border p-2">
                        <span class="mt-1 block text-xs text-slate-500">JPG, PNG, atau WebP. Maksimal 2 MB.</span>
                    @elseif ($type === 'select')
                        @if ($resource === 'checkpoints' && $name === 'departure_id')
                            <div class="mb-2 rounded-xl bg-blue-50 p-3 text-xs leading-5 text-blue-900 dark:bg-blue-950/30 dark:text-blue-100">
                                Pilih paket jika titik ini harus dikirim ke semua jamaah dalam paket perjalanan tersebut.
                            </div>
                        @elseif ($resource === 'checkpoints' && $name === 'group_id')
                            <div class="mb-2 rounded-xl bg-violet-50 p-3 text-xs leading-5 text-violet-900 dark:bg-violet-950/30 dark:text-violet-100">
                                Pilih rombongan jika titik ini hanya dipakai oleh rombongan tertentu. Rombongan harus berasal dari paket yang sama.
                            </div>
                        @elseif ($resource === 'checkpoints' && $name === 'category')
                            <div class="mb-2 rounded-xl bg-amber-50 p-3 text-xs leading-5 text-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                                Kategori Titik Kumpul dan Hotel dipakai oleh geofence. Kategori lain tetap tampil sebagai tujuan/navigasi.
                            </div>
                        @endif
                        <select name="{{ $name }}" class="control-field w-full">
                            <option value="">Pilih {{ str($label)->lower() }}</option>
                            @foreach ($choices as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'multiselect')
                        @php $selectedValues = collect($current ?? [])->map(fn ($item) => (string) $item)->all(); @endphp
                        @if ($resource === 'departures' && $name === 'hotel_ids')
                            <div class="mb-2 rounded-xl bg-teal-50 p-3 text-xs leading-5 text-teal-900 dark:bg-teal-950/30 dark:text-teal-100">
                                Pilih minimal satu hotel Makkah dan satu hotel Madinah jika tersedia. Jika daftar kosong, buat data hotel terlebih dahulu di menu Hotel.
                            </div>
                        @endif
                        <select name="{{ $name }}[]" multiple class="control-field min-h-32 w-full">
                            @foreach ($choices as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selectedValues, true))>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                        <span class="mt-1.5 block text-xs leading-5 text-slate-500">
                            {{ $resource === 'departures' && $name === 'hotel_ids'
                                ? 'Pilih hotel yang dipakai paket ini, minimal hotel Makkah dan Madinah. Tahan Ctrl untuk memilih lebih dari satu hotel.'
                                : 'Tahan Ctrl untuk memilih lebih dari satu data.' }}
                        </span>
                    @elseif ($type === 'textarea')
                        <textarea name="{{ $name }}" rows="4" class="control-field w-full">{{ $current }}</textarea>
                    @elseif ($type === 'itinerary')
                        @if ($resource === 'departures')
                            <div class="mb-2 rounded-xl bg-amber-50 p-3 text-xs leading-5 text-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                                Jadwal harian boleh diisi bertahap. Nomor hari tidak boleh melebihi durasi paket dari tanggal berangkat sampai pulang.
                            </div>
                        @endif
                        <textarea name="{{ $name }}" rows="7" class="control-field w-full" placeholder="1|Berangkat dari Indonesia|Jeddah|Penerbangan dan proses imigrasi.&#10;2|Umroh pertama|Makkah|Thawaf, sai, dan tahallul.">{{ $current }}</textarea>
                        <span class="mt-1.5 block text-xs leading-5 text-slate-500">Format per baris: hari|judul kegiatan|kota|keterangan singkat. Contoh: 1|Berangkat dari Indonesia|Jeddah|Penerbangan dan proses imigrasi.</span>
                    @elseif ($type === 'boolean')
                        <select name="{{ $name }}" class="control-field w-full">
                            <option value="1" @selected((string) $current === '1')>Aktif</option>
                            <option value="0" @selected((string) $current === '0')>Nonaktif</option>
                        </select>
                    @else
                        <input type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : $current }}"
                               @if ($type === 'number') step="any" @endif
                               @if ($type === 'password') autocomplete="new-password" @endif
                               class="control-field w-full">
                    @endif
                    @if (filled($fieldHelp[$resource][$name] ?? null))
                        <span class="mt-1.5 block text-xs leading-5 text-slate-500">{{ $fieldHelp[$resource][$name] }}</span>
                    @endif
                    @error($name)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            @endforeach
        </div>

        @if ($hasLocationPicker)
            @php
                $latitudeValue = $value('latitude');
                $longitudeValue = $value('longitude');
                $pickerCity = $value('city', 'makkah');
            @endphp
            <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
                     data-location-picker
                     data-lat="{{ $latitudeValue }}"
                     data-lng="{{ $longitudeValue }}"
                     data-city="{{ $pickerCity }}">
                <input type="hidden" name="latitude" value="{{ $latitudeValue }}" data-location-lat>
                <input type="hidden" name="longitude" value="{{ $longitudeValue }}" data-location-lng>

                <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h2 class="flex items-center gap-2 text-lg font-bold text-slate-950 dark:text-white">
                                <i data-lucide="map-pinned" class="size-5 text-blue-600"></i>
                                Pilih Lokasi dari Peta
                            </h2>
                            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Klik titik pada peta atau cari nama tempat. Latitude dan longitude akan terisi otomatis dari pilihan peta.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="button-secondary min-h-10 px-3 text-xs" data-location-preset="makkah">Pusat Makkah</button>
                            <button type="button" class="button-secondary min-h-10 px-3 text-xs" data-location-preset="madinah">Pusat Madinah</button>
                            <button type="button" class="button-secondary min-h-10 px-3 text-xs" data-location-preset="jeddah">Pusat Jeddah</button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_auto]">
                        <label class="relative">
                            <span class="sr-only">Cari lokasi</span>
                            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400"></i>
                            <input type="search" data-location-search class="control-field w-full pl-10" placeholder="Cari hotel, masjid, bandara, atau alamat">
                        </label>
                        <button type="button" class="button-primary justify-center" data-location-search-button>
                            <i data-lucide="search" class="size-4"></i>
                            Cari Lokasi
                        </button>
                        <button type="button" class="button-secondary justify-center" data-location-current>
                            <i data-lucide="map" class="size-4"></i>
                            Lokasi Saya
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-slate-500" data-location-message>Pilih titik pada peta untuk mengisi koordinat.</p>
                </div>

                <div class="grid lg:grid-cols-[minmax(0,1fr)_280px]">
                    <div data-location-map class="h-[420px] min-h-[320px] bg-slate-100 dark:bg-slate-950"></div>
                    <aside class="border-t border-slate-200 p-5 dark:border-slate-800 lg:border-l lg:border-t-0">
                        <h3 class="text-sm font-bold text-slate-950 dark:text-white">Koordinat Terpilih</h3>
                        <div class="mt-4 space-y-3">
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Latitude</span>
                                <input readonly data-location-lat-display value="{{ $latitudeValue }}" class="control-field w-full bg-slate-50 font-mono text-sm dark:bg-slate-800">
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Longitude</span>
                                <input readonly data-location-lng-display value="{{ $longitudeValue }}" class="control-field w-full bg-slate-50 font-mono text-sm dark:bg-slate-800">
                            </label>
                        </div>
                        @error('latitude')<span class="mt-3 block text-xs text-red-600">{{ $message }}</span>@enderror
                        @error('longitude')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        <p class="mt-4 rounded-xl bg-blue-50 p-3 text-xs leading-5 text-blue-800 dark:bg-blue-950/40 dark:text-blue-200">Koordinat ini dipakai untuk titik tujuan di aplikasi jamaah, radius geofence, dan marker pada Live Map.</p>
                    </aside>
                </div>
            </section>
        @endif
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/70 sm:flex-row sm:justify-end sm:px-7">
            <a href="{{ route('master-data.index', $resource) }}" class="button-secondary">Batal</a>
            <button class="button-primary px-6">
                <i data-lucide="save" class="size-4"></i>
                Simpan Data
            </button>
        </div>
    </form>
</x-app-layout>
