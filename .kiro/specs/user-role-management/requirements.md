# Requirements Document

## Introduction

Fitur **Sistem Manajemen User & Role** adalah inti dari aplikasi travel umroh berbasis Laravel ini. Sistem ini mengatur identitas, hak akses, dan kemampuan operasional dari lima jenis pengguna: Super Admin, Admin Cabang, Tour Leader, Muthawwif, dan Jamaah. Setiap role memiliki scope data dan izin aksi yang berbeda — dari kontrol penuh lintas cabang oleh Super Admin, isolasi ketat per wilayah oleh Admin Cabang, pemantauan kloter oleh Tour Leader dan Muthawwif, hingga interaksi GPS dan SOS oleh Jamaah melalui aplikasi mobile.

Sistem ini juga mencakup entitas operasional perjalanan umroh: **Keberangkatan** (jadwal keberangkatan dan kepulangan per cabang), **Group/Rombongan** (pengelompokan Jamaah beserta penugasan Tour Leader dan Muthawwif), dan **Hotel** (data penginapan per keberangkatan yang ditampilkan di peta). Alur penanganan SOS juga diperluas dengan kemampuan resolusi oleh petugas, termasuk pencatatan histori SOS yang lengkap.

Sistem ini bertumpu pada tabel `users` (dengan kolom `role` dan `branch_id`), tabel `branches`, tabel `locations`, tabel `departures`, tabel `groups`, tabel `group_jamaah`, dan tabel `hotels` di database.

---

## Glossary

- **System**: Keseluruhan aplikasi Laravel travel umroh
- **AuthManager**: Komponen yang menangani proses autentikasi (login/logout) menggunakan Laravel Sanctum
- **AccessControl**: Komponen middleware yang memverifikasi role dan izin akses setiap request
- **UserManager**: Komponen yang mengelola operasi CRUD akun pengguna
- **BranchManager**: Komponen yang mengelola operasi CRUD kantor cabang
- **DashboardController**: Komponen yang menyajikan data statistik ke halaman dashboard
- **MapController**: Komponen yang menyajikan data lokasi ke halaman peta GIS
- **LocationTracker**: Komponen API yang menerima dan menyimpan data koordinat GPS dari Jamaah
- **SOSHandler**: Komponen yang memproses sinyal darurat dari Jamaah
- **NotificationDispatcher**: Komponen yang mengirim notifikasi push kepada Muthawwif
- **Super_Admin**: Pengguna dengan role `super_admin` — pemilik/direksi pusat, akses penuh tanpa batas cabang
- **Admin_Cabang**: Pengguna dengan role `admin` — staf kantor cabang, akses terbatas pada `branch_id` miliknya
- **Tour_Leader**: Pengguna dengan role `tour_leader` — pendamping kloter, akses terbatas pada kloter yang dipimpinnya
- **Muthawwif**: Pengguna dengan role `muthawwif` — pembimbing ibadah lapangan, akses real-time ke lokasi jamaah bimbingannya
- **Jamaah**: Pengguna dengan role `jamaah` — peserta umroh, hanya berinteraksi via aplikasi mobile (tidak mengakses web admin)
- **Branch**: Kantor cabang yang direpresentasikan oleh tabel `branches` (kolom: `id`, `name_branch`, `city`)
- **Kloter**: Rombongan jamaah yang dipimpin oleh satu Tour_Leader
- **Grup_Bimbingan**: Kelompok jamaah yang dibimbing oleh satu Muthawwif
- **SOS**: Sinyal darurat yang dikirim Jamaah, menandai `is_sos = true` di tabel `locations`
- **GPS_Koordinat**: Pasangan nilai `latitude` dan `longitude` yang dikirim dari perangkat Jamaah
- **Peta_GIS**: Tampilan peta digital berbasis Leaflet.js yang menampilkan pin lokasi jamaah secara real-time
- **Sanctum_Token**: Token autentikasi berbasis Laravel Sanctum yang diberikan setelah login berhasil
- **GPS_Payload**: Paket data GPS lengkap yang dikirim dari Flutter_App, berisi `latitude`, `longitude`, `accuracy`, `speed`, `heading`, `battery`, dan `timestamp`
- **WebSocket_Channel**: Saluran komunikasi dua arah berbasis Laravel Broadcasting (Pusher/Reverb) yang digunakan untuk mendorong event realtime ke browser dan aplikasi mobile tanpa polling
- **RealtimeUpdater**: Komponen sisi server (Event & Listener) yang mem-broadcast perubahan posisi Jamaah dan event SOS melalui WebSocket_Channel
- **Geofence**: Area geografis virtual berbentuk poligon atau lingkaran yang ditentukan oleh administrator; sistem menghasilkan alert jika Jamaah melewati batas area tersebut
- **GeofenceMonitor**: Komponen yang memeriksa posisi Jamaah terhadap batas Geofence setiap kali GPS_Payload diterima
- **Background_Service**: Layanan sistem operasi pada perangkat Flutter (Android Foreground Service / iOS Background Task) yang menjaga pengiriman GPS_Payload tetap berjalan meskipun Flutter_App diminimalkan
- **Flutter_App**: Aplikasi mobile lintas platform (Android & iOS) yang digunakan oleh Jamaah untuk login, mengirim GPS_Payload, menekan SOS, dan melihat peta
- **HistoryTracker**: Komponen yang menyimpan dan menyajikan histori pergerakan Jamaah dari tabel `locations`
- **OnlineStatusResolver**: Komponen yang menentukan status online/offline Jamaah berdasarkan selisih waktu antara `now()` dengan `timestamp` kiriman GPS_Payload terakhir
- **ServiceLayer**: Lapisan kelas PHP (Laravel Service) yang mengelola business logic, dipisahkan dari controller
- **RepositoryLayer**: Lapisan kelas PHP (Laravel Repository) yang mengelola semua query database, dipisahkan dari Service
- **QueueWorker**: Proses antrean Laravel Queue yang menjalankan job-job berat secara asinkron (broadcast SOS, pengiriman notifikasi)
- **Notification_Threshold**: Ambang waktu (dalam menit) yang digunakan OnlineStatusResolver untuk memutuskan apakah seorang Jamaah dianggap offline
- **Keberangkatan**: Jadwal perjalanan umroh yang direpresentasikan oleh tabel `departures`; memiliki tanggal keberangkatan, tanggal kepulangan, nama program, dan `branch_id` penyelenggara
- **Group**: Rombongan Jamaah dalam satu Keberangkatan, direpresentasikan oleh tabel `groups`; memiliki nama group, `departure_id`, `tour_leader_id`, dan `muthawwif_id` yang ditugaskan
- **GroupManager**: Komponen yang mengelola operasi CRUD Group/Rombongan, termasuk penugasan Tour Leader, Muthawwif, dan anggota Jamaah
- **HotelManager**: Komponen yang mengelola data hotel per Keberangkatan, termasuk menyimpan koordinat GPS hotel untuk ditampilkan di peta
- **SOSResolver**: Komponen yang menangani proses penyelesaian (resolusi) sinyal SOS oleh petugas, termasuk mencatat waktu resolusi dan identitas petugas yang menyelesaikan
- **AssignmentManager**: Komponen yang mengelola penugasan Tour Leader dan Muthawwif ke Group, serta penugasan Jamaah ke dalam Group
- **Hotel**: Data penginapan untuk setiap Keberangkatan, direpresentasikan oleh tabel `hotels`; memiliki nama hotel, alamat, `latitude`, `longitude`, dan `departure_id`
- **SOS_Histori**: Catatan lengkap penanganan sinyal SOS yang tersimpan permanen, mencakup waktu sinyal, posisi Jamaah, waktu resolusi, dan identitas petugas yang menyelesaikan

---

## Requirements

---

### Requirement 1: Autentikasi Pengguna

**User Story:** Sebagai pengguna sistem (semua role), saya ingin dapat masuk dan keluar dari sistem dengan aman, sehingga identitas dan hak akses saya terverifikasi sebelum menggunakan fitur apapun.

#### Acceptance Criteria

