import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

import '../data/location_repository.dart';

class TrackingProvider extends ChangeNotifier {
  TrackingProvider(this._repository);

  final LocationRepository _repository;

  // Subscription ini menyimpan aliran GPS dari perangkat.
  // Saat stop/logout, subscription harus dibatalkan agar tracking berhenti.
  StreamSubscription<Position>? _positionSubscription;

  // isTracking dipakai UI untuk menampilkan status tracking aktif/tidak.
  bool isTracking = false;
  bool isStaffTracking = false;
  bool isSending = false;
  String? error;

  // Posisi terakhir yang berhasil dikirim ke server.
  Position? lastPosition;
  DateTime? lastSentAt;
  bool _sendAsStaff = false;

  Future<void> start({bool asStaff = false}) async {
    if (isTracking) return;
    isTracking = true;
    isStaffTracking = asStaff;
    _sendAsStaff = asStaff;
    error = null;
    notifyListeners();
    try {
      // Kirim satu posisi segera saat tracking dimulai. Tanpa langkah ini,
      // perangkat yang diam dapat menunggu terlalu lama sebelum stream GPS
      // menghasilkan data pertama dan marker belum muncul di Live Map.
      final initialPosition = await _repository.currentPosition();
      await _send(initialPosition);

      // Repository mengecek izin GPS, lalu membuka stream lokasi foreground.
      final positions = await _repository.foregroundPositions();
      _positionSubscription = positions.listen(
        _send,
        onError: (Object exception) {
          error = exception.toString().replaceFirst('Exception: ', '');
          notifyListeners();
        },
      );
    } catch (exception) {
      isTracking = false;
      isStaffTracking = false;
      error = exception.toString().replaceFirst('Exception: ', '');
      notifyListeners();
    }
  }

  Future<void> stop() async {
    isTracking = false;
    isStaffTracking = false;

    // Bagian ini penting: ketika logout atau token tidak valid,
    // stream GPS dihentikan agar perangkat tidak terus mengirim lokasi.
    await _positionSubscription?.cancel();
    _positionSubscription = null;
    notifyListeners();
  }

  Future<void> _send(Position position) async {
    if (isSending) return;
    isSending = true;
    notifyListeners();
    try {
      // Jamaah mengirim ke endpoint send-location.
      // Petugas mengirim ke endpoint staff-location.
      if (_sendAsStaff) {
        await _repository.sendStaff(position);
      } else {
        await _repository.send(position);
      }
      lastPosition = position;
      lastSentAt = DateTime.now();
      error = null;
    } catch (exception) {
      error = exception.toString().replaceFirst('Exception: ', '');
    } finally {
      isSending = false;
      notifyListeners();
    }
  }

  @override
  void dispose() {
    _positionSubscription?.cancel();
    super.dispose();
  }
}
