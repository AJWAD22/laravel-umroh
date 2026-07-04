# Skema Database Monitoring Jamaah

Tahap 2 menggunakan pemisahan tanggung jawab berikut:

- `users` menyimpan akun autentikasi web/mobile. Hak akses disimpan oleh Spatie Permission.
- `branches` menjadi tenant boundary untuk seluruh data operasional.
- `pilgrims`, `tour_leaders`, dan `muthawwifs` menyimpan profil domain dan dapat dihubungkan ke akun melalui `user_id`.
- `departures` menyimpan jadwal perjalanan, sedangkan hotel reusable dihubungkan melalui `departure_hotel`.
- `groups` terikat pada satu cabang dan keberangkatan. Anggota disimpan dalam `group_members`.
- `pilgrim_locations` menyimpan satu posisi terbaru per jamaah untuk live map.
- `location_histories` bersifat append-only untuk histori dan perhitungan jarak.
- `sos_reports` menyimpan snapshot koordinat saat SOS dibuat dan jejak penyelesaiannya.
- `notifications` mengikuti struktur polymorphic Laravel serta memiliki `branch_id` untuk filter tenant.

## Aturan integritas utama

- Data cabang menggunakan `restrictOnDelete` agar histori operasional tidak hilang tidak sengaja.
- Referensi petugas/akun opsional menggunakan `nullOnDelete`.
- Data turunan murni seperti anggota dan lokasi menggunakan `cascadeOnDelete`.
- Entitas master dan operasional utama menggunakan soft delete.
- Koordinat memakai `decimal`, bukan `float`, agar hasil penyimpanan stabil.
- Foreign key tidak menggantikan pemeriksaan kesamaan `branch_id`; konsistensi lintas cabang tetap wajib dijaga melalui Form Request, Policy, dan service pada tahap berikutnya.