1. WHEN seorang pengguna mengirimkan email dan password yang valid, THE AuthManager SHALL memverifikasi kredensial terhadap tabel `users` dan menghasilkan Sanctum_Token yang unik untuk sesi tersebut.
2. WHEN seorang pengguna mengirimkan email atau password yang tidak valid, THE AuthManager SHALL menolak permintaan login dan mengembalikan pesan kesalahan `"Kredensial tidak valid."`.
3. WHEN seorang pengguna yang sudah login mengirimkan permintaan logout, THE AuthManager SHALL menghapus Sanctum_Token aktif dari tabel `personal_access_tokens` sehingga token tersebut tidak dapat digunakan kembali.
4. WHEN sebuah request HTTP diterima tanpa Sanctum_Token yang valid di header `Authorization`, THE AccessControl SHALL menolak request tersebut dengan HTTP status `401 Unauthorized`.
5. WHEN seorang pengguna berhasil login, THE AuthManager SHALL menyertakan data `role` dan `branch_id` pengguna tersebut dalam respons autentikasi.

---

### Requirement 2: Kontrol Akses Berbasis Role (Role-Based Access Control)

**User Story:** Sebagai administrator sistem, saya ingin setiap pengguna hanya dapat mengakses fitur yang sesuai dengan role-nya, sehingga tidak ada akses yang tidak sah ke data atau fungsi yang bukan haknya.

#### Acceptance Criteria

1. WHEN sebuah request masuk ke route yang dilindungi, THE AccessControl SHALL memeriksa role dari Sanctum_Token pengguna sebelum meneruskan request ke controller.
2. IF pengguna dengan role `tour_leader`, `muthawwif`, atau `jamaah` mencoba mengakses route yang hanya diizinkan untuk `super_admin` atau `admin`, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
3. IF pengguna dengan role `jamaah` mencoba mengakses halaman mana pun di web admin panel, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
4. THE System SHALL mendefinisikan matriks izin sebagai berikut:
   - `super_admin`: akses penuh ke semua route dan semua data lintas cabang
   - `admin`: akses ke route manajemen staf dan jamaah, dibatasi hanya pada `branch_id` miliknya
   - `tour_leader`: akses baca ke data manifest jamaah dalam kloternya dan peta kloternya
   - `muthawwif`: akses baca ke data lokasi real-time jamaah dalam Grup_Bimbingannya dan penerimaan notifikasi SOS
   - `jamaah`: akses hanya ke API GPS, API SOS, dan endpoint peta terbatas

---

### Requirement 3: Isolasi Data Per Cabang (Branch Scoping)

**User Story:** Sebagai Admin Cabang, saya ingin data yang saya kelola terbatas hanya pada wilayah cabang saya, sehingga data cabang lain tidak dapat saya lihat atau ubah secara tidak sengaja maupun disengaja.

#### Acceptance Criteria

1. WHILE pengguna dengan role `admin` sedang mengakses sistem, THE UserManager SHALL memfilter semua query daftar pengguna dengan kondisi `WHERE branch_id = {branch_id pengguna yang login}`.
2. WHILE pengguna dengan role `admin` sedang mengakses sistem, THE DashboardController SHALL menghitung statistik (total jamaah, staf) hanya dari pengguna yang memiliki `branch_id` yang sama dengan Admin_Cabang yang login.
3. WHILE pengguna dengan role `admin` sedang mengakses sistem, THE MapController SHALL mengembalikan data GPS_Koordinat hanya dari jamaah yang memiliki `branch_id` yang sama dengan Admin_Cabang yang login.
4. IF pengguna dengan role `admin` mengirimkan request untuk membaca, mengubah, atau menghapus data pengguna yang memiliki `branch_id` berbeda, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
5. THE UserManager SHALL memastikan bahwa saat Admin_Cabang membuat akun baru, nilai `branch_id` pada akun baru tersebut secara otomatis diset sama dengan `branch_id` dari Admin_Cabang yang login, terlepas dari nilai `branch_id` yang dikirimkan dalam request body.

---

### Requirement 4: Manajemen Kantor Cabang oleh Super Admin

**User Story:** Sebagai Super Admin, saya ingin dapat menambah, mengubah, dan menghapus data kantor cabang, sehingga struktur organisasi perusahaan dapat saya kelola secara terpusat.

#### Acceptance Criteria

1. WHEN Super_Admin mengirimkan request pembuatan cabang baru dengan data `name_branch` dan `city` yang valid, THE BranchManager SHALL menyimpan data tersebut ke tabel `branches` dan mengembalikan data cabang yang baru dibuat beserta HTTP status `201 Created`.
2. WHEN Super_Admin mengirimkan request pembaruan data cabang dengan `id` cabang yang valid, THE BranchManager SHALL memperbarui kolom `name_branch` dan/atau `city` di tabel `branches` untuk cabang tersebut.
3. WHEN Super_Admin mengirimkan request penghapusan cabang dengan `id` cabang yang valid, THE BranchManager SHALL menghapus data cabang dari tabel `branches`.
4. IF pengguna dengan role `admin`, `tour_leader`, `muthawwif`, atau `jamaah` mengirimkan request untuk membuat, mengubah, atau menghapus data cabang, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
5. THE BranchManager SHALL menyediakan endpoint daftar semua cabang yang dapat diakses oleh Super_Admin dan Admin_Cabang (hanya untuk keperluan tampilan dan penugasan).

---

### Requirement 5: Manajemen Akun oleh Super Admin (Lingkup Nasional)

**User Story:** Sebagai Super Admin, saya ingin dapat membuat dan mengelola semua akun pengguna dari seluruh cabang di Indonesia, sehingga saya memiliki kontrol penuh atas seluruh sumber daya manusia dalam sistem.

#### Acceptance Criteria

1. WHEN Super_Admin mengirimkan request pembuatan akun baru dengan data `name`, `email`, `password`, `role`, dan `branch_id` yang valid, THE UserManager SHALL menyimpan akun tersebut ke tabel `users` dengan password yang di-hash menggunakan bcrypt.
2. WHEN Super_Admin mengirimkan request pembaruan akun dengan `id` pengguna yang valid, THE UserManager SHALL memperbarui data `name`, `email`, `role`, `branch_id`, dan/atau `phone_number` untuk akun tersebut.
3. WHEN Super_Admin mengirimkan request penghapusan akun dengan `id` pengguna yang valid, THE UserManager SHALL menghapus akun tersebut dari tabel `users`.
4. THE UserManager SHALL memvalidasi bahwa nilai `email` bersifat unik di seluruh tabel `users` sebelum menyimpan akun baru.
5. THE UserManager SHALL memvalidasi bahwa nilai `role` yang dikirimkan merupakan salah satu dari enum yang valid: `super_admin`, `admin`, `tour_leader`, `muthawwif`, atau `jamaah`.
6. THE UserManager SHALL memvalidasi bahwa `branch_id` yang dikirimkan merujuk pada ID yang ada di tabel `branches`, kecuali untuk akun dengan role `super_admin` di mana `branch_id` boleh bernilai `null`.
7. THE UserManager SHALL memvalidasi bahwa `password` memiliki panjang minimal 8 karakter sebelum menyimpan akun baru.
8. IF Super_Admin mengirimkan request pembuatan akun dengan `email` yang sudah terdaftar, THEN THE UserManager SHALL menolak request tersebut dan mengembalikan pesan kesalahan `"Email sudah digunakan."`.

---

### Requirement 6: Manajemen Akun oleh Admin Cabang (Lingkup Lokal)

**User Story:** Sebagai Admin Cabang, saya ingin dapat membuat dan mengelola akun Tour Leader, Muthawwif, dan Jamaah khusus untuk cabang saya, sehingga operasional cabang dapat saya urus secara mandiri.

#### Acceptance Criteria

1. WHEN Admin_Cabang mengirimkan request pembuatan akun baru dengan data yang valid dan role target `tour_leader`, `muthawwif`, atau `jamaah`, THE UserManager SHALL menyimpan akun tersebut dengan `branch_id` yang otomatis diset sama dengan `branch_id` milik Admin_Cabang yang sedang login.
2. IF Admin_Cabang mengirimkan request pembuatan akun dengan role `super_admin` atau `admin`, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
3. WHEN Admin_Cabang mengirimkan request pembaruan akun untuk pengguna yang berada dalam `branch_id` yang sama, THE UserManager SHALL memperbarui data `name`, `email`, `phone_number` pengguna tersebut.
4. IF Admin_Cabang mengirimkan request untuk mengubah nilai `role` menjadi `super_admin` atau `admin`, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
5. WHEN Admin_Cabang mengirimkan request penghapusan akun untuk pengguna yang berada dalam `branch_id` yang sama dengan role `tour_leader`, `muthawwif`, atau `jamaah`, THE UserManager SHALL menghapus akun tersebut dari tabel `users`.

