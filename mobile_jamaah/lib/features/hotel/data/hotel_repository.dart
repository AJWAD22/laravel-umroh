import '../../../core/network/api_client.dart';
import '../domain/hotel.dart';

class HotelRepository {
  HotelRepository(this._api);

  final ApiClient _api;

  Future<List<Hotel>> getHotels() async {
    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        '/api/mobile/hotel',
      );
      final items = response.data?['data'] as List<dynamic>? ?? [];
      return items
          .map((item) => Hotel.fromJson(item as Map<String, dynamic>))
          .toList();
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }
}
