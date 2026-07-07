# Checklist Uji Lapangan Mobile Mantau Umroh

Gunakan checklist ini setiap kali APK baru dipasang di HP asli.

## 1. Persiapan

- Pastikan APK yang dipasang adalah build terbaru.
- Pastikan internet HP aktif.
- Pastikan izin lokasi aktif.
- Untuk tracking yang stabil, pilih izin lokasi paling longgar yang tersedia di perangkat.
- Matikan sementara mode hemat baterai untuk aplikasi Mantau Umroh saat uji tracking.

## 2. Uji FCM Petugas

1. Login sebagai Tour Leader atau Muthawwif di HP petugas.
2. Tunggu 5-10 detik setelah login agar token FCM tersimpan.
3. Login sebagai Jamaah di HP lain.
4. Jamaah menekan SOS.
5. Hasil normal:
   - Petugas menerima notifikasi SOS.
   - Saat notifikasi ditekan, aplikasi membuka detail SOS.
   - Detail SOS menampilkan nama jamaah, waktu, posisi, dan peta internal.

## 3. Uji Tracking Jamaah

1. Login sebagai Jamaah.
2. Buka dashboard jamaah.
3. Pastikan status tracking aktif.
4. Biarkan aplikasi berjalan minimal 3 menit.
5. Login sebagai petugas.
6. Buka menu Cari Jamaah atau Lokasi Jamaah.
7. Hasil normal:
   - Lokasi jamaah tampil.
   - Waktu lokasi terakhir bertambah sesuai update terbaru.
   - Jika jamaah bergerak, titik lokasi ikut berubah setelah beberapa siklus.

## 4. Uji Checkpoint

1. Admin membuat Tujuan & Titik Penting.
2. Isi salah satu sebagai umum cabang.
3. Isi salah satu khusus keberangkatan.
4. Isi salah satu khusus rombongan.
5. Login sebagai jamaah/petugas yang terkait.
6. Hasil normal:
   - Titik umum cabang tampil.
   - Titik khusus keberangkatan tampil jika pengguna ikut keberangkatan itu.
   - Titik khusus rombongan tampil jika pengguna terkait rombongan itu.
   - Peta internal menampilkan Titik Saya, Titik Tujuan, garis arah, dan jarak lurus.

## 5. Uji Penyelesaian SOS

1. Jamaah menekan SOS.
2. Petugas membuka detail SOS.
3. Petugas menekan tombol Jamaah Sudah Diamankan.
4. Hasil normal:
   - SOS hilang dari daftar aktif.
   - Dashboard admin tidak lagi menampilkan SOS tersebut sebagai aktif.
   - Riwayat SOS tetap tersimpan di laporan/monitoring.
