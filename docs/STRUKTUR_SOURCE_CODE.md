# Peta Source Code Mantau Umroh

Struktur Laravel dan Flutter tidak dipindahkan karena mengikuti standar
framework. Gunakan peta berikut saat mencari kode.

## Backend Laravel

| Kebutuhan | Lokasi |
|---|---|
| Controller website admin | `app/Http/Controllers/` |
| Controller API Flutter | `app/Http/Controllers/Api/Mobile/` |
| Validasi request | `app/Http/Requests/` |
| Logika bisnis | `app/Services/` |
| Hak akses per data | `app/Policies/` |
| Model database | `app/Models/` |
| Notifikasi | `app/Notifications/` |

## Route

| Route | Lokasi |
|---|---|
| Website admin | `routes/web.php` |
| API aplikasi Flutter | `routes/api.php` |
| Login dan reset password | `routes/auth.php` |
| Scheduler/command | `routes/console.php` |
| Broadcasting | `routes/channels.php` |

## Frontend

| Frontend | Lokasi |
|---|---|
| Blade website admin | `resources/views/` |
| CSS dan JavaScript web | `resources/css/`, `resources/js/` |
| Aplikasi Flutter | `mobile_jamaah/lib/` |
| Konfigurasi Android | `mobile_jamaah/android/` |

## Database dan pengujian

| Kebutuhan | Lokasi |
|---|---|
| Struktur tabel | `database/migrations/` |
| Data awal/demo | `database/seeders/` |
| Factory test | `database/factories/` |
| Test Laravel | `tests/` |
| Dokumentasi | `docs/` |

## Alur cepat membaca fitur

Untuk mengikuti satu fitur, baca dengan urutan:

1. Route pada `routes/web.php` atau `routes/api.php`.
2. Method controller yang dituju route.
3. Form Request untuk validasi.
4. Service untuk logika bisnis.
5. Model untuk penyimpanan database.
6. Blade atau repository Flutter sebagai pemanggil endpoint.

Folder `vendor`, `node_modules`, `.dart_tool`, `build`, dan cache framework
adalah hasil instalasi/generasi. Folder tersebut bukan tempat menulis fitur.
