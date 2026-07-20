# Pengujian API Mobile dengan Postman

Dokumen ini hanya memuat endpoint yang benar-benar digunakan aplikasi Flutter.

## Persiapan

1. Jalankan Laravel dan siapkan database.
2. Buat environment Postman dengan `base_url`.
3. Gunakan header `Accept: application/json`.
4. Untuk endpoint terproteksi, kirim `Authorization: Bearer {{token}}`.

## Endpoint publik

### Aktivasi PIN

- `POST {{base_url}}/api/mobile/activation/claim`
- `POST {{base_url}}/api/mobile/activation/status`

Aktivasi langsung disetujui sistem setelah PIN valid. Tidak ada endpoint
persetujuan manual Tour Leader.

### Login petugas

- `POST {{base_url}}/api/mobile/login`

```json
{
  "email": "petugas@mantauumroh.id",
  "password": "password",
  "device_name": "Android Petugas"
}
```

Simpan nilai `access_token` dari respons sebagai `token`.

## Endpoint semua pengguna mobile

- `GET /api/mobile/profile`
- `POST /api/mobile/device-token`
- `POST /api/mobile/logout`
- `GET /api/mobile/checkpoints`

## Endpoint Jamaah

- `POST /api/mobile/send-location`
- `POST /api/mobile/sos`
- `GET /api/mobile/staff-locations`

Contoh lokasi:

```json
{
  "latitude": 21.422487,
  "longitude": 39.826206,
  "accuracy": 5.5,
  "speed": 1.2,
  "heading": 180,
  "battery_level": 87
}
```

## Endpoint Tour Leader dan Muthawwif

- `POST /api/mobile/staff-location`
- `GET /api/mobile/sos-reports`
- `POST /api/mobile/sos-reports/{id}/acknowledge`
- `POST /api/mobile/sos-reports/{id}/resolve`

## Endpoint khusus Tour Leader

- `GET /api/mobile/group-pilgrims`
- `GET /api/mobile/group-locations`
- `POST /api/mobile/staff-checkpoints`
- `PATCH /api/mobile/staff-checkpoints/{id}`
- `DELETE /api/mobile/staff-checkpoints/{id}`

## Endpoint khusus Muthawwif

- `GET /api/mobile/assigned-pilgrims`
- `GET /api/mobile/assigned-locations`

Respons validasi, autentikasi, dan otorisasi menggunakan status HTTP `422`,
`401`, dan `403`. Endpoint yang tidak terdaftar di dokumen ini tidak boleh
dianggap sebagai bagian API aktif.
