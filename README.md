# Mantau Umroh

Mantau Umroh adalah sistem monitoring jamaah umroh berbasis web dan aplikasi Android.

Sederhananya:

- Web dipakai oleh Super Admin dan Admin Cabang.
- APK dipakai oleh Jamaah, Tour Leader, dan Muthawwif.
- Jamaah mengirim lokasi dari HP.
- Petugas dan admin bisa melihat posisi jamaah di peta.
- Jika Jamaah menekan SOS, petugas dan admin bisa melihat laporan daruratnya.

Dokumen ini dibuat sebagai pegangan praktis supaya alur sistem, push, deploy, build APK, dan input data tidak membingungkan.

Panduan penjelasan kode dan simulasi live coding tersedia di [docs/PANDUAN_LIVE_CODING.md](docs/PANDUAN_LIVE_CODING.md).

Supaya folder lebih mudah dicari di VS Code, buka file
`mantau-umroh.code-workspace` melalui menu **File > Open Workspace from File**.
Workspace tersebut memisahkan tampilan Backend Laravel, Route/API, Frontend Web,
Frontend Flutter, Database, Test, dan Dokumentasi. Penjelasan susunannya tersedia
di [docs/STRUKTUR_SOURCE_CODE.md](docs/STRUKTUR_SOURCE_CODE.md).

---

## 1. Struktur Sistem

### Web Laravel

Folder utama:

```text
app/
database/
resources/
routes/
public/
```

Fungsi web:

- Login admin.
- Mengelola Data Master.
- Melihat dashboard.
- Melihat Live Map.
- Melihat laporan.
- Import data Jamaah dari Excel.

### Aplikasi Android Flutter

Folder:

```text
mobile_jamaah/
```

Fungsi APK:

- Jamaah aktivasi memakai PIN.
- Jamaah mengirim lokasi.
- Jamaah mengirim SOS.
- Tour Leader dan Muthawwif melihat jamaah.
- Petugas melihat laporan SOS.

---

## 2. Role Pengguna

### Super Admin

Mengelola sistem secara nasional.

Bisa melihat:

- Semua cabang.
- Semua admin cabang.
- Semua data jamaah.
- Semua rombongan.
- Monitoring semua cabang.

### Admin Cabang

Mengelola data untuk cabangnya sendiri.

Bisa melihat dan mengelola:

- Jamaah cabangnya.
- Tour Leader cabangnya.
- Muthawwif cabangnya.
- Rombongan cabangnya.
- Monitoring cabangnya.

### Tour Leader

Dipakai di APK.

Fungsi utama:

- Melihat jamaah dalam rombongan.
- Melihat lokasi jamaah.
- Menerima dan menangani SOS jamaah.

### Muthawwif

Dipakai di APK.

Fungsi utama:

- Melihat jamaah yang menjadi tanggung jawabnya.
- Melihat lokasi jamaah.
- Membantu menangani SOS.

### Jamaah

Dipakai di APK.

Fungsi utama:

- Aktivasi memakai PIN.
- Mengirim lokasi otomatis.
- Menekan tombol SOS jika butuh bantuan.

---

## 3. Data Master

Data Master yang dipakai:

```text
Jamaah
Tour Leader
Muthawwif
Rombongan
```

Data organisasi:

```text
Data Cabang
Akun Admin Cabang
```

Catatan penting:

- Import Excel hanya untuk data Jamaah.
- Data cabang, admin cabang, Tour Leader, Muthawwif, dan Rombongan sebaiknya dibuat manual dari form web.
- Alasannya: data tersebut punya relasi dan akun login, jadi lebih aman dibuat satu per satu.

---

## 4. Import Excel Jamaah

Import Excel hanya ada di:

```text
Data Master > Jamaah
```

Langkah penggunaan:

1. Login web sebagai Super Admin atau Admin Cabang.
2. Buka menu `Data Master > Jamaah`.
3. Klik `Template Excel`.
4. Isi data jamaah di file template.
5. Klik `Import Jamaah`.
6. Pilih file Excel.
7. Sistem akan menambah atau memperbarui data jamaah.

Kolom template:

```text
cabang
rombongan
nama
nik
nomor_paspor
masa_berlaku_paspor
jenis_kelamin
telepon
tanggal_lahir
alamat
status
```

Contoh isi:

