import 'package:geolocator/geolocator.dart';

import '../../../core/network/api_client.dart';

class SosRepository {
  SosRepository(this._api);

  final ApiClient _api;

  Future<void> send({
    required Position position,
    String message = 'Jamaah meminta bantuan.',
  }) async {
    try {
      await _api.dio.post<Map<String, dynamic>>(
        '/api/mobile/sos',
        data: {
          'latitude': position.latitude,
          'longitude': position.longitude,
          'accuracy': position.accuracy,
          'message': message,
        },
      );
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }
}
