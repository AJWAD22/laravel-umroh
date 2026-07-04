@php
    $editing = $record !== null;
    $value = fn (string $key, mixed $default = null) => old($key, data_get($record, $key, $default));
    $commonBranch = [['branch_id', 'Cabang', 'select', $options['branches']]];
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
            ['registration_number','Nomor Registrasi','text'], ['full_name','Nama Lengkap','text'], ['nik','NIK','text'],
            ['passport_number','Nomor Paspor','text'], ['passport_expired_at','Masa Berlaku Paspor','date'],
            ['gender','Jenis Kelamin','select',['male'=>'Laki-laki','female'=>'Perempuan']], ['phone','Telepon','text'],
            ['birth_date','Tanggal Lahir','date'], ['address','Alamat','textarea'],
            ['photo','Foto Jamaah','file'],
            ['status','Status','select',['registered'=>'Terdaftar','active'=>'Aktif','completed'=>'Selesai','cancelled'=>'Batal']],
        ],
        'tour-leaders' => [...$commonBranch,
            ['employee_number','Nomor Pegawai','text'], ['full_name','Nama Lengkap','text'], ['phone','Telepon','text'], ['photo','Foto Profil','file'], ['is_active','Status','boolean'],
        ],
        'muthawwifs' => [...$commonBranch,
            ['employee_number','Nomor Pegawai','text'], ['full_name','Nama Lengkap','text'], ['phone','Telepon','text'],
            ['photo','Foto Profil','file'], ['languages','Bahasa yang Dikuasai','textarea'], ['is_active','Status','boolean'],
        ],
        'hotels' => [...$commonBranch,
            ['name','Nama Hotel','text'], ['city','Kota','select',['makkah'=>'Makkah','madinah'=>'Madinah','other'=>'Lainnya']],
            ['address','Alamat','textarea'], ['latitude','Latitude','number'], ['longitude','Longitude','number'],
            ['geofence_radius_meters','Radius Geofence (meter)','number'],
        ],
        'departures' => [...$commonBranch,
            ['code','Kode Keberangkatan','text'], ['program_name','Nama Program','text'], ['departure_date','Tanggal Berangkat','date'],
            ['return_date','Tanggal Pulang','date'], ['departure_airport','Bandara Keberangkatan','text'],
            ['arrival_airport','Bandara Kedatangan','text'], ['quota','Kuota','number'],
            ['status','Status','select',['draft'=>'Draft','scheduled'=>'Terjadwal','departed'=>'Berangkat','completed'=>'Selesai','cancelled'=>'Batal']],
        ],
        'groups' => [...$commonBranch,
            ['departure_id','Keberangkatan','select',$options['departures']], ['tour_leader_id','Tour Leader','select',$options['tourLeaders']],
            ['muthawwif_id','Muthawwif','select',$options['muthawwifs']], ['code','Kode Rombongan','text'], ['name','Nama Rombongan','text'],
            ['capacity','Kapasitas','number'], ['notes','Catatan','textarea'], ['is_active','Status','boolean'],
        ],
    };
@endphp

<x-app-layout>
    <x-slot:title>{{ $editing ? 'Edit' : 'Tambah' }} {{ $definition['label'] }}</x-slot:title>
    <x-slot:header>
        <nav class="mb-2 text-sm text-slate-500">Master Data / {{ $definition['label'] }} / {{ $editing ? 'Edit' : 'Tambah' }}</nav>
        <h1 class="text-2xl font-bold">{{ $editing ? 'Edit' : 'Tambah' }} {{ $definition['label'] }}</h1>
    </x-slot:header>

    <form method="POST" action="{{ $editing ? route('master-data.update', [$resource, $record->id]) : route('master-data.store', $resource) }}"
          enctype="multipart/form-data"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 md:grid-cols-2">
            @foreach ($fields as $field)
                @php
                    [$name, $label, $type] = $field;
                    $choices = $field[3] ?? [];
                    $default = $type === 'boolean' ? true : ($name === 'geofence_radius_meters' ? 250 : null);
                    $current = $value($name, $default);
                @endphp
                <label class="{{ $type === 'textarea' ? 'md:col-span-2' : '' }}">
                    <span class="mb-1.5 block text-sm font-medium">{{ $label }}</span>
                    @if ($type === 'file')
                        @if ($editing && $record->photo_path)
                            <img src="{{ asset('storage/'.$record->photo_path) }}" alt="Foto {{ $definition['label'] }}" class="mb-3 size-20 rounded-2xl object-cover">
                        @endif
                        <input type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp"
                               class="w-full rounded-xl border border-slate-300 p-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <span class="mt-1 block text-xs text-slate-500">JPG, PNG, atau WebP. Maksimal 2 MB.</span>
                    @elseif ($type === 'select')
                        <select name="{{ $name }}" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="">Pilih {{ str($label)->lower() }}</option>
                            @foreach ($choices as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'textarea')
                        <textarea name="{{ $name }}" rows="3" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">{{ $current }}</textarea>
                    @elseif ($type === 'boolean')
                        <select name="{{ $name }}" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="1" @selected((string) $current === '1')>Aktif</option>
                            <option value="0" @selected((string) $current === '0')>Nonaktif</option>
                        </select>
                    @else
                        <input type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : $current }}"
                               @if ($type === 'number') step="any" @endif
                               class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    @endif
                    @error($name)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
            <a href="{{ route('master-data.index', $resource) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold">Batal</a>
            <button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</x-app-layout>
