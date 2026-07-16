import 'package:geolocator/geolocator.dart';
import '../../../core/config/app_config.dart';
import '../../../core/network/api_client.dart';

class LocationRepository {
  LocationRepository(this._api);

  final ApiClient _api;

  // Mengambil posisi sekali saja. Dipakai saat fitur membutuhkan
  // lokasi saat ini, misalnya membuat titik kumpul atau membuka peta arah.
  Future<Position> currentPosition() async {
    await _ensureLocationPermission();

    return Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
    );
  }

  // Membuka stream GPS untuk tracking berkala selama aplikasi aktif.
  // Interval pengiriman diatur dari AppConfig.trackingInterval.
  Future<Stream<Position>> foregroundPositions() async {
    await _ensureLocationPermission();

    return Geolocator.getPositionStream(
      locationSettings: AndroidSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 0,
        intervalDuration: AppConfig.trackingInterval,
        foregroundNotificationConfig: const ForegroundNotificationConfig(
          notificationTitle: 'Mantau Umroh',
          notificationText: 'Tracking aktif - lokasi dikirim setiap 60 detik.',
          notificationChannelName: 'Tracking Jamaah',
          enableWakeLock: true,
          setOngoing: true,
        ),
      ),
    );
  }

  // Stream untuk halaman navigasi internal.
  // Marker akan diperbarui saat pengguna bergerak sekitar 3 meter.
  Future<Stream<Position>> navigationPositions() async {
    await _ensureLocationPermission();

    return Geolocator.getPositionStream(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 3,
      ),
    );
  }

  // Semua fitur lokasi wajib melewati pengecekan ini.
  // Jika izin belum diberikan, aplikasi meminta izin ke pengguna.
  Future<void> _ensureLocationPermission() async {
    if (!await Geolocator.isLocationServiceEnabled()) {
      throw Exception('Layanan lokasi perangkat belum aktif.');
    }
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied) {
      throw Exception('Izin lokasi ditolak.');
    }
    if (permission == LocationPermission.deniedForever) {
      throw Exception(
        'Izin lokasi ditolak permanen. Aktifkan melalui pengaturan perangkat.',
      );
    }
  }

  // Mengirim lokasi jamaah ke backend Laravel.
  Future<void> send(Position position) async {
    await _sendTo('/api/mobile/send-location', position);
  }

  // Mengirim lokasi petugas ke backend Laravel.
  Future<void> sendStaff(Position position) async {
    await _sendTo('/api/mobile/staff-location', position);
  }

  // Format payload lokasi dibuat seragam untuk jamaah dan petugas.
  Future<void> _sendTo(String endpoint, Position position) async {
    try {
      await _api.dio.post<void>(
        endpoint,
        data: {
          'latitude': position.latitude,
          'longitude': position.longitude,
          'accuracy': position.accuracy,
          'speed': position.speed < 0 ? 0 : position.speed,
          'heading': position.heading < 0 ? 0 : position.heading,
          'recorded_at': position.timestamp.toIso8601String(),
        },
      );
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }
}