```text
cabang: BJM
rombongan: Rombongan Al-Ikhlas
nama: Ahmad Fauzi
nik: 6371010101700001
nomor_paspor: A1234567
masa_berlaku_paspor: 2030-12-31
jenis_kelamin: laki-laki
telepon: 081234567892
tanggal_lahir: 1970-01-01
alamat: Banjarmasin
status: registered
```

Catatan:

- Untuk Admin Cabang, kolom `cabang` boleh tetap diisi, tetapi sistem tetap memakai cabang akun admin tersebut.
- Untuk Super Admin, kolom `cabang` wajib benar.
- Kolom `rombongan` boleh kosong.
- Jika `rombongan` diisi, rombongan harus sudah dibuat lebih dulu.
- Jika NIK atau nomor paspor sudah ada, sistem memperbarui data jamaah lama.
- Jika belum ada, sistem membuat data jamaah baru.
- PIN aktivasi dibuat otomatis untuk jamaah baru.

---

## 5. Aktivasi Jamaah di APK

Alur aktivasi:

1. Admin membuat atau mengimport data Jamaah.
2. Sistem membuat PIN aktivasi.
3. Jamaah membuka APK.
4. Jamaah memasukkan PIN.
5. Aktivasi langsung diproses otomatis.

Catatan:

- PIN tetap dipakai.
- Tidak perlu menunggu izin Tour Leader.
- PIN bisa diperbarui dari web jika diperlukan.

Saat PIN diperbarui, semua token login dan perangkat aktif milik jamaah tersebut
dicabut. APK akan menerima `401 Unauthorized`, menghapus sesi lokal, kembali ke
halaman login/aktivasi, dan menghentikan tracking. Riwayat lokasi yang sudah
tersimpan tidak dihapus. Snapshot lokasi aktif dihapus agar marker lama tidak
tetap tampil di Live Map; setelah aktivasi PIN baru dan pengiriman lokasi
pertama, marker akan muncul kembali.

## 5a. Tujuan Umum dan Titik Kumpul

- Admin mengelola tujuan umum melalui menu `Data Pendukung Sistem > Tujuan & Titik Penting`.
- Tour Leader atau Muthawwif dapat membuat titik kumpul dari APK untuk rombongan yang menjadi tanggung jawabnya.
- Petugas dapat mengubah nama, keterangan, dan—jika sedang berada di lokasi baru—koordinat titik kumpul.
- Petugas dapat menonaktifkan titik kumpul yang sudah tidak dipakai.
- Jamaah dan petugas hanya menerima titik yang berstatus aktif. Daftar berubah setelah aplikasi di-refresh.

---

## 6. Live Map dan Status Online

Live Map menampilkan lokasi terakhir jamaah.

Status:

- `Online`: jamaah baru saja mengirim lokasi.
- `Offline`: lokasi terakhir sudah melewati batas waktu.
- `SOS`: jamaah sedang meminta bantuan.

Batas offline diatur dari:

```text
Pengaturan > Sistem
```

Setting:

```text
Batas GPS Offline (menit)
```

Default:

```text
10 menit
```

Artinya:

- Jika jamaah login tetapi APK belum mengirim lokasi, belum tentu online.
- Login tidak sama dengan GPS online.
- Online dihitung dari data lokasi terakhir.

Jika Live Map tidak update:

1. Pastikan GPS HP aktif.
2. Pastikan izin lokasi aplikasi aktif.
3. Pastikan internet HP aktif.
4. Buka APK selama 30–60 detik.
5. Klik `Muat Ulang` di Live Map.

---

## 7. SOS

Alur SOS:

1. Jamaah menekan tombol SOS di APK.
2. Sistem menyimpan laporan SOS.
3. Admin dan petugas bisa melihat laporan SOS.
4. Petugas membuka detail SOS.
5. Petugas melihat titik lokasi jamaah di peta.
6. Setelah aman, laporan SOS bisa ditandai selesai.

Konsep penting:

- SOS dibuat sesingkat mungkin.
- Saat darurat, petugas tidak perlu banyak klik.
- Fokusnya adalah menemukan posisi jamaah.

---

## 8. Push ke GitHub

Push berarti mengirim perubahan dari laptop ke GitHub.

Jalankan di PowerShell:

```powershell
cd D:\laragon\www\laravel_umroh
git status
git add .
git commit -m "Tulis pesan perubahan di sini"
git push origin main
```

Contoh pesan commit:

```powershell
git commit -m "Perbaiki status online live map"
```

Jika muncul:

```text
nothing to commit
```

