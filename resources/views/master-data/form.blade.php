@php
    $editing = $record !== null;
    $value = fn (string $key, mixed $default = null) => old(
        $key,
        $key === 'email' && in_array($resource, ['tour-leaders', 'muthawwifs'], true)
            ? data_get($record, 'user.email', $default)
            : ($resource === 'pilgrims' && $key === 'group_id'
                ? data_get($record?->groupMemberships?->firstWhere('status', 'active'), 'group_id', $default)
                : data_get($record, $key, $default))
    );
    $commonBranch = [['branch_id', 'Cabang', 'select', $options['branches']]];
    $automaticCodeHelp = match ($resource) {
        'pilgrims' => 'Nomor registrasi dibuat otomatis, contoh BJM-JMH-00001.',
        'tour-leaders' => 'Nomor pegawai dibuat otomatis, contoh BJM-TL-001.',
        'muthawwifs' => 'Nomor pegawai dibuat otomatis, contoh BJM-MTF-001.',
        'departures' => 'Kode dibuat dari cabang dan tahun keberangkatan, contoh BJM-DEP-2026-001.',
        'groups' => 'Kode dibuat otomatis dari cabang, contoh BJM-GRP-001.',
        default => null,
    };
    $sectionLabel = match (true) {
        in_array($resource, ['branch-admins', 'pilgrims', 'tour-leaders', 'muthawwifs', 'groups'], true) => 'Data Master',
        in_array($resource, ['departures', 'hotels', 'checkpoints'], true) => 'Data Pendukung Sistem',
        $resource === 'branches' => 'Organisasi',
        default => 'Data',
    };
    $fields = match ($resource) {
        'branches' => [
            ['code','Kode Cabang','text'], ['name','Nama Cabang','text'], ['city','Kota','text'], ['province','Provinsi','text'],
            ['phone','Telepon','text'], ['email','Email','email'], ['address','Alamat','textarea'], ['is_active','Status','boolean'],
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
            ['departure_id','Khusus Keberangkatan','select',$options['departures']],
            ['group_id','Khusus Rombongan','select',$options['groups']],
            ['name','Nama Tujuan','text'],
            ['category','Kategori','select',[
                'ibadah'=>'Tempat Ibadah','hotel'=>'Hotel','titik_kumpul'=>'Titik Kumpul',
                'kesehatan'=>'Kesehatan','transportasi'=>'Transportasi','belanja'=>'Belanja','lainnya'=>'Lainnya',
            ]],
            ['city','Kota','select',['makkah'=>'Makkah','madinah'=>'Madinah','jeddah'=>'Jeddah','other'=>'Lainnya']],
            ['address','Alamat','textarea'], ['latitude','Latitude','number'], ['longitude','Longitude','number'],
            ['description','Petunjuk Singkat','textarea'], ['is_active','Status','boolean'],
        ],
        'departures' => [...$commonBranch,
            ['code','Kode Keberangkatan','text'], ['program_name','Nama Program','text'], ['departure_date','Tanggal Berangkat','date'],
            ['return_date','Tanggal Pulang','date'], ['departure_airport','Bandara Keberangkatan','text'],
            ['arrival_airport','Bandara Kedatangan','text'], ['quota','Kuota','number'],
            ['status','Status','select',['draft'=>'Draft','scheduled'=>'Terjadwal','departed'=>'Berangkat','completed'=>'Selesai','cancelled'=>'Batal']],
        ],
        'groups' => [...$commonBranch,
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
                        <select name="{{ $name }}" class="control-field w-full">
                            <option value="">Pilih {{ str($label)->lower() }}</option>
                            @foreach ($choices as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'textarea')
                        <textarea name="{{ $name }}" rows="4" class="control-field w-full">{{ $current }}</textarea>
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
                    @error($name)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            @endforeach
        </div>
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
