import 'package:flutter/foundation.dart';

import '../data/checkpoint_repository.dart';
import '../domain/checkpoint.dart';

class CheckpointProvider extends ChangeNotifier {
  CheckpointProvider(this._repository);

  final CheckpointRepository _repository;
  List<Checkpoint> checkpoints = const [];
  bool isLoading = false;
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
}
