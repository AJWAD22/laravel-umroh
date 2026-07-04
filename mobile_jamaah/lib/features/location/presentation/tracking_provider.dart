import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

import '../../../core/config/app_config.dart';
import '../data/location_repository.dart';

class TrackingProvider extends ChangeNotifier {
  TrackingProvider(this._repository);

  final LocationRepository _repository;
  Timer? _timer;
  bool isTracking = false;
  bool isSending = false;
  String? error;
  Position? lastPosition;
  DateTime? lastSentAt;

  Future<void> start() async {
    if (isTracking) return;
    isTracking = true;
    error = null;
    notifyListeners();
    await _sendOnce();
    _schedule();
  }

  void stop() {
    isTracking = false;
    _timer?.cancel();
    _timer = null;
    notifyListeners();
  }

  void pauseForLifecycle() {
    _timer?.cancel();
    _timer = null;
  }

  void resumeForLifecycle() {
    if (isTracking && _timer == null) {
      _sendOnce();
      _schedule();
    }
  }

  void _schedule() {
    _timer?.cancel();
    _timer = Timer.periodic(AppConfig.trackingInterval, (_) => _sendOnce());
  }

  Future<void> _sendOnce() async {
    if (isSending) return;
    isSending = true;
    notifyListeners();
    try {
      final position = await _repository.currentPosition();
      await _repository.send(position);
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
    _timer?.cancel();
    super.dispose();
  }
}