Artinya tidak ada perubahan baru yang perlu dikirim.

Jika muncul:

```text
Changes not staged for commit
```

Artinya belum menjalankan:

```powershell
git add .
```

---

## 9. Deploy ke Hostinger

Karena Hostinger sudah terhubung ke GitHub, setelah `git push` biasanya deploy berjalan otomatis.

Setelah deploy selesai, masuk SSH:

```bash
ssh -p 65002 u799496565@145.223.108.10
```

Lalu masuk folder project:

```bash
cd ~/domains/mantauumroh.web.id/public_html
```

Perintah aman setelah deploy biasa:

```bash
php artisan optimize:clear
php artisan optimize
```

Jika ada perubahan database atau migration:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

Jika perlu mengisi ulang data demo:

```bash
php artisan db:seed --force
```

Hati-hati:

- `db:seed --force` bisa mengubah/menghapus data demo sesuai isi seeder.
- Jangan jalankan seeder sembarangan jika data asli sudah dipakai.

---

## 10. Build APK Android

APK perlu dibuild ulang hanya jika ada perubahan di folder:

```text
mobile_jamaah/
```

Contoh perubahan yang butuh build ulang APK:

- Tampilan APK berubah.
- Login APK berubah.
- Map APK berubah.
- SOS APK berubah.
- Tracking APK berubah.
- Nama aplikasi/icon berubah.
- API URL APK berubah.

Perintah build:

```powershell
cd D:\laragon\www\laravel_umroh\mobile_jamaah
flutter build apk --release --dart-define=API_BASE_URL=https://mantauumroh.web.id
Copy-Item build\app\outputs\flutter-apk\app-release.apk build\app\outputs\flutter-apk\Mantau-Umroh.apk -Force
```

File APK hasil build:

```text
mobile_jamaah/build/app/outputs/flutter-apk/Mantau-Umroh.apk
```

Jika hanya web/backend yang berubah, APK tidak perlu dibuild ulang.

---

## 11. Perintah Cek Saat Error

Jika web muncul:

```text
500 Server Error
```

Jalankan di SSH:

```bash
cd ~/domains/mantauumroh.web.id/public_html
tail -n 80 storage/logs/laravel.log
```

Kirim bagian paling bawah log ke developer/Codex.

Perintah bersih cache:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize
```

Jika route tidak ditemukan:

```bash
php artisan route:list
```

Jika ingin cek migration:

```bash
php artisan migrate:status
```

---

## 12. Perintah Cek Lokal

Jalankan di laptop:

```powershell
cd D:\laragon\www\laravel_umroh
php artisan test
```

Jika hasilnya:

```text
passed
```

Artinya test backend aman.

Untuk cek daftar route:

```powershell
php artisan route:list
```

Untuk membersihkan cache lokal:

```powershell
php artisan optimize:clear
```

---

## 13. Cara Menjelaskan Sistem Saat Presentasi

Kalimat sederhana:

> Mantau Umroh adalah sistem monitoring jamaah umroh. Admin mengelola data jamaah dan rombongan melalui web. Jamaah memakai aplikasi Android untuk aktivasi PIN, mengirim lokasi, dan mengirim SOS. Petugas dapat melihat posisi jamaah dan menangani SOS melalui aplikasi.

Alur singkat:

1. Admin membuat cabang dan akun admin cabang.
2. Admin cabang membuat Tour Leader dan Muthawwif.
3. Admin cabang membuat Rombongan.
4. Admin cabang input atau import data Jamaah.
5. Jamaah aktivasi APK memakai PIN.
6. APK mengirim lokasi jamaah.
7. Admin dan petugas melihat lokasi jamaah.
8. Jika jamaah butuh bantuan, jamaah menekan SOS.
9. Petugas melihat titik jamaah dan menangani laporan SOS.

---

## 14. Catatan Penting untuk Pengembangan

Jangan ubah file `.env` sembarangan.

Jangan upload data rahasia ke GitHub, misalnya:

- Password database.
- Firebase service account JSON.
- API key rahasia.

Jika mengubah backend/web:

- Push ke GitHub.
- Tunggu Hostinger deploy.
- Bersihkan cache Laravel.

Jika mengubah APK:

- Build ulang APK.
- Install ulang APK ke HP.

Jika data peta tidak muncul:

- Pastikan HP mengirim lokasi.
- Pastikan izin lokasi aktif.
- Pastikan data rombongan jamaah benar.
