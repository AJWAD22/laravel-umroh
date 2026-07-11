import 'package:flutter/foundation.dart';

import '../data/staff_repository.dart';
import '../domain/staff_pilgrim.dart';

class StaffProvider extends ChangeNotifier {
  StaffProvider(this._repository);

  final StaffRepository _repository;
  List<StaffPilgrim> pilgrims = const [];
  List<StaffPilgrim> locations = const [];
  bool isLoading = false;
  String? error;
  String? _loadedRole;

  Future<void> load(String role, {bool force = false}) async {
    if (!force && _loadedRole == role && !isLoading) return;
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      final results = await Future.wait([
        _repository.pilgrims(role),
        _repository.locations(role),
      ]);
      pilgrims = results[0] as List<StaffPilgrim>;
      locations = results[1] as List<StaffPilgrim>;
      _loadedRole = role;
    } catch (exception) {
      error = exception.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  void clear() {
    pilgrims = const [];
    locations = const [];
    error = null;
    _loadedRole = null;
    notifyListeners();
  }
}
