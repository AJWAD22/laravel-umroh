import '../../../core/network/api_client.dart';
import '../domain/staff_pilgrim.dart';
import '../domain/staff_sos_report.dart';

class StaffRepository {
  StaffRepository(this._api);

  final ApiClient _api;

  String _endpoint(String role, String leaderPath, String muthawwifPath) {
    return role == 'tour-leader' ? leaderPath : muthawwifPath;
  }

  Future<List<StaffPilgrim>> pilgrims(String role) async {
    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        _endpoint(
          role,
          '/api/mobile/group-pilgrims',
          '/api/mobile/assigned-pilgrims',
        ),
      );
      return _items(response.data).map(StaffPilgrim.fromJson).toList();
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  Future<List<StaffPilgrim>> locations(String role) async {
    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        _endpoint(
          role,
          '/api/mobile/group-locations',
          '/api/mobile/assigned-locations',
        ),
      );
      return _items(response.data).map((item) {
        final pilgrim = Map<String, dynamic>.from(
          item['pilgrim'] as Map? ?? {},
        );
        pilgrim['latest_location'] = item['location'];
        return StaffPilgrim.fromJson(pilgrim);
      }).toList();
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  Future<List<StaffSosReport>> sosReports() async {
    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        '/api/mobile/sos-reports',
      );
      return _items(response.data).map(StaffSosReport.fromJson).toList();
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  Future<StaffSosReport> acknowledgeSos(int id) async {
    try {
      final response = await _api.dio.post<Map<String, dynamic>>(
        '/api/mobile/sos-reports/$id/acknowledge',
      );
      return StaffSosReport.fromJson(
        Map<String, dynamic>.from(response.data?['data'] as Map? ?? {}),
      );
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  Future<StaffSosReport> resolveSos(int id) async {
    try {
      final response = await _api.dio.post<Map<String, dynamic>>(
        '/api/mobile/sos-reports/$id/resolve',
        data: const {'resolution_notes': 'Ditandai aman dari aplikasi petugas.'},
      );
      return StaffSosReport.fromJson(
        Map<String, dynamic>.from(response.data?['data'] as Map? ?? {}),
      );
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  List<Map<String, dynamic>> _items(Map<String, dynamic>? response) {
    final data = response?['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }
}
