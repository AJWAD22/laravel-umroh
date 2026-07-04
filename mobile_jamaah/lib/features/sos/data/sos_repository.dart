import 'package:geolocator/geolocator.dart';

import '../../../core/network/api_client.dart';

class SosRepository {
  SosRepository(this._api);

  final ApiClient _api;

  Future<void> send(Position position, {String? message}) async {
    try {
      await _api.dio.post<void>(
        '/api/mobile/sos',
        data: {
          'latitude': position.latitude,
          'longitude': position.longitude,
          'message': message,
        },
      );
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }
}
