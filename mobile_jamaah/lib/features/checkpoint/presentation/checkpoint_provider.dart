import 'package:flutter/foundation.dart';

import '../data/checkpoint_repository.dart';
import '../domain/checkpoint.dart';

class CheckpointProvider extends ChangeNotifier {
  CheckpointProvider(this._repository);

  final CheckpointRepository _repository;
  List<Checkpoint> checkpoints = const [];
  bool isLoading = false;
  bool isCreating = false;
  String? error;

  Future<void> load() async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      checkpoints = await _repository.getCheckpoints();
    } catch (exception) {
      error = exception.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> createMeetingPoint({
    required String name,
    required String city,
    required double latitude,
    required double longitude,
    String? address,
    String? description,
  }) async {
    isCreating = true;
    error = null;
    notifyListeners();
    try {
      final created = await _repository.createMeetingPoint(
        name: name,
        city: city,
        latitude: latitude,
        longitude: longitude,
        address: address,
        description: description,
      );
      checkpoints = [created, ...checkpoints];
    } catch (exception) {
      error = exception.toString();
      rethrow;
    } finally {
      isCreating = false;
      notifyListeners();
    }
  }
}
