import '../../../core/network/api_client.dart';
import '../../../core/storage/secure_storage_service.dart';
import '../../profile/domain/jamaah_profile.dart';

class AuthRepository {
  AuthRepository(this._api, this._storage);

  final ApiClient _api;
  final SecureStorageService _storage;

  Future<JamaahProfile> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _api.dio.post<Map<String, dynamic>>(
        '/api/mobile/login',
        data: {
          'email': email,
          'password': password,
          'device_name': 'Flutter Jamaah',
        },
      );
      final data = response.data!;
      final role = data['role']?.toString();
      if (!{'jamaah', 'tour-leader', 'muthawwif'}.contains(role)) {
        throw const FormatException('Role akun tidak didukung aplikasi.');
      }
      await _storage.saveToken(data['access_token'].toString());
      return JamaahProfile.fromJson(data['user'] as Map<String, dynamic>);
    } catch (error) {
      await _storage.clearToken();
      throw _api.errorFrom(error);
    }
  }

  Future<JamaahProfile> profile() async {
    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        '/api/mobile/profile',
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      final role = data['role']?.toString();
      if (!{'jamaah', 'tour-leader', 'muthawwif'}.contains(role)) {
        throw const FormatException('Role token tidak didukung aplikasi.');
      }
      return JamaahProfile.fromJson(data);
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }

  Future<bool> hasToken() async {
    final token = await _storage.readToken();
    return token != null && token.isNotEmpty;
  }

  // Dipakai saat server mencabut token sehingga tidak memanggil endpoint
  // logout yang memang sudah tidak dapat diakses oleh token tersebut.
  Future<void> clearLocalSession() => _storage.clearToken();

  Future<void> logout() async {
    // Jika token sudah dicabut server, tidak perlu mengirim request logout
    // yang pasti akan dibalas 401. Sesi lokal tetap dibersihkan.
    if (!await hasToken()) {
      await _storage.clearToken();
      return;
    }
    try {
      await _api.dio.post<void>('/api/mobile/logout');
    } catch (_) {
      // Token lokal tetap harus dibersihkan jika perangkat sedang offline.
    } finally {
      await _storage.clearToken();
    }
  }
}
