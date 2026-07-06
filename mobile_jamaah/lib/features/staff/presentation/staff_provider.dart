import 'package:flutter/foundation.dart';

import '../data/staff_repository.dart';
import '../domain/staff_pilgrim.dart';
import '../domain/staff_sos.dart';

class StaffProvider extends ChangeNotifier {
  StaffProvider(this._repository);

  final StaffRepository _repository;
  List<StaffPilgrim> pilgrims = const [];
  List<StaffPilgrim> locations = const [];
  List<StaffSos> sosReports = const [];
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
        _repository.sos(role),
      ]);
      pilgrims = results[0] as List<StaffPilgrim>;
      locations = results[1] as List<StaffPilgrim>;
      sosReports = results[2] as List<StaffSos>;
      _loadedRole = role;
    } catch (exception) {
      error = exception.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> resolveSos(String role, int reportId) async {
    await _repository.resolveSos(role, reportId);
    await load(role, force: true);
  }

  void clear() {
    pilgrims = const [];
    locations = const [];
    sosReports = const [];
    error = null;
    _loadedRole = null;
    notifyListeners();
  }
}
