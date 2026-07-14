# Panduan Live Coding Mantau Umroh

Dokumen ini dibuat sebagai pegangan saat demonstrasi atau sidang. Komentar pada kode menjelaskan **file apa**, **fungsi utamanya**, **data yang masuk**, dan **hasil yang dikeluarkan**.

## 1. Gambaran alur sistem

```text
Pengguna membuka web/APK
        |
Login atau aktivasi PIN
        |
Laravel memvalidasi akun dan hak akses
        |
Data dibaca/ditulis ke MySQL
        |
APK mengirim lokasi dan token FCM
        |
Web/petugas melihat lokasi dan laporan SOS
```

## 2. Cara membaca folder

| Folder/file | Kegunaan |
|---|---|
| `routes/web.php` | Daftar alamat halaman website admin. |
| `routes/api.php` | Daftar endpoint yang dipanggil APK. |
| `app/Http/Controllers` | Menerima request, memanggil service, lalu mengembalikan halaman/JSON. |
| `app/Services` | Tempat aturan bisnis agar controller tetap sederhana. |
| `app/Models` | Representasi tabel database dan relasinya. |
| `app/Http/Requests` | Validasi data dari form atau API. |
| `database/migrations` | Struktur tabel database. |
| `database/seeders` | Data awal, role, pengaturan, dan data demo. |
| `resources/views` | Tampilan website Blade. |
| `mobile_jamaah/lib/core` | Konfigurasi, jaringan, tema, penyimpanan, dan notifikasi APK. |
| `mobile_jamaah/lib/features` | Fitur APK berdasarkan modul: auth, tracking, SOS, staff, dan lainnya. |

## 3. Alur fitur utama

### Login website

1. Pengguna mengisi email dan password pada `resources/views/auth/login.blade.php`.
2. Route login memanggil controller autentikasi Laravel.
3. `LoginRequest` memvalidasi input.
4. Laravel memeriksa akun, status aktif, dan role.
5. Pengguna diarahkan ke dashboard sesuai hak akses.

### Data master

1. Admin membuka menu Data Master.
2. `MasterDataController` menerima resource yang dipilih.
3. `MasterDataService` menjalankan proses tambah, ubah, hapus, dan relasi.
4. Model menyimpan data ke tabel MySQL.
5. View menampilkan hasil terbaru.

### Import Excel jamaah

1. Admin mengunduh template dari menu Jamaah.
2. File dikirim ke `MasterDataController`.
3. `SpreadsheetArrayImport` membaca baris Excel.
4. `MasterDataImportService` memvalidasi cabang, rombongan, NIK, dan paspor.
5. Data jamaah baru dibuat atau data lama diperbarui.

### Tracking lokasi

1. APK meminta izin lokasi.
2. `TrackingProvider` mengambil koordinat berkala.
3. Repository mengirim data melalui `ApiClient` ke endpoint tracking.
4. Laravel menyimpan lokasi terakhir dan riwayat lokasi.
5. `MonitoringService` menentukan status online/offline untuk Live Map.

### SOS

1. Jamaah menekan tombol SOS pada APK.
2. API menyimpan laporan pada tabel `sos_reports`.
3. `AdminNotificationService` mengirim notifikasi ke petugas/admin.
4. Petugas membuka detail dan melihat koordinat jamaah.
5. Petugas mengakui atau menyelesaikan laporan.
6. Jamaah menerima informasi bahwa SOS sedang ditangani.

## 4. Perintah live coding yang aman

Jalankan dari folder project Laravel:

```powershell
cd D:\laragon\www\laravel_umroh
php artisan route:list
php artisan migrate:status
php artisan test
```

Untuk melihat alur APK:

```powershell
cd D:\laragon\www\laravel_umroh\mobile_jamaah
flutter pub get
flutter analyze
```

Jelaskan bahwa `route:list` menampilkan alamat endpoint, `migrate:status` menampilkan status struktur database, `test` menguji backend, dan `flutter analyze` memeriksa kesalahan Dart tanpa menjalankan aplikasi.

## 5. Kamus sintaks yang sering ditanya

### Laravel/PHP

