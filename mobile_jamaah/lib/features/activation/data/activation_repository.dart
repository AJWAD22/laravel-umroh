import '../../../core/network/api_client.dart';
import '../../../core/storage/secure_storage_service.dart';
import '../../profile/domain/jamaah_profile.dart';
import '../domain/activation_models.dart';

class ActivationRepository {
  ActivationRepository(this._api, this._storage);

  final ApiClient _api;
  final SecureStorageService _storage;

  Future<ActivationClaim> claim({required String numericCode}) async {
    try {
      final deviceUuid = await _storage.deviceUuid();
      final response = await _api.dio.post<Map<String, dynamic>>(
        '/api/mobile/activation/claim',
        data: {
          'numeric_code': numericCode,
          'device_uuid': deviceUuid,
          'device_name': 'Perangkat Android Jamaah',
          'platform': 'android',
        },
      );
      final data = Map<String, dynamic>.from(response.data!['data'] as Map);
      return ActivationClaim(
        publicId: data['public_id'].toString(),
        claimSecret: data['claim_secret'].toString(),
        deviceUuid: deviceUuid,
        pilgrimName: data['pilgrim_name']?.toString() ?? 'Jamaah',
      );
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  Future<JamaahProfile?> activationStatus(ActivationClaim claim) async {
    try {
      final response = await _api.dio.post<Map<String, dynamic>>(
        '/api/mobile/activation/status',
        data: {
          'public_id': claim.publicId,
          'claim_secret': claim.claimSecret,
          'device_uuid': claim.deviceUuid,
        },
      );
      final data = Map<String, dynamic>.from(response.data!['data'] as Map);
      if (data['status']?.toString() != 'completed') return null;

      await _storage.saveToken(data['access_token'].toString());
      return JamaahProfile.fromJson(
        Map<String, dynamic>.from(data['user'] as Map),
      );
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }
}
