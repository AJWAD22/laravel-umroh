# PATCH-03 AUTHORIZATION HARDENING

Ekstrak ke root project.

Jalankan:
php artisan migrate
php artisan optimize:clear
php artisan test

Isi patch:
- Device revoke service
- Policy lokasi dan SOS
- Field revoked_reason
- Role permission matrix
