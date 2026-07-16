import '../../../core/network/api_client.dart';
import '../domain/checkpoint.dart';

class CheckpointRepository {
  CheckpointRepository(this._api);

  final ApiClient _api;

  // Mengambil semua tujuan aktif yang boleh dilihat pengguna.
  // Isinya gabungan tujuan umum dari admin dan titik kumpul dari petugas.
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

  // Petugas membuat titik kumpul dari aplikasi.
  // Data ini tersimpan sebagai checkpoint khusus lapangan.
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

  // Petugas memperbarui titik kumpul yang pernah dibuat.
  // Yang bisa diubah: nama, kota, koordinat, alamat, dan keterangan.
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

  // Hapus di aplikasi berarti menonaktifkan titik kumpul.
  // Data lama tetap aman di database, tetapi tidak tampil lagi ke jamaah.
  Future<void> deactivateMeetingPoint(int id) async {
    try {
      await _api.dio.delete<void>('/api/mobile/staff-checkpoints/$id');
    } catch (error) {
      throw _api.errorFrom(error);
    }
  }
}