---

### Requirement 7: Dashboard Statistik Super Admin (Lingkup Nasional)

**User Story:** Sebagai Super Admin, saya ingin melihat statistik agregat seluruh operasional di dashboard, sehingga saya dapat memantau kondisi bisnis secara keseluruhan tanpa harus masuk ke setiap cabang.

#### Acceptance Criteria

1. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah seluruh cabang yang terdaftar di tabel `branches`.
2. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah seluruh pengguna dengan role `jamaah` di seluruh cabang.
3. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah seluruh pengguna dengan role `muthawwif` di seluruh cabang.
4. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah seluruh pengguna dengan role `tour_leader` di seluruh cabang.
5. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah seluruh pengguna dengan role `admin` di seluruh cabang.
6. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah Jamaah yang berstatus online, yaitu Jamaah yang memiliki setidaknya satu record di tabel `locations` dengan `created_at` dalam rentang Notification_Threshold menit terakhir, dihitung dari seluruh cabang.
7. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah Jamaah yang berstatus offline, yaitu seluruh Jamaah terdaftar dikurangi jumlah Jamaah online, dihitung dari seluruh cabang.
8. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah SOS Aktif, yaitu jumlah record di tabel `locations` dengan nilai `is_sos = true` yang belum berstatus resolved, dihitung dari seluruh cabang.
9. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menyediakan data agregasi jumlah Jamaah per cabang dalam format JSON yang dapat dirender sebagai grafik batang atau pie chart di sisi browser.

---

### Requirement 8: Dashboard Statistik Admin Cabang (Lingkup Lokal)

**User Story:** Sebagai Admin Cabang, saya ingin melihat statistik operasional khusus untuk cabang saya di dashboard, sehingga saya dapat memantau kondisi cabang tanpa terganggu data dari cabang lain.

#### Acceptance Criteria

1. WHEN Admin_Cabang mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah Jamaah yang memiliki `branch_id` sama dengan `branch_id` Admin_Cabang yang login.
2. WHEN Admin_Cabang mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah Muthawwif yang memiliki `branch_id` sama dengan `branch_id` Admin_Cabang yang login.
3. WHEN Admin_Cabang mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah Tour_Leader yang memiliki `branch_id` sama dengan `branch_id` Admin_Cabang yang login.
4. WHILE Admin_Cabang sedang mengakses halaman dashboard, THE DashboardController SHALL menyembunyikan data statistik cabang lain sehingga hanya statistik cabang miliknya yang tampil.
5. WHEN Admin_Cabang mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah Jamaah yang berstatus online dalam cabangnya, yaitu Jamaah dengan record `locations` terbaru dalam rentang Notification_Threshold menit terakhir dan memiliki `branch_id` yang sama.
6. WHEN Admin_Cabang mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah Jamaah yang berstatus offline dalam cabangnya, yaitu seluruh Jamaah cabang dikurangi jumlah Jamaah online cabang.
7. WHEN Admin_Cabang mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah SOS Aktif dalam cabangnya, yaitu jumlah record di tabel `locations` dengan nilai `is_sos = true` yang belum berstatus resolved dan `user_id`-nya memiliki `branch_id` yang sama dengan Admin_Cabang yang login.
8. WHEN Admin_Cabang mengakses halaman dashboard, THE DashboardController SHALL menyediakan data ringkasan per Group/Rombongan dalam cabangnya, termasuk nama group, nama Tour_Leader yang ditugaskan, nama Muthawwif yang ditugaskan, dan jumlah Jamaah dalam group tersebut.

---

### Requirement 9: Peta GIS — Monitoring Global oleh Super Admin

**User Story:** Sebagai Super Admin, saya ingin melihat posisi real-time seluruh jamaah dari semua cabang pada satu peta terpadu, sehingga saya dapat memantau kondisi lapangan di seluruh Indonesia secara bersamaan.

#### Acceptance Criteria

1. WHEN Super_Admin mengakses halaman peta, THE MapController SHALL mengembalikan data GPS_Payload terbaru (record `locations` terbaru per `user_id`) dari seluruh Jamaah di semua cabang.
2. WHEN Super_Admin mengakses halaman peta, THE MapController SHALL menyertakan data `name`, `branch.name_branch`, `is_sos`, `latitude`, `longitude`, `accuracy`, `speed`, `heading`, `battery`, dan `created_at` untuk setiap marker yang ditampilkan.
3. WHEN data JSON lokasi diterima oleh Peta_GIS di sisi browser, THE Peta_GIS SHALL merender marker untuk setiap Jamaah pada koordinat `latitude` dan `longitude` yang sesuai.
4. WHEN sebuah marker mewakili Jamaah dengan `is_sos = true`, THE Peta_GIS SHALL menampilkan marker tersebut dengan warna merah berkedip untuk membedakannya dari marker normal.
5. WHEN Peta_GIS menerima event WebSocket_Channel dari RealtimeUpdater, THE Peta_GIS SHALL memperbarui posisi marker Jamaah yang bersangkutan secara langsung tanpa memuat ulang halaman.
6. WHEN Super_Admin mengklik sebuah marker di Peta_GIS, THE Peta_GIS SHALL menampilkan popup detail berisi `name`, `status online/offline`, `battery`, `speed`, `accuracy`, dan waktu kiriman terakhir.
7. WHEN Super_Admin mengaktifkan filter cabang pada Peta_GIS, THE Peta_GIS SHALL menampilkan hanya marker Jamaah yang memiliki `branch_id` sesuai filter yang dipilih.
8. WHEN Super_Admin mengaktifkan filter grup pada Peta_GIS, THE Peta_GIS SHALL menampilkan hanya marker Jamaah yang tergabung dalam kloter atau Grup_Bimbingan sesuai filter yang dipilih.
9. WHEN Super_Admin mengaktifkan filter status pada Peta_GIS, THE Peta_GIS SHALL menampilkan hanya marker Jamaah dengan status yang dipilih (online, offline, atau SOS).
10. WHEN Super_Admin mengaktifkan fitur Live Tracking untuk seorang Jamaah, THE Peta_GIS SHALL menggambar jejak garis polilyne pada peta berdasarkan histori GPS_Payload Jamaah tersebut.
11. WHEN Super_Admin mengakses halaman peta, THE Peta_GIS SHALL menampilkan marker Hotel pada koordinat hotel yang dikonfigurasi untuk setiap kloter.
12. WHEN Super_Admin mengakses halaman peta, THE Peta_GIS SHALL menampilkan marker Muthawwif berdasarkan posisi terkini setiap Muthawwif yang aktif.

---

### Requirement 10: Peta GIS — Monitoring Lokal oleh Admin Cabang

**User Story:** Sebagai Admin Cabang, saya ingin melihat posisi real-time jamaah yang berasal dari cabang saya saja pada peta, sehingga saya dapat memantau keselamatan jamaah cabang saya tanpa informasi yang tidak relevan.

#### Acceptance Criteria

1. WHEN Admin_Cabang mengakses halaman peta, THE MapController SHALL mengembalikan data GPS_Payload terbaru hanya dari Jamaah yang memiliki `branch_id` sama dengan `branch_id` Admin_Cabang yang login.
2. IF Admin_Cabang mengirimkan request ke endpoint data lokasi untuk mendapatkan data Jamaah dari `branch_id` yang berbeda, THEN THE AccessControl SHALL menolak request tersebut dan mengembalikan data kosong `[]`.
3. WHEN data JSON lokasi diterima oleh Peta_GIS di sisi browser, THE Peta_GIS SHALL merender marker hanya untuk Jamaah dalam cakupan cabang Admin_Cabang tersebut.
4. WHEN Peta_GIS menerima event WebSocket_Channel dari RealtimeUpdater untuk Jamaah dalam cabangnya, THE Peta_GIS SHALL memperbarui posisi marker Jamaah yang bersangkutan secara langsung tanpa memuat ulang halaman.
5. WHEN Admin_Cabang mengaktifkan filter grup pada Peta_GIS, THE Peta_GIS SHALL menampilkan hanya marker Jamaah dari kloter atau Grup_Bimbingan yang dipilih dalam cabangnya.
6. WHEN Admin_Cabang mengaktifkan filter status pada Peta_GIS, THE Peta_GIS SHALL menampilkan hanya marker Jamaah dengan status yang dipilih (online, offline, atau SOS) dalam cabangnya.
7. WHEN Admin_Cabang mengakses halaman peta, THE Peta_GIS SHALL menampilkan jumlah Jamaah online, offline, dan SOS Aktif khusus untuk cabangnya sebagai ringkasan di atas peta.

