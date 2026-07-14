import 'package:flutter/foundation.dart';

import '../data/staff_repository.dart';
import '../domain/staff_pilgrim.dart';
import '../domain/staff_sos_report.dart';

// State untuk layar petugas: daftar jamaah, lokasi terakhir, dan laporan SOS.
// Repository menangani HTTP; provider hanya mengatur loading/error dan refresh UI.
class StaffProvider extends ChangeNotifier {
  StaffProvider(this._repository);

  final StaffRepository _repository;
  List<StaffPilgrim> pilgrims = const [];
  List<StaffPilgrim> locations = const [];
  List<StaffSosReport> sosReports = const [];
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
        _repository.sosReports(),
      ]);
      pilgrims = results[0] as List<StaffPilgrim>;
      locations = results[1] as List<StaffPilgrim>;
      sosReports = results[2] as List<StaffSosReport>;
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
    sosReports = const [];
    error = null;
    _loadedRole = null;
    notifyListeners();
  }

  Future<void> acknowledgeSos(int id) async {
    final updated = await _repository.acknowledgeSos(id);
    _replaceSos(updated);
  }

  Future<void> resolveSos(int id) async {
    final updated = await _repository.resolveSos(id);
    _replaceSos(updated);
  }

  StaffSosReport? findSosById(int id) {
    for (final report in sosReports) {
      if (report.id == id) return report;
    }
    return null;
  }

  void _replaceSos(StaffSosReport updated) {
    sosReports =
        sosReports
            .map((report) => report.id == updated.id ? updated : report)
            .toList(growable: false);
    notifyListeners();
  }
}
