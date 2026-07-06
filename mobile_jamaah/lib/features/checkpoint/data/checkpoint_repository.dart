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
}