---

### Requirement 11: Peta GIS — Pemantauan Kloter oleh Tour Leader

**User Story:** Sebagai Tour Leader, saya ingin melihat manifest dan posisi peta jamaah yang berada dalam kloter saya, sehingga saya dapat memastikan seluruh anggota rombongan saya terpantau dan tidak ada yang terpisah.

#### Acceptance Criteria

1. WHEN Tour_Leader mengakses halaman manifest kloter, THE UserManager SHALL mengembalikan daftar pengguna dengan role `jamaah` yang tergabung dalam kloter yang dipimpin oleh Tour_Leader tersebut.
2. WHEN Tour_Leader mengakses halaman peta kloter, THE MapController SHALL mengembalikan data GPS_Koordinat terbaru hanya dari Jamaah yang tergabung dalam kloternya.
3. IF Tour_Leader mengirimkan request untuk mengakses data Jamaah di luar kloternya, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.

---

### Requirement 12: Pelacakan Lokasi Real-Time oleh Muthawwif

**User Story:** Sebagai Muthawwif, saya ingin memantau pergerakan real-time jamaah dalam kelompok bimbingan saya dan menerima notifikasi jika ada yang mengirim sinyal SOS, sehingga saya dapat merespons situasi darurat dengan cepat.

#### Acceptance Criteria

1. WHEN Muthawwif mengakses halaman peta bimbingan, THE MapController SHALL mengembalikan data GPS_Koordinat terbaru hanya dari Jamaah yang tergabung dalam Grup_Bimbingan Muthawwif tersebut.
2. WHEN Jamaah dalam Grup_Bimbingan seorang Muthawwif mengirimkan sinyal SOS, THE NotificationDispatcher SHALL mengirimkan notifikasi push ke perangkat Muthawwif tersebut dalam waktu tidak lebih dari 10 detik setelah sinyal SOS diterima oleh backend.
3. IF Muthawwif mengirimkan request untuk mengakses data lokasi Jamaah di luar Grup_Bimbingannya, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.

---

### Requirement 13: Pengiriman GPS oleh Jamaah (Location Tracking API)

**User Story:** Sebagai Jamaah, saya ingin aplikasi di HP saya secara berkala mengirimkan posisi GPS saya ke server, sehingga posisi saya dapat dipantau oleh Tour Leader dan Muthawwif yang bertanggung jawab atas keselamatan saya.

#### Acceptance Criteria

1. WHEN aplikasi mobile Jamaah mengirimkan request POST ke API pengiriman lokasi dengan GPS_Payload yang valid beserta Sanctum_Token yang valid, THE LocationTracker SHALL menyimpan seluruh field GPS_Payload — `latitude`, `longitude`, `accuracy`, `speed`, `heading`, `battery`, dan `timestamp` — ke tabel `locations` dengan `user_id` yang diambil dari Sanctum_Token.
2. THE LocationTracker SHALL memvalidasi bahwa nilai `latitude` berada dalam rentang -90.0 hingga 90.0 dan nilai `longitude` berada dalam rentang -180.0 hingga 180.0 sebelum menyimpan data.
3. THE LocationTracker SHALL memvalidasi bahwa nilai `accuracy` adalah bilangan positif dalam satuan meter, nilai `speed` adalah bilangan tidak negatif dalam satuan m/s, nilai `heading` berada dalam rentang 0.0 hingga 360.0 derajat, dan nilai `battery` adalah bilangan bulat dalam rentang 0 hingga 100 sebelum menyimpan data.
4. THE LocationTracker SHALL memvalidasi bahwa field `timestamp` memenuhi format ISO 8601 sebelum menyimpan data.
5. IF aplikasi mobile Jamaah mengirimkan request ke API pengiriman lokasi tanpa Sanctum_Token yang valid, THEN THE LocationTracker SHALL menolak request tersebut dengan HTTP status `401 Unauthorized`.
6. IF aplikasi mobile Jamaah mengirimkan nilai `latitude` di luar rentang -90.0 hingga 90.0 atau nilai `longitude` di luar rentang -180.0 hingga 180.0, THEN THE LocationTracker SHALL menolak request tersebut dan mengembalikan pesan kesalahan validasi yang deskriptif.
7. THE LocationTracker SHALL menyimpan setiap kiriman GPS_Payload sebagai record baru di tabel `locations` sehingga riwayat pergerakan dapat ditelusuri.
8. WHEN LocationTracker berhasil menyimpan GPS_Payload, THE RealtimeUpdater SHALL mem-broadcast event pembaruan posisi ke WebSocket_Channel yang sesuai sehingga Peta_GIS di browser dapat memperbarui marker tanpa polling.

---

### Requirement 14: Tombol SOS Darurat oleh Jamaah

**User Story:** Sebagai Jamaah, saya ingin dapat menekan tombol SOS darurat di aplikasi saya ketika menghadapi situasi berbahaya, sehingga sinyal darurat saya segera terlihat oleh petugas dan saya dapat mendapatkan pertolongan dengan cepat.

#### Acceptance Criteria

1. WHEN aplikasi mobile Jamaah mengirimkan request POST ke API SOS dengan Sanctum_Token yang valid, THE SOSHandler SHALL menyimpan record baru ke tabel `locations` dengan nilai `is_sos = true` beserta GPS_Payload terkini Jamaah tersebut.
2. WHEN SOSHandler menerima sinyal SOS dari Jamaah, THE SOSHandler SHALL merespons dengan HTTP status `200 OK` dalam waktu tidak lebih dari 3 detik.
3. WHEN SOSHandler berhasil menyimpan sinyal SOS, THE RealtimeUpdater SHALL mem-broadcast event SOS ke WebSocket_Channel sehingga Peta_GIS di browser memperbarui marker Jamaah tersebut menjadi merah berkedip secara langsung tanpa polling.
4. WHEN Peta_GIS menerima event SOS dari WebSocket_Channel, THE Peta_GIS SHALL mengubah marker Jamaah tersebut menjadi indikator visual warna merah berkedip yang berbeda secara jelas dari marker normal.
5. WHEN SOSHandler berhasil menyimpan sinyal SOS, THE NotificationDispatcher SHALL mengirimkan notifikasi realtime kepada Muthawwif yang bertanggung jawab atas Jamaah tersebut dalam waktu tidak lebih dari 10 detik.
6. WHEN SOSHandler berhasil menyimpan sinyal SOS, THE NotificationDispatcher SHALL mengirimkan notifikasi realtime kepada Tour_Leader yang memimpin kloter Jamaah tersebut dalam waktu tidak lebih dari 10 detik.
7. WHEN SOSHandler berhasil menyimpan sinyal SOS, THE NotificationDispatcher SHALL mengirimkan notifikasi realtime kepada Admin_Cabang yang memiliki `branch_id` sama dengan Jamaah tersebut dalam waktu tidak lebih dari 10 detik.
8. WHEN SOSHandler berhasil menyimpan sinyal SOS, THE NotificationDispatcher SHALL mengirimkan notifikasi realtime kepada seluruh Super_Admin yang aktif dalam waktu tidak lebih dari 10 detik.
9. IF aplikasi mobile Jamaah mengirimkan request SOS tanpa Sanctum_Token yang valid, THEN THE SOSHandler SHALL menolak request tersebut dengan HTTP status `401 Unauthorized`.

---

### Requirement 15: Fitur Peta Jamaah — Posisi Hotel dan Muthawwif

**User Story:** Sebagai Jamaah, saya ingin melihat posisi hotel tempat saya menginap dan posisi Muthawwif saya di peta pada aplikasi HP, sehingga saya dapat menavigasi kembali ke hotel atau menemukan pembimbing saya jika tersesat.

#### Acceptance Criteria

