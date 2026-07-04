# Pengujian REST API Mobile dengan Postman

## Persiapan

1. Jalankan `php artisan migrate:fresh --seed`.
2. Jalankan `php artisan serve`.
3. Buat Postman Environment:
   - `base_url` = `http://127.0.0.1:8000`
   - `token` = kosong
4. Gunakan header `Accept: application/json` pada seluruh request.

## Akun demo

Semua akun memakai password `password`.

| Role | Email |
|---|---|
| Jamaah | `jamaah@umrah.test` |
| Tour Leader | `tourleader@umrah.test` |
| Muthawwif | `muthawwif@umrah.test` |

## Login

`POST {{base_url}}/api/mobile/login`

```json
{
  "email": "jamaah@umrah.test",
  "password": "password",
  "device_name": "Postman Android"
}
```

Tambahkan script berikut pada tab **Tests** agar token otomatis tersimpan:

```javascript
const response = pm.response.json();
pm.environment.set('token', response.access_token);
```

Untuk endpoint terproteksi pilih Authorization → Bearer Token → `{{token}}`.

## Request Jamaah

### Kirim lokasi

`POST {{base_url}}/api/mobile/send-location`

```json
{
  "latitude": 21.422487,
  "longitude": 39.826206,
  "accuracy": 5.5,
  "speed": 1.2,
  "heading": 180,
  "battery_level": 87,
  "recorded_at": "2026-06-29T08:00:00+08:00"
}
```

### Kirim SOS

`POST {{base_url}}/api/mobile/sos`

```json
{
  "latitude": 21.422487,
  "longitude": 39.826206,
  "message": "Saya membutuhkan bantuan."
}
```

Endpoint GET lainnya:

- `{{base_url}}/api/mobile/profile`
- `{{base_url}}/api/mobile/hotel`
- `{{base_url}}/api/mobile/muthawwif-location`
- `{{base_url}}/api/mobile/my-location-history?date_from=2026-06-01&date_to=2026-06-29`

## Request Tour Leader

Login menggunakan akun Tour Leader, kemudian:

- `GET {{base_url}}/api/mobile/group-pilgrims`
- `GET {{base_url}}/api/mobile/group-locations`
- `GET {{base_url}}/api/mobile/group-sos?status=active`

## Request Muthawwif

Login menggunakan akun Muthawwif, kemudian:

- `GET {{base_url}}/api/mobile/assigned-pilgrims`
- `GET {{base_url}}/api/mobile/assigned-locations`
- `GET {{base_url}}/api/mobile/assigned-sos?status=active`

## Logout

`POST {{base_url}}/api/mobile/logout`

Token aktif akan dihapus. Respons validation, authentication, dan authorization selalu berbentuk JSON dengan status HTTP `422`, `401`, atau `403`.
