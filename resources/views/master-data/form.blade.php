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
            $resource === 'pilgrims' && $key === 'payment_status'
                => optional($record?->user?->portalRegistrations?->sortByDesc('updated_at')?->first())->payment_status ?? $default,
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
        in_array($resource, ['branch-admins', 'pilgrims', 'tour-leaders', 'muthawwifs'], true) => 'Data Master',
        in_array($resource, ['departures', 'hotels', 'checkpoints', 'groups'], true) => 'Operasional Perjalanan',
        $resource === 'branches' => 'Organisasi',
        default => 'Data',
    };
    $hasLocationPicker = in_array($resource, ['hotels', 'checkpoints'], true);
    $fieldHelp = [
        'departures' => [
            'cover_image' => 'Gunakan foto paket yang jelas dan relevan. Foto tampil di landing page dan halaman detail paket.',
            'departure_airport' => 'Contoh: Banjarmasin BDJ.',
            'flight_number' => 'Isi jika nomor penerbangan sudah diketahui.',
            'is_public' => 'Tampil di landing page jika status paket Terjadwal.',
            'status' => 'Gunakan Terjadwal saat paket sudah siap dipilih.',
        ],
        'hotels' => [
            'geofence_radius_meters' => 'Umumnya 100-300 meter.',
        ],
        'checkpoints' => [
            'name' => 'Gunakan nama yang mudah dipahami jamaah, misalnya Lobi Hotel, Gate 79, atau Titik Kumpul Bus.',
            'geofence_radius_meters' => 'Umumnya 100-300 meter.',
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
            ['group_id','Paket/Rombongan','select',$options['groups']],
            ['payment_status','Status Pembayaran','select',['unpaid'=>'Belum bayar','down_payment'=>'DP','paid'=>'Lunas','verified'=>'Terverifikasi']],
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
            ['cover_image','Foto Sampul Paket','file'],
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
            <p class="mb-5 rounded-xl bg-blue-50 px-4 py-3 text-xs font-semibold text-blue-800 dark:bg-blue-950/30 dark:text-blue-200">
                Email dan password dipakai untuk login aplikasi. Kosongkan password saat edit jika tidak diganti.
            </p>
        @endif

        <div class="grid gap-x-6 gap-y-5 md:grid-cols-2">
            @foreach ($fields as $field)
                @php
                    [$name, $label, $type] = $field;
                    $choices = $field[3] ?? [];
                    $default = match (true) {
                        $resource === 'departures' && $name === 'is_public' => false,
                        $resource === 'departures' && $name === 'status' => 'draft',
                        $type === 'boolean' => true,
                        $name === 'geofence_radius_meters' => 250,
                        default => null,
                    };
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
                        @php
                            $storedImagePath = $editing
                                ? ($name === 'cover_image' ? $record->cover_image_path : $record->photo_path)
                                : null;
                        @endphp
                        @if ($storedImagePath)
                            <img src="{{ asset('storage/'.$storedImagePath) }}" alt="{{ $label }}" class="mb-3 h-32 w-full max-w-sm rounded-2xl object-cover">
                        @endif
                        <input type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp"
                               class="control-field w-full border p-2">
                        <span class="mt-1 block text-xs text-slate-500">
                            JPG, PNG, atau WebP. Maksimal {{ $name === 'cover_image' ? '3' : '2' }} MB.
                            @if ($editing && $storedImagePath) Kosongkan jika tidak ingin mengganti gambar. @endif
                        </span>
                    @elseif ($type === 'select')
                        <select name="{{ $name }}" class="control-field w-full">
                            <option value="">Pilih {{ str($label)->lower() }}</option>
                            @foreach ($choices as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'multiselect')
                        @php $selectedValues = collect($current ?? [])->map(fn ($item) => (string) $item)->all(); @endphp
                        <select name="{{ $name }}[]" multiple class="control-field min-h-32 w-full">
                            @foreach ($choices as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selectedValues, true))>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                        <span class="mt-1.5 block text-xs leading-5 text-slate-500">
                            {{ $resource === 'departures' && $name === 'hotel_ids'
                                ? 'Pilih hotel paket. Tahan Ctrl untuk memilih lebih dari satu hotel.'
                                : 'Tahan Ctrl untuk memilih lebih dari satu data.' }}
                        </span>
                    @elseif ($type === 'textarea')
                        <textarea name="{{ $name }}" rows="4" class="control-field w-full">{{ $current }}</textarea>
                    @elseif ($type === 'itinerary')
                        <textarea name="{{ $name }}" rows="7" class="control-field w-full" placeholder="1|Berangkat dari Indonesia|Jeddah|Penerbangan dan proses imigrasi.&#10;2|Umroh pertama|Makkah|Thawaf, sai, dan tahallul.">{{ $current }}</textarea>
                        <span class="mt-1.5 block text-xs leading-5 text-slate-500">Format: hari|judul|kota|keterangan.</span>
                    @elseif ($type === 'boolean')
                        @php
                            $booleanCurrent = filter_var($current, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        @endphp
                        <select name="{{ $name }}" class="control-field w-full">
                            <option value="1" @selected($booleanCurrent === true)>Aktif</option>
                            <option value="0" @selected($booleanCurrent !== true)>Nonaktif</option>
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
                $rawLatitudeValue = $value('latitude');
                $rawLongitudeValue = $value('longitude');
                $hasRealCoordinate = filled($rawLatitudeValue)
                    && filled($rawLongitudeValue)
                    && ! ((float) $rawLatitudeValue === 0.0 && (float) $rawLongitudeValue === 0.0);
                $latitudeValue = $hasRealCoordinate ? $rawLatitudeValue : '';
                $longitudeValue = $hasRealCoordinate ? $rawLongitudeValue : '';
                $pickerCity = $value('city', 'makkah');
            @endphp
            <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
                     data-location-picker
                     data-location-search-url="{{ route('locations.search') }}"
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
                            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Cari lokasi atau klik peta untuk menentukan titik.</p>
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
                        <h3 class="text-sm font-bold text-slate-950 dark:text-white">Hasil Pilihan Peta</h3>
                        <div class="mt-4 space-y-3">
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Latitude otomatis</span>
                                <input readonly data-location-lat-display value="{{ $latitudeValue }}" class="control-field w-full bg-slate-50 font-mono text-sm dark:bg-slate-800">
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Longitude otomatis</span>
                                <input readonly data-location-lng-display value="{{ $longitudeValue }}" class="control-field w-full bg-slate-50 font-mono text-sm dark:bg-slate-800">
                            </label>
                        </div>
                        @error('latitude')<span class="mt-3 block text-xs text-red-600">{{ $message }}</span>@enderror
                        @error('longitude')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        <p class="mt-4 rounded-xl bg-blue-50 p-3 text-xs leading-5 text-blue-800 dark:bg-blue-950/40 dark:text-blue-200">Lokasi ini dipakai untuk titik tujuan di aplikasi jamaah, radius geofence, dan marker pada Live Map.</p>
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
