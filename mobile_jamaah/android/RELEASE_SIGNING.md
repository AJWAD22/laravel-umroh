# Release signing Mantau Umroh

APK release resmi sebaiknya memakai keystore sendiri, bukan debug key.

1. Buat keystore di folder `mobile_jamaah/android/app`:

```bash
keytool -genkey -v -keystore app/mantau-umroh-release.jks -keyalg RSA -keysize 2048 -validity 10000 -alias mantau-umroh
```

2. Salin file contoh:

```bash
cp key.properties.example key.properties
```

3. Isi `key.properties`:

```properties
storePassword=PASSWORD_KEYSTORE
keyPassword=PASSWORD_KEY
keyAlias=mantau-umroh
storeFile=app/mantau-umroh-release.jks
```

4. Build:

```bash
flutter build apk --release
```

Catatan penting:

- Jangan commit `key.properties`.
- Jangan commit file `.jks` atau `.keystore`.
- Simpan backup keystore dengan aman. Jika hilang, APK lama tidak bisa di-update dengan signature yang sama.