| Sintaks | Arti |
|---|---|
| `Route::get('/alamat', ...)` | Membuat alamat halaman dengan metode HTTP GET. |
| `Route::post('/alamat', ...)` | Menerima data kiriman form atau APK. |
| `middleware([...])` | Pemeriksaan sebelum request diteruskan, misalnya login dan role. |
| `Controller` | Pengatur alur request dan response. |
| `Service` | Tempat aturan bisnis dan proses yang dapat dipakai beberapa controller. |
| `Model::query()` | Memulai query ke tabel yang diwakili model. |
| `->where(...)` | Menyaring data berdasarkan kondisi. |
| `->with(...)` | Mengambil relasi model agar data terkait tersedia. |
| `DB::transaction(...)` | Menjamin beberapa perubahan database berhasil bersama-sama atau dibatalkan bersama-sama. |
| `Request`/`FormRequest` | Data masukan pengguna dan aturan validasinya. |
| `Migration` | Kode pembentuk atau pengubah tabel database. |
| `Seeder` | Kode pengisi role, pengaturan, atau data contoh. |
| `private readonly` | Dependency hanya dapat diisi saat object dibuat dan tidak diganti lagi. |

### Flutter/Dart

| Sintaks | Arti |
|---|---|
| `class ... extends ChangeNotifier` | State aplikasi yang dapat memberi tahu widget saat data berubah. |
| `notifyListeners()` | Meminta widget yang mendengarkan provider menggambar ulang tampilannya. |
| `Future<void>` | Proses asynchronous yang selesai kemudian, misalnya request API. |
| `async`/`await` | Menjalankan proses asynchronous tanpa membekukan tampilan. |
| `final` | Nilai hanya dapat diisi satu kali setelah object dibuat. |
| `const` | Nilai tetap yang dapat dibuat saat compile time. |
| `String.fromEnvironment` | Membaca nilai build-time seperti `API_BASE_URL`. |
| `Repository` | Lapisan yang khusus berkomunikasi dengan API atau penyimpanan lokal. |
| `Provider` | Cara membagikan state dan dependency ke widget Flutter. |
| `Navigator` | Perpindahan antarhalaman APK. |

Saat live coding, jelaskan pola sederhananya: **route menerima request → controller mengatur alur → service menjalankan aturan bisnis → model mengakses database → view/API mengirim hasil**.

## 6. Push, deploy, dan build APK

### Perubahan web/backend

```powershell
cd D:\laragon\www\laravel_umroh
git status
git add .
git commit -m "Jelaskan perubahan"
git push origin main
```

Hostinger mengambil commit dari GitHub secara otomatis. Setelah deploy selesai:

```bash
cd ~/domains/mantauumroh.web.id/public_html
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
```

### Perubahan APK

Perubahan di `mobile_jamaah/` harus dibuat menjadi APK baru:

```powershell
cd D:\laragon\www\laravel_umroh\mobile_jamaah
flutter build apk --release --dart-define=API_BASE_URL=https://mantauumroh.web.id
Copy-Item build\app\outputs\flutter-apk\app-release.apk build\app\outputs\flutter-apk\Mantau-Umroh.apk -Force
```

Jika hanya backend atau tampilan web yang berubah, APK tidak perlu dibuild ulang.

## 7. Aturan keamanan saat menjelaskan kode

- Jangan menampilkan isi `.env`, password database, atau Firebase service account.
- Jangan mengedit folder `vendor`, `node_modules`, `build`, `storage`, atau `.git`.
- Jangan menjalankan `db:seed --force` pada data produksi tanpa memastikan isi seeder.
- Jika ada error 500, baca bagian terakhir `storage/logs/laravel.log`.

## 8. Kalimat singkat saat sidang

> Website Laravel berfungsi sebagai pusat pengelolaan data dan monitoring, sedangkan aplikasi Flutter digunakan jamaah dan petugas di lapangan. Laravel menyediakan API untuk login, aktivasi PIN, pengiriman lokasi, dan SOS. Data disimpan di MySQL, lokasi ditampilkan menggunakan Leaflet/OpenStreetMap, dan notifikasi dikirim melalui Firebase Cloud Messaging.