1. WHEN Jamaah mengakses endpoint API peta dari aplikasi mobile dengan Sanctum_Token yang valid, THE MapController SHALL mengembalikan GPS_Koordinat terkini dari Muthawwif yang tergabung dalam Grup_Bimbingan Jamaah tersebut.
2. WHEN Jamaah mengakses endpoint API peta dari aplikasi mobile dengan Sanctum_Token yang valid, THE MapController SHALL mengembalikan data lokasi hotel yang telah dikonfigurasi untuk kloter Jamaah tersebut.
3. IF Jamaah mengirimkan request ke endpoint API yang tidak diizinkan (seperti endpoint daftar semua Jamaah atau manajemen akun), THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.

---

### Requirement 16: Integritas Data dan Validasi Input

**User Story:** Sebagai pengembang sistem, saya ingin setiap input yang masuk divalidasi secara ketat sebelum diproses, sehingga data yang tersimpan di database selalu konsisten dan terhindar dari korupsi data.

#### Acceptance Criteria

1. THE UserManager SHALL memvalidasi bahwa field `name` tidak kosong dan memiliki panjang maksimum 255 karakter untuk setiap request pembuatan atau pembaruan akun.
2. THE UserManager SHALL memvalidasi bahwa field `email` memenuhi format email yang valid (RFC 5321) untuk setiap request pembuatan atau pembaruan akun.
3. THE UserManager SHALL memvalidasi bahwa field `phone_number`, jika dikirimkan, hanya berisi digit numerik dan memiliki panjang antara 10 hingga 15 digit.
4. THE BranchManager SHALL memvalidasi bahwa field `name_branch` dan `city` tidak kosong dan masing-masing memiliki panjang maksimum 255 karakter untuk setiap request pembuatan atau pembaruan cabang.
5. IF sebuah request gagal validasi, THEN THE System SHALL mengembalikan HTTP status `422 Unprocessable Entity` beserta daftar pesan kesalahan validasi yang deskriptif dalam format JSON.

---

### Requirement 17: Histori Lokasi & Perjalanan

**User Story:** Sebagai Tour Leader atau Muthawwif, saya ingin melihat riwayat pergerakan jamaah dalam kloter atau kelompok bimbingan saya, sehingga saya dapat mengetahui di mana jamaah pernah berada dan mendeteksi pola pergerakan yang tidak wajar.

#### Acceptance Criteria

1. WHEN Tour_Leader mengirimkan request GET ke API histori lokasi dengan `user_id` Jamaah yang tergabung dalam kloternya, THE HistoryTracker SHALL mengembalikan daftar seluruh record `locations` milik Jamaah tersebut diurutkan berdasarkan `created_at` secara ascending.
2. WHEN Muthawwif mengirimkan request GET ke API histori lokasi dengan `user_id` Jamaah yang tergabung dalam Grup_Bimbingannya, THE HistoryTracker SHALL mengembalikan daftar seluruh record `locations` milik Jamaah tersebut diurutkan berdasarkan `created_at` secara ascending.
3. THE HistoryTracker SHALL mendukung parameter filter `start_date` dan `end_date` dalam format ISO 8601 untuk membatasi rentang waktu histori yang dikembalikan.
4. IF Tour_Leader atau Muthawwif mengirimkan request histori lokasi untuk `user_id` Jamaah yang berada di luar kloter atau Grup_Bimbingannya, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
5. WHEN Peta_GIS menampilkan histori pergerakan seorang Jamaah, THE Peta_GIS SHALL menggambar jejak polyline pada peta berdasarkan urutan koordinat histori tersebut.

---

### Requirement 18: Realtime WebSocket (Laravel Broadcasting)

**User Story:** Sebagai administrator atau petugas lapangan yang membuka peta di browser, saya ingin marker posisi jamaah diperbarui secara otomatis tanpa me-refresh halaman, sehingga pemantauan realtime lebih efisien dan tidak ada penundaan informasi.

#### Acceptance Criteria

1. WHEN LocationTracker berhasil menyimpan GPS_Payload dari seorang Jamaah, THE RealtimeUpdater SHALL mempublikasikan event pembaruan posisi ke WebSocket_Channel privat yang sesuai dengan `branch_id` Jamaah tersebut dalam waktu tidak lebih dari 2 detik.
2. WHEN RealtimeUpdater mem-broadcast event pembaruan posisi, THE RealtimeUpdater SHALL menyertakan `user_id`, `latitude`, `longitude`, `speed`, `battery`, `heading`, dan `timestamp` dalam payload event.
3. WHEN browser yang membuka Peta_GIS menerima event pembaruan posisi melalui WebSocket_Channel, THE Peta_GIS SHALL memindahkan marker Jamaah yang bersangkutan ke koordinat baru tanpa memuat ulang halaman.
4. WHEN SOSHandler berhasil menyimpan sinyal SOS, THE RealtimeUpdater SHALL mempublikasikan event SOS terpisah ke WebSocket_Channel dalam waktu tidak lebih dari 2 detik.
5. THE System SHALL mendukung minimal satu dari provider WebSocket berikut yang dikonfigurasi melalui variabel environment: Laravel Reverb atau Pusher.
6. IF koneksi WebSocket_Channel terputus di sisi browser, THE Peta_GIS SHALL mencoba menyambung kembali secara otomatis dengan interval backoff eksponensial maksimum 30 detik.

---

### Requirement 19: Status Online/Offline Jamaah

**User Story:** Sebagai Admin Cabang atau Super Admin, saya ingin mengetahui secara sekilas jamaah mana yang saat ini aktif mengirim GPS dan mana yang tidak aktif, sehingga saya dapat segera menindaklanjuti jamaah yang tidak terpantau.

#### Acceptance Criteria

1. WHEN sebuah komponen sistem meminta status keaktifan seorang Jamaah, THE OnlineStatusResolver SHALL mengembalikan status `online` jika terdapat setidaknya satu record `locations` milik Jamaah tersebut dengan nilai `created_at` dalam rentang Notification_Threshold menit terakhir dihitung dari waktu server saat ini.
2. WHEN sebuah komponen sistem meminta status keaktifan seorang Jamaah, THE OnlineStatusResolver SHALL mengembalikan status `offline` jika tidak terdapat record `locations` milik Jamaah tersebut dalam rentang Notification_Threshold menit terakhir.
3. THE System SHALL menggunakan nilai Notification_Threshold yang dikonfigurasi melalui variabel environment, dengan nilai default 5 menit.
4. WHEN Peta_GIS menampilkan marker Jamaah, THE Peta_GIS SHALL membedakan tampilan visual marker Jamaah berstatus online (hijau) dari marker Jamaah berstatus offline (abu-abu).
5. WHEN DashboardController menghitung jumlah Jamaah online dan offline, THE DashboardController SHALL menggunakan OnlineStatusResolver untuk menentukan status setiap Jamaah.

---

### Requirement 20: Geofence (Batas Area Virtual)

**User Story:** Sebagai Admin Cabang atau Super Admin, saya ingin mendefinisikan area batas virtual untuk setiap kloter, sehingga sistem otomatis memberi peringatan ketika seorang jamaah keluar dari area yang telah ditentukan.

#### Acceptance Criteria

1. WHEN Super_Admin atau Admin_Cabang mengirimkan request pembuatan Geofence baru dengan parameter `name`, `center_latitude`, `center_longitude`, dan `radius_meters` yang valid, THE GeofenceMonitor SHALL menyimpan definisi Geofence tersebut ke database dan mengaitkannya dengan kloter yang ditentukan.
2. WHEN LocationTracker berhasil menyimpan GPS_Payload dari seorang Jamaah, THE GeofenceMonitor SHALL memeriksa apakah posisi terbaru Jamaah tersebut berada di luar semua Geofence yang berlaku untuk kloternya.
3. WHEN GeofenceMonitor mendeteksi bahwa seorang Jamaah berada di luar batas Geofence kloternya, THE NotificationDispatcher SHALL mengirimkan notifikasi peringatan kepada Muthawwif, Tour_Leader, dan Admin_Cabang yang terkait dengan Jamaah tersebut.
4. WHEN GeofenceMonitor mendeteksi pelanggaran batas Geofence, THE RealtimeUpdater SHALL mem-broadcast event pelanggaran tersebut ke WebSocket_Channel sehingga Peta_GIS dapat menampilkan peringatan visual secara langsung.
5. IF Super_Admin atau Admin_Cabang mengirimkan request pembuatan Geofence dengan nilai `radius_meters` kurang dari 50 atau lebih dari 50000, THEN THE GeofenceMonitor SHALL menolak request tersebut dengan pesan kesalahan validasi yang deskriptif.
6. WHEN Super_Admin mengakses halaman peta, THE Peta_GIS SHALL merender overlay lingkaran atau poligon pada peta untuk setiap Geofence yang aktif.

