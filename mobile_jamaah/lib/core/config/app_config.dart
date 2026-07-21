class AppConfig {
  const AppConfig._();

  // URL backend dapat diganti saat build menggunakan --dart-define.
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://mantauumroh.web.id',
  );

  // Interval pengiriman lokasi berkala; 20 detik dipilih agar lebih realtime
  // namun tetap hemat baterai dan kuota untuk sistem skala kecil.
  static const trackingInterval = Duration(seconds: 20);
}
