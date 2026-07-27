# PATCH-02 Location Tracking Reliability

Perubahan:
- validasi waktu device/server
- deduplikasi histori lokasi
- batas per_page
- persiapan retensi histori lokasi

Install:
php artisan migrate
php artisan optimize:clear
php artisan test

Rollback:
php artisan migrate:rollback