---

### Requirement 21: Aplikasi Flutter (Flutter_App)

**User Story:** Sebagai Jamaah, saya ingin menggunakan aplikasi mobile yang intuitif untuk login, melihat informasi perjalanan, memantau posisi hotel dan Muthawwif, serta menekan tombol SOS jika terjadi kedaruratan, sehingga saya merasa aman dan terhubung selama perjalanan umroh.

#### Acceptance Criteria

1. THE Flutter_App SHALL menyediakan halaman Login yang memungkinkan Jamaah memasukkan email dan password untuk mendapatkan Sanctum_Token dari API AuthManager.
2. THE Flutter_App SHALL menyediakan halaman Dashboard yang menampilkan informasi perjalanan aktif Jamaah, termasuk nama kloter, nama Muthawwif, dan nama Tour_Leader.
3. THE Flutter_App SHALL menyediakan halaman Profil yang menampilkan data akun Jamaah yang sedang login.
4. THE Flutter_App SHALL menyediakan halaman Tracking yang menampilkan peta Leaflet dengan marker posisi Muthawwif dan marker Hotel yang diambil dari API MapController.
5. THE Flutter_App SHALL menyediakan halaman Hotel yang menampilkan detail informasi hotel tempat Jamaah menginap, termasuk nama, alamat, dan koordinat GPS hotel tersebut.
6. THE Flutter_App SHALL menyediakan halaman SOS yang menampilkan tombol darurat berukuran besar; WHEN Jamaah menekan tombol tersebut dan mengonfirmasi tindakan, THE Flutter_App SHALL mengirimkan request ke API SOSHandler beserta GPS_Payload terkini.
7. THE Flutter_App SHALL menyediakan halaman Riwayat yang menampilkan histori perjalanan Jamaah berdasarkan data dari HistoryTracker API.
8. WHILE Flutter_App aktif berjalan sebagai Background_Service di perangkat Jamaah, THE Flutter_App SHALL mengirimkan GPS_Payload ke API LocationTracker dengan interval yang dikonfigurasi, tidak melebihi satu kali setiap 30 detik.
9. WHEN Background_Service sedang aktif dan koneksi internet tersedia, THE Flutter_App SHALL mengirimkan GPS_Payload ke LocationTracker API menggunakan Sanctum_Token yang tersimpan di local storage perangkat.
10. IF Sanctum_Token yang tersimpan di Flutter_App sudah tidak valid atau kedaluwarsa, THEN THE Flutter_App SHALL mengarahkan pengguna ke halaman Login secara otomatis.

---

### Requirement 22: Background GPS Service (Flutter)

**User Story:** Sebagai Jamaah, saya ingin posisi GPS saya tetap terkirim ke server meskipun saya meminimalkan aplikasi atau menggunakan aplikasi lain, sehingga pemantauan tidak terputus karena interaksi saya dengan perangkat.

#### Acceptance Criteria

1. WHEN Jamaah mengaktifkan layanan pelacakan dan kemudian meminimalkan Flutter_App, THE Background_Service SHALL melanjutkan pengiriman GPS_Payload ke LocationTracker API sesuai interval yang dikonfigurasi.
2. THE Background_Service SHALL menggunakan mekanisme Android Foreground Service dengan notifikasi persisten untuk memastikan proses tidak dihentikan oleh sistem operasi Android dalam kondisi normal.
3. THE Background_Service SHALL menggunakan mekanisme iOS Background Task yang sesuai dengan kemampuan sistem operasi iOS untuk pengiriman GPS_Payload dalam mode latar belakang.
4. WHEN perangkat Jamaah tidak memiliki koneksi internet saat Background_Service hendak mengirim GPS_Payload, THE Flutter_App SHALL menyimpan GPS_Payload tersebut dalam antrian lokal dan mengirimkannya kembali ketika koneksi internet tersedia kembali.
5. WHEN nilai `battery` dalam GPS_Payload yang akan dikirim kurang dari 15, THE Flutter_App SHALL meningkatkan interval pengiriman GPS_Payload menjadi dua kali lipat interval normal untuk menghemat daya.

---

### Requirement 23: Standar Arsitektur Kode (Code Architecture Standards)

**User Story:** Sebagai pengembang yang akan merawat dan mengembangkan sistem ini, saya ingin seluruh kode mengikuti standar arsitektur yang modular dan konsisten, sehingga setiap bagian sistem mudah diuji, diubah, dan diperluas tanpa menimbulkan efek samping yang tidak terduga.

#### Acceptance Criteria

1. THE System SHALL memisahkan business logic ke dalam kelas-kelas ServiceLayer yang terpisah dari kelas Controller; setiap Controller HANYA diperbolehkan memanggil ServiceLayer, bukan mengakses database secara langsung.
2. THE System SHALL memisahkan semua query database ke dalam kelas-kelas RepositoryLayer; kelas ServiceLayer HANYA diperbolehkan mengakses database melalui RepositoryLayer, bukan melalui Eloquent langsung di dalam service.
3. THE System SHALL menggunakan kelas Form Request Laravel untuk setiap endpoint yang menerima input pengguna; validasi input TIDAK diperbolehkan ditempatkan di dalam Controller atau ServiceLayer.
4. THE System SHALL menggunakan kelas Policy Laravel untuk menentukan otorisasi setiap aksi (create, read, update, delete) terhadap setiap model; otorisasi TIDAK diperbolehkan menggunakan pemeriksaan `if ($user->role === ...)` secara langsung di dalam Controller.
5. THE System SHALL menggunakan Middleware Laravel untuk memverifikasi autentikasi Sanctum_Token dan memeriksa role pengguna pada setiap route group yang dilindungi.
6. THE System SHALL menggunakan Observer Laravel untuk menangani side effect yang dipicu oleh perubahan model (misalnya: memperbarui status online saat record `locations` dibuat).
7. THE System SHALL menggunakan kelas Event dan Listener Laravel untuk memisahkan logika pengiriman notifikasi dan broadcast dari logika penyimpanan data; pemanggilan NotificationDispatcher dan RealtimeUpdater HARUS dilakukan melalui Event/Listener, bukan langsung dari Controller atau Service.
8. THE System SHALL menggunakan Laravel Queue untuk menjalankan job-job yang berpotensi lambat secara asinkron, termasuk pengiriman notifikasi push, broadcast SOS, dan pemeriksaan Geofence.
9. THE System SHALL menggunakan kelas Notification Laravel untuk mengirimkan notifikasi melalui channel `database` dan `broadcast`, sehingga riwayat notifikasi dapat disimpan dan diakses oleh penerima.

---

### Requirement 24: Hak Akses Tour Leader

**User Story:** Sebagai Tour Leader, saya ingin dapat melihat data jamaah dalam kloter yang saya pimpin, memantau posisi rombongan di peta, dan melihat histori perjalanan, sehingga saya dapat menjalankan tanggung jawab saya sebagai pemimpin rombongan secara efektif.

#### Acceptance Criteria

1. WHEN Tour_Leader mengakses sistem, THE AccessControl SHALL mengizinkan Tour_Leader membaca daftar Jamaah yang tergabung dalam kloternya melalui endpoint manifest kloter.
2. WHEN Tour_Leader mengakses halaman peta, THE MapController SHALL mengembalikan posisi terkini seluruh Jamaah dalam kloternya beserta marker Hotel yang relevan.
3. WHEN Tour_Leader mengakses fitur histori perjalanan untuk seorang Jamaah dalam kloternya, THE HistoryTracker SHALL mengembalikan riwayat GPS_Payload lengkap Jamaah tersebut.
4. IF Tour_Leader mencoba mengakses data Jamaah, manifest, atau peta di luar cakupan kloternya, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
5. IF Tour_Leader mencoba mengakses fitur manajemen akun (membuat, mengubah, atau menghapus pengguna), THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.

---

