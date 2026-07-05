import 'package:dio/dio.dart';

import '../config/app_config.dart';
import '../storage/secure_storage_service.dart';
import 'api_exception.dart';

class ApiClient {
  ApiClient(this._storage)
    : dio = Dio(
        BaseOptions(
          baseUrl: AppConfig.apiBaseUrl,
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 15),
          headers: const {'Accept': 'application/json'},
        ),
      ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _storage.readToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  final SecureStorageService _storage;
  final Dio dio;

  ApiException errorFrom(Object error) {
    if (error is DioException) {
      final data = error.response?.data;
      String message = _connectionMessage(error);
      if (data is Map<String, dynamic>) {
        message = data['message']?.toString() ?? message;
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) {
            message = first.first.toString();
          }
        }
      }
      return ApiException(message, statusCode: error.response?.statusCode);
    }
    return ApiException(error.toString());
  }

  String _connectionMessage(DioException error) {
    if (error.response != null) {
      return 'Terjadi kesalahan pada server.';
    }

    return 'Tidak dapat terhubung ke server Mantau Umroh. '
        'Periksa koneksi internet Anda, lalu coba kembali.';
  }
}
