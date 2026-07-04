import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'dart:math';

class SecureStorageService {
  static const _tokenKey = 'mobile_access_token';
  static const _deviceUuidKey = 'mobile_device_uuid';
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<void> clearToken() => _storage.delete(key: _tokenKey);

  Future<String> deviceUuid() async {
    final existing = await _storage.read(key: _deviceUuidKey);
    if (existing != null && existing.isNotEmpty) return existing;

    final random = Random.secure();
    final value =
        List.generate(
          32,
          (_) => random.nextInt(256).toRadixString(16).padLeft(2, '0'),
        ).join();
    await _storage.write(key: _deviceUuidKey, value: value);

    return value;
  }
}