### Requirement 25: Hak Akses Muthawwif

**User Story:** Sebagai Muthawwif, saya ingin dapat memantau lokasi real-time jamaah dalam kelompok bimbingan saya, menerima notifikasi SOS, dan melihat histori lokasi, sehingga saya dapat mendampingi dan melindungi jamaah bimbingan saya selama perjalanan.

#### Acceptance Criteria

1. WHEN Muthawwif mengakses halaman peta bimbingan, THE MapController SHALL mengembalikan posisi terkini seluruh Jamaah dalam Grup_Bimbingannya secara realtime melalui WebSocket_Channel.
2. WHEN Jamaah dalam Grup_Bimbingan seorang Muthawwif mengirimkan sinyal SOS, THE NotificationDispatcher SHALL mengirimkan notifikasi kepada Muthawwif tersebut melalui channel `broadcast` dan `database` dalam waktu tidak lebih dari 10 detik.
3. WHEN Muthawwif mengakses fitur histori lokasi untuk seorang Jamaah dalam Grup_Bimbingannya, THE HistoryTracker SHALL mengembalikan riwayat GPS_Payload lengkap Jamaah tersebut.
4. WHEN Muthawwif mengakses sistem, THE AccessControl SHALL mengizinkan Muthawwif membaca daftar Jamaah yang tergabung dalam Grup_Bimbingannya.
5. IF Muthawwif mencoba mengakses data Jamaah di luar Grup_Bimbingannya, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
6. IF Muthawwif mencoba mengakses fitur manajemen akun (membuat, mengubah, atau menghapus pengguna) atau fitur manajemen cabang, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.

---

### Requirement 26: Manajemen Jadwal Keberangkatan

**User Story:** Sebagai Admin Cabang, saya ingin dapat membuat dan mengelola jadwal keberangkatan umroh untuk cabang saya, sehingga seluruh operasional perjalanan terorganisir dengan baik dan memiliki referensi waktu yang jelas.

#### Acceptance Criteria

1. WHEN Admin_Cabang mengirimkan request pembuatan Keberangkatan baru dengan field `departure_date`, `return_date`, `program_name`, dan `branch_id` yang valid, THE GroupManager SHALL menyimpan data tersebut ke tabel `departures` dan mengembalikan data Keberangkatan yang baru dibuat beserta HTTP status `201 Created`.
2. THE GroupManager SHALL memvalidasi bahwa nilai `departure_date` adalah tanggal yang valid dalam format ISO 8601 dan nilai `return_date` lebih besar dari `departure_date` sebelum menyimpan data Keberangkatan.
3. THE GroupManager SHALL memvalidasi bahwa field `program_name` tidak kosong dan memiliki panjang maksimum 255 karakter sebelum menyimpan data Keberangkatan.
4. WHEN Admin_Cabang mengirimkan request pembaruan Keberangkatan dengan `id` yang valid dan berada dalam `branch_id` miliknya, THE GroupManager SHALL memperbarui kolom `departure_date`, `return_date`, dan/atau `program_name` pada record tersebut.
5. WHEN Admin_Cabang mengirimkan request penghapusan Keberangkatan dengan `id` yang valid dan berada dalam `branch_id` miliknya, THE GroupManager SHALL menghapus data Keberangkatan tersebut dari tabel `departures`.
6. IF Admin_Cabang mengirimkan request untuk membaca, mengubah, atau menghapus Keberangkatan yang memiliki `branch_id` berbeda dari `branch_id`-nya, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
7. WHEN Admin_Cabang mengakses daftar Keberangkatan, THE GroupManager SHALL mengembalikan seluruh Keberangkatan yang memiliki `branch_id` sama dengan `branch_id` Admin_Cabang yang login.
8. IF pengguna dengan role `tour_leader`, `muthawwif`, atau `jamaah` mengirimkan request untuk membuat, mengubah, atau menghapus Keberangkatan, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.

---

### Requirement 27: Manajemen Group / Rombongan

**User Story:** Sebagai Admin Cabang, saya ingin dapat membuat rombongan, menambahkan jamaah ke dalam rombongan, serta menugaskan Tour Leader dan Muthawwif ke rombongan, sehingga struktur rombongan perjalanan umroh dapat dikelola secara terstruktur.

#### Acceptance Criteria

1. WHEN Admin_Cabang mengirimkan request pembuatan Group baru dengan field `group_name` dan `departure_id` yang valid, THE GroupManager SHALL menyimpan data Group ke tabel `groups` dan mengembalikan data Group yang baru dibuat beserta HTTP status `201 Created`.
2. THE GroupManager SHALL memvalidasi bahwa `departure_id` yang dikirimkan merujuk pada Keberangkatan yang ada dan berada dalam `branch_id` yang sama dengan Admin_Cabang yang login sebelum menyimpan Group baru.
3. WHEN Admin_Cabang mengirimkan request penugasan Tour_Leader ke sebuah Group dengan `group_id` dan `user_id` Tour_Leader yang valid, THE AssignmentManager SHALL memperbarui kolom `tour_leader_id` pada tabel `groups` dengan `user_id` Tour_Leader tersebut.
4. WHEN Admin_Cabang mengirimkan request penugasan Muthawwif ke sebuah Group dengan `group_id` dan `user_id` Muthawwif yang valid, THE AssignmentManager SHALL memperbarui kolom `muthawwif_id` pada tabel `groups` dengan `user_id` Muthawwif tersebut.
5. WHEN Admin_Cabang mengirimkan request penambahan Jamaah ke dalam Group dengan `group_id` dan `user_id` Jamaah yang valid, THE AssignmentManager SHALL menyimpan relasi tersebut ke tabel `group_jamaah` yang menghubungkan `group_id` dan `user_id`.
6. WHEN Admin_Cabang mengirimkan request penghapusan Jamaah dari Group, THE AssignmentManager SHALL menghapus record relasi yang sesuai dari tabel `group_jamaah`.
7. THE AssignmentManager SHALL memvalidasi bahwa Tour_Leader yang akan ditugaskan ke sebuah Group memiliki `branch_id` yang sama dengan `branch_id` Group tersebut.
8. THE AssignmentManager SHALL memvalidasi bahwa Muthawwif yang akan ditugaskan ke sebuah Group memiliki `branch_id` yang sama dengan `branch_id` Group tersebut.
9. THE AssignmentManager SHALL memvalidasi bahwa Jamaah yang akan ditambahkan ke sebuah Group memiliki `branch_id` yang sama dengan `branch_id` Group tersebut.
10. IF pengguna dengan role `tour_leader`, `muthawwif`, atau `jamaah` mengirimkan request untuk membuat, mengubah, atau menghapus Group, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
11. IF Admin_Cabang mengirimkan request untuk mengakses atau memodifikasi Group yang memiliki `branch_id` berbeda dari miliknya, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.

---

### Requirement 28: Manajemen Data Hotel

**User Story:** Sebagai Admin Cabang, saya ingin dapat menginput data hotel untuk setiap jadwal keberangkatan, sehingga informasi penginapan dapat ditampilkan di peta dan Jamaah dapat menemukan lokasi hotel mereka dengan mudah.

#### Acceptance Criteria

1. WHEN Admin_Cabang mengirimkan request pembuatan data Hotel baru dengan field `hotel_name`, `address`, `latitude`, `longitude`, dan `departure_id` yang valid, THE HotelManager SHALL menyimpan data tersebut ke tabel `hotels` dan mengembalikan data Hotel yang baru dibuat beserta HTTP status `201 Created`.
2. THE HotelManager SHALL memvalidasi bahwa `departure_id` yang dikirimkan merujuk pada Keberangkatan yang ada dan berada dalam `branch_id` yang sama dengan Admin_Cabang yang login sebelum menyimpan data Hotel.
3. THE HotelManager SHALL memvalidasi bahwa nilai `latitude` berada dalam rentang -90.0 hingga 90.0 dan nilai `longitude` berada dalam rentang -180.0 hingga 180.0 sebelum menyimpan data Hotel.
4. THE HotelManager SHALL memvalidasi bahwa field `hotel_name` tidak kosong dan memiliki panjang maksimum 255 karakter, serta field `address` tidak kosong sebelum menyimpan data Hotel.
5. WHEN Admin_Cabang mengirimkan request pembaruan data Hotel dengan `id` yang valid dan berada dalam cakupan `branch_id` miliknya, THE HotelManager SHALL memperbarui kolom `hotel_name`, `address`, `latitude`, dan/atau `longitude` pada record tersebut.
6. WHEN Admin_Cabang mengirimkan request penghapusan data Hotel dengan `id` yang valid, THE HotelManager SHALL menghapus data Hotel tersebut dari tabel `hotels`.
7. WHEN MapController menyusun data peta untuk Jamaah atau petugas, THE MapController SHALL mengambil data Hotel dari tabel `hotels` berdasarkan `departure_id` yang relevan dan menyertakan `hotel_name`, `address`, `latitude`, dan `longitude` dalam payload respons.
8. IF pengguna dengan role `tour_leader`, `muthawwif`, atau `jamaah` mengirimkan request untuk membuat, mengubah, atau menghapus data Hotel, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.

