import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

import '../data/location_repository.dart';

class TrackingProvider extends ChangeNotifier {
  TrackingProvider(this._repository);

  final LocationRepository _repository;
  StreamSubscription<Position>? _positionSubscription;
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
    try {
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
      error = exception.toString().replaceFirst('Exception: ', '');
      notifyListeners();
    }
  }

  Future<void> stop() async {
    isTracking = false;
    await _positionSubscription?.cancel();
    _positionSubscription = null;
    notifyListeners();
  }

  Future<void> _send(Position position) async {
    if (isSending) return;
    isSending = true;
    notifyListeners();
    try {
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
    _positionSubscription?.cancel();
    super.dispose();
  }
}
