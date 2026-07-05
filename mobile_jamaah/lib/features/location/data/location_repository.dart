import 'package:geolocator/geolocator.dart';
import '../../../core/config/app_config.dart';
import '../../../core/network/api_client.dart';

class LocationRepository {
  LocationRepository(this._api);

  final ApiClient _api;

  Future<Position> currentPosition() async {
    await _ensureLocationPermission();

    return Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
    );
  }

  Future<Stream<Position>> foregroundPositions() async {
    await _ensureLocationPermission();

    return Geolocator.getPositionStream(
      locationSettings: AndroidSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 0,
        intervalDuration: AppConfig.trackingInterval,
        foregroundNotificationConfig: const ForegroundNotificationConfig(
          notificationTitle: 'Mantau Umroh',
          notificationText:
              'Lokasi Anda sedang dibagikan kepada petugas rombongan.',
          notificationChannelName: 'Tracking Jamaah',
          enableWakeLock: true,
          setOngoing: true,
        ),
      ),
    );
  }

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

  Future<void> send(Position position) async {
    try {
      await _api.dio.post<void>(
        '/api/mobile/send-location',
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
