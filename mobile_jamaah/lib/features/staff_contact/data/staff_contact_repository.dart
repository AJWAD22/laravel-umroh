import '../../../core/network/api_client.dart';
import '../domain/staff_contact.dart';

class StaffContactRepository {
  StaffContactRepository(this._api);

  final ApiClient _api;

  Future<List<StaffContact>> getStaffContacts() async {
    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        '/api/mobile/staff-locations',
      );
      final data = response.data?['data'];
      if (data is! List) return [];

      return data
          .whereType<Map<String, dynamic>>()
          .map(StaffContact.fromJson)
          .toList();
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }
}
