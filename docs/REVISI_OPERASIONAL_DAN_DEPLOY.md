# Revisi Operasional Mantau Umroh

Dokumen ini menjelaskan perubahan hak akses, alur registrasi, tracking, PIN
aktivasi, serta langkah penerapan ke hosting.

## Hak akses utama

| Fitur | Super Admin | Admin Cabang | Tour Leader | Muthawwif | Jamaah |
|---|---|---|---|---|---|
| Dashboard | Ringkasan nasional | Operasional cabang | Ringkasan rombongan di APK | Ringkasan rombongan di APK | Status perjalanan di portal/APK |
| Cabang dan akun Admin Cabang | Kelola | Tidak | Tidak | Tidak | Tidak |
| Paket dan jadwal perjalanan | Lihat nasional | Kelola cabang | Lihat rombongan | Lihat rombongan | Pilih paket publik |
| Registrasi paket | Lihat nasional | Verifikasi dan setujui | Tidak | Tidak | Daftar dan isi biodata |
| Hotel, titik tujuan, titik kumpul | Lihat nasional | Kelola cabang | Lihat di APK | Lihat di APK | Lihat sesuai perjalanan |
| Rombongan dan jamaah | Lihat nasional | Kelola cabang | Lihat rombongan sendiri | Lihat rombongan sendiri | Lihat data sendiri |
| PIN aktivasi | Tidak | Buat/reset per jamaah atau rombongan | Tidak | Tidak | Gunakan selama perjalanan |
| Live map, histori tracking, SOS | Tidak | Kelola cabang sendiri | Kirim lokasi dan tangani rombongan | Kirim lokasi dan tangani rombongan | Kirim lokasi dan SOS |
| Laporan | Agregat nasional | Laporan cabang | Tidak | Tidak | Tidak |

Super Admin tidak menerima notifikasi lokasi individual dan tidak dapat membuka
live map, histori tracking, histori SOS, ataupun mereset PIN jamaah.

## Alur operasional

1. Jamaah membuat satu akun melalui landing page.
2. Jamaah login ke portal dan memilih paket perjalanan.
3. Jamaah mengisi biodata dan mengirim pendaftaran.
4. Jamaah melakukan pembayaran langsung di cabang travel.
5. Admin Cabang memverifikasi pembayaran, memilih rombongan yang memakai paket
   tersebut, lalu menyetujui registrasi.
6. Sistem membuat atau menghubungkan data jamaah operasional, memasukkannya ke
   rombongan, dan menghasilkan PIN aktivasi.
7. Jamaah menggunakan akun dan PIN itu pada APK selama perjalanan.
8. Titik tujuan/titik kumpul yang dibuat Admin Cabang tampil di APK apabila
   cabang, paket perjalanan, atau rombongannya sesuai.
9. Saat perjalanan selesai atau dibatalkan, token, perangkat aktif, snapshot
   lokasi, sesi aktivasi, dan PIN dicabut. Riwayat lokasi tetap disimpan untuk
   laporan.

## Persiapan sebelum deploy

Jalankan dari folder proyek lokal.

```powershell
git status --short
php artisan test
npm ci
npm run build
cd mobile_jamaah
flutter analyze
cd ..
```

Pastikan pengujian berhasil sebelum melakukan commit dan push.

## Deploy ke hosting

Backup database dan `.env` terlebih dahulu. Kemudian masuk ke folder aplikasi
yang benar:

```bash
cd /home/u799496565/domains/mantauumroh.web.id/public_html
pwd
git status --short
git pull origin main
php artisan down
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Seeder `RolePermissionSeeder` wajib dijalankan agar role lama memperoleh hak
akses terbaru dan hak monitoring Super Admin dicabut.

Jika hosting tidak menyediakan Node.js, jalankan `npm ci` dan `npm run build` di
komputer lokal, commit folder `public/build`, lalu lakukan `git pull` di hosting.

## Worker dan scheduler

Tracking real-time membutuhkan proses queue dan scheduler yang tetap berjalan.
Atur cron hosting:

```cron
* * * * * cd /home/u799496565/domains/mantauumroh.web.id/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Jika hosting memiliki process manager, jalankan worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Setelah deploy atau perubahan kode, restart worker:

```bash
php artisan queue:restart
```

Untuk hosting tanpa process manager, buat cron worker sesuai fasilitas penyedia
hosting dan batasi waktu prosesnya.

## Pemeriksaan setelah deploy

- Login Super Admin: dashboard nasional dan laporan dapat dibuka; live map,
  histori tracking, histori SOS, serta reset PIN menghasilkan `403`.
- Login Admin Cabang: hanya data cabangnya yang terlihat.
- Buat titik kumpul untuk paket/rombongan, lalu cek endpoint titik tujuan di APK
  jamaah yang menjadi anggota rombongan tersebut.
- Setujui satu registrasi dengan pembayaran `verified` dan pilih rombongan;
  pastikan data jamaah, anggota rombongan, dan PIN terbentuk.
- Kirim lokasi dari APK; pastikan marker berubah di live map Admin Cabang.
- Uji keluar radius geofence; pastikan notifikasi hanya diterima Admin Cabang dan
  petugas rombongan.
- Reset PIN satu rombongan; pastikan PIN lama tidak dapat dipakai.
- Ubah status perjalanan menjadi `completed`; pastikan pengiriman lokasi dan SOS
  ditolak dan token APK dicabut.

## Pemulihan jika deploy gagal

Jangan menghapus database. Kembalikan commit aplikasi ke commit stabil melalui
prosedur deployment yang digunakan tim, jalankan `composer install`, bersihkan
cache Laravel, lalu hidupkan aplikasi kembali. Jika migrasi baru sudah dijalankan,
evaluasi migrasinya satu per satu sebelum melakukan rollback di server produksi.
