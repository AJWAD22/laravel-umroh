# Umrah Monitoring Mobile

Aplikasi Flutter untuk role Jamaah, Tour Leader, dan Muthawwif. Login diarahkan
otomatis berdasarkan role yang dikembalikan API. Fitur Jamaah tetap menyediakan
tracking aktif, SOS, profil, dan peta hotel. Tour Leader dan Muthawwif dapat
melihat jamaah yang ditugaskan, lokasi terakhir, detail, dan laporan SOS.

## Persiapan backend

Di root project Laravel:

```powershell
php artisan migrate:fresh --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Akun demo (semua password `password`):

```text
Jamaah:      jamaah@umrah.test
Tour Leader: tourleader@umrah.test
Muthawwif:   muthawwif@umrah.test
```

## Base URL

Base URL dibaca dari compile-time config `API_BASE_URL`:

- HP Android via USB: `http://127.0.0.1:8000` dengan `adb reverse`
- Android Emulator: `http://10.0.2.2:8000`
- iOS Simulator: `http://127.0.0.1:8000`
- Perangkat fisik via Wi-Fi: gunakan IP LAN komputer, misalnya `http://192.168.1.10:8000`

Default project ini adalah `http://127.0.0.1:8000`, sehingga paling nyaman untuk
HP Android via USB.

HP Android via USB:

```powershell
adb devices
adb reverse tcp:8000 tcp:8000
flutter run
```

Android Emulator:

```powershell
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000
```

Perangkat fisik via Wi-Fi:

```powershell
flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8000
```

Pastikan firewall mengizinkan port 8000 dan perangkat berada pada jaringan yang sama.

## Pengujian

```powershell
flutter pub get
dart format lib test
flutter analyze
flutter test
```

## Batas implementasi

- Tracking hanya memakai timer saat aplikasi aktif.
- Tidak ada background service atau foreground service.
- Tour Leader dan Muthawwif bersifat read-only sesuai endpoint dan relasi backend.
- Endpoint lokasi Muthawwif dapat mengembalikan lokasi kosong sampai backend menerima telemetry staf.
- HTTP cleartext hanya disiapkan untuk development lokal. Produksi wajib memakai HTTPS dan menonaktifkan pengecualian cleartext/ATS.
