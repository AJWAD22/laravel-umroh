import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

import '../data/location_repository.dart';

class TrackingProvider extends ChangeNotifier {
  TrackingProvider(this._repository);

  final LocationRepository _repository;
  StreamSubscription<Position>? _positionSubscription;
  bool isTracking = false;
  bool isStaffTracking = false;
  bool isSending = false;
  String? error;
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
    await _positionSubscription?.cancel();
    _positionSubscription = null;
    notifyListeners();
  }

  Future<void> _send(Position position) async {
    if (isSending) return;
    isSending = true;
    notifyListeners();
    try {
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