---

### Requirement 29: Penanganan & Resolusi SOS

**User Story:** Sebagai petugas (Admin Cabang, Tour Leader, atau Muthawwif), saya ingin dapat menandai sinyal SOS sebagai "Selesai" setelah saya menangani jamaah yang membutuhkan pertolongan, sehingga status darurat tercatat dengan akurat dan sistem mengetahui bahwa situasi telah tertangani.

#### Acceptance Criteria

1. WHEN Admin_Cabang, Tour_Leader, atau Muthawwif yang memiliki akses terhadap Jamaah bersangkutan mengirimkan request resolusi SOS dengan `sos_id` yang valid dan status `is_sos = true`, THE SOSResolver SHALL memperbarui status SOS tersebut menjadi resolved dengan menyimpan nilai `resolved_at` (timestamp saat ini) dan `resolved_by` (user_id petugas yang menyelesaikan) ke tabel `locations`.
2. WHEN SOSResolver berhasil menyelesaikan sebuah SOS, THE RealtimeUpdater SHALL mem-broadcast event resolusi SOS ke WebSocket_Channel sehingga Peta_GIS di browser memperbarui marker Jamaah tersebut kembali ke tampilan normal (bukan merah berkedip) secara langsung tanpa polling.
3. WHEN SOSResolver berhasil menyelesaikan sebuah SOS, THE System SHALL menyimpan SOS_Histori secara permanen di database dengan data: `user_id` Jamaah, GPS_Koordinat saat SOS dikirim, `created_at` (waktu SOS dikirim), `resolved_at` (waktu SOS diselesaikan), dan `resolved_by` (user_id petugas).
4. WHEN Super_Admin atau Admin_Cabang mengakses daftar SOS_Histori, THE SOSResolver SHALL mengembalikan seluruh catatan SOS yang pernah terjadi beserta status resolved dan detail petugas yang menyelesaikan, diurutkan berdasarkan `created_at` secara descending.
5. THE SOSResolver SHALL memvalidasi bahwa petugas yang mengirimkan request resolusi SOS memiliki akses terhadap Jamaah yang bersangkutan sesuai cakupan role-nya (Admin_Cabang: cakupan cabang, Tour_Leader: cakupan kloternya, Muthawwif: cakupan Grup_Bimbingannya).
6. IF petugas mengirimkan request resolusi SOS untuk `sos_id` yang sudah berstatus resolved, THEN THE SOSResolver SHALL menolak request tersebut dan mengembalikan pesan kesalahan `"SOS ini sudah diselesaikan sebelumnya."`.
7. WHEN Super_Admin mengakses halaman dashboard, THE DashboardController SHALL menampilkan jumlah SOS Aktif (belum resolved) dari seluruh cabang berdasarkan data tabel `locations`.
8. WHEN Admin_Cabang mengakses halaman peta, THE Peta_GIS SHALL membedakan tampilan SOS Aktif (merah berkedip) dari SOS yang sudah resolved (tidak berkedip) dalam cakupan cabangnya.

---

### Requirement 30: Dashboard Tour Leader

**User Story:** Sebagai Tour Leader, saya ingin memiliki dashboard yang menampilkan daftar jamaah rombongan saya, status kehadiran, dan ringkasan kondisi lapangan, sehingga saya dapat menjalankan tanggung jawab kepemimpinan rombongan secara efektif.

#### Acceptance Criteria

1. WHEN Tour_Leader mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah Jamaah yang tergabung dalam Group yang dipimpin oleh Tour_Leader tersebut.
2. WHEN Tour_Leader mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah Jamaah dalam Group-nya yang berstatus online, menggunakan OnlineStatusResolver untuk menentukan status setiap Jamaah.
3. WHEN Tour_Leader mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah Jamaah dalam Group-nya yang berstatus offline, yaitu total Jamaah dikurangi jumlah Jamaah online dalam Group tersebut.
4. WHEN Tour_Leader mengakses halaman dashboard, THE DashboardController SHALL menampilkan daftar Jamaah dalam Group-nya beserta status online/offline masing-masing Jamaah.
5. WHEN Tour_Leader mengakses halaman dashboard, THE DashboardController SHALL menampilkan informasi Keberangkatan yang terkait dengan Group-nya, termasuk `program_name`, `departure_date`, dan `return_date`.
6. WHEN Tour_Leader mengakses halaman dashboard, THE DashboardController SHALL menampilkan ringkasan histori pergerakan terbaru untuk Group-nya, yaitu daftar Jamaah beserta timestamp kiriman GPS_Payload terakhir dari masing-masing Jamaah.
7. WHILE Tour_Leader sedang mengakses halaman dashboard, THE DashboardController SHALL menyembunyikan data Jamaah, Group, dan statistik di luar cakupan Group yang dipimpin oleh Tour_Leader tersebut.
8. IF Tour_Leader mencoba mengakses endpoint dashboard milik Admin_Cabang atau Super_Admin, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.

---

### Requirement 31: Dashboard Muthawwif

**User Story:** Sebagai Muthawwif, saya ingin memiliki dashboard yang menampilkan posisi jamaah bimbingan saya, notifikasi SOS aktif, dan histori pergerakan terkini, sehingga saya dapat mendampingi dan melindungi jamaah selama di lapangan.

#### Acceptance Criteria

1. WHEN Muthawwif mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan total jumlah Jamaah yang tergabung dalam Grup_Bimbingan Muthawwif tersebut.
2. WHEN Muthawwif mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah Jamaah dalam Grup_Bimbingannya yang berstatus online, menggunakan OnlineStatusResolver untuk menentukan status setiap Jamaah.
3. WHEN Muthawwif mengakses halaman dashboard, THE DashboardController SHALL menghitung dan menampilkan jumlah Jamaah dalam Grup_Bimbingannya yang berstatus offline, yaitu total Jamaah dikurangi jumlah Jamaah online dalam Grup_Bimbingan tersebut.
4. WHEN Muthawwif mengakses halaman dashboard, THE DashboardController SHALL menampilkan daftar SOS Aktif dari Jamaah dalam Grup_Bimbingannya, yaitu record di tabel `locations` dengan `is_sos = true` yang belum resolved dan `user_id`-nya termasuk dalam Grup_Bimbingan Muthawwif tersebut.
5. WHEN Muthawwif mengakses halaman dashboard, THE DashboardController SHALL menampilkan ringkasan posisi terkini setiap Jamaah dalam Grup_Bimbingannya, termasuk `latitude`, `longitude`, dan timestamp kiriman GPS_Payload terakhir.
6. WHEN Muthawwif mengakses halaman dashboard, THE DashboardController SHALL menyediakan akses cepat ke halaman peta bimbingan yang menampilkan posisi real-time seluruh Jamaah dalam Grup_Bimbingannya.
7. WHEN Muthawwif mengakses halaman dashboard, THE DashboardController SHALL menampilkan SOS_Histori terbaru (maksimum 10 record terakhir) dari Jamaah dalam Grup_Bimbingannya, termasuk status resolved dan detail petugas yang menyelesaikan.
8. WHILE Muthawwif sedang mengakses halaman dashboard, THE DashboardController SHALL menyembunyikan data Jamaah dan statistik di luar cakupan Grup_Bimbingan milik Muthawwif yang login.
9. IF Muthawwif mencoba mengakses endpoint dashboard milik Admin_Cabang, Tour_Leader, atau Super_Admin, THEN THE AccessControl SHALL menolak request tersebut dengan HTTP status `403 Forbidden`.
