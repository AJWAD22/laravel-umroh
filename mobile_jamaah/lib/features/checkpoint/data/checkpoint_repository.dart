import '../../../core/network/api_client.dart';
import '../domain/checkpoint.dart';

class CheckpointRepository {
  CheckpointRepository(this._api);

  final ApiClient _api;

  Future<List<Checkpoint>> getCheckpoints() async {
    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        '/api/mobile/checkpoints',
      );
      final items = response.data?['data'] as List<dynamic>? ?? [];
      return items
          .map((item) => Checkpoint.fromJson(item as Map<String, dynamic>))
          .toList();
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  Future<Checkpoint> createMeetingPoint({
    required String name,
    required String city,
    required double latitude,
    required double longitude,
    String? address,
    String? description,
  }) async {
    try {
      final response = await _api.dio.post<Map<String, dynamic>>(
        '/api/mobile/staff-checkpoints',
        data: {
          'name': name,
          'city': city,
          'latitude': latitude,
          'longitude': longitude,
          if (address != null && address.trim().isNotEmpty)
            'address': address.trim(),
          if (description != null && description.trim().isNotEmpty)
            'description': description.trim(),
        },
      );
      final data = response.data?['data'] as Map<String, dynamic>? ?? {};
      return Checkpoint.fromJson(data);
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  Future<Checkpoint> updateMeetingPoint({
    required int id,
    required String name,
    required String city,
    required double latitude,
    required double longitude,
    String? address,
    String? description,
  }) async {
    try {
      final response = await _api.dio.patch<Map<String, dynamic>>(
        '/api/mobile/staff-checkpoints/$id',
        data: {
          'name': name,
          'city': city,
          'latitude': latitude,
          'longitude': longitude,
          if (address != null && address.trim().isNotEmpty)
            'address': address.trim(),
          if (description != null && description.trim().isNotEmpty)
            'description': description.trim(),
        },
      );
      final data = response.data?['data'] as Map<String, dynamic>? ?? {};
      return Checkpoint.fromJson(data);
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  Future<void> deactivateMeetingPoint(int id) async {
    try {
      await _api.dio.delete<void>('/api/mobile/staff-checkpoints/$id');
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }
}
