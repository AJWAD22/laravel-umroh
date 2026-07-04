import 'package:flutter_test/flutter_test.dart';
import 'package:umrah_jamaah/features/profile/domain/jamaah_profile.dart';
import 'package:umrah_jamaah/features/staff/domain/staff_pilgrim.dart';
import 'package:umrah_jamaah/features/staff/domain/staff_sos.dart';

void main() {
  test('parses Tour Leader profile for role routing', () {
    final profile = JamaahProfile.fromJson({
      'id': 2,
      'name': 'Tour Leader Mobile',
      'email': 'tourleader@umrah.test',
      'role': 'tour-leader',
      'branch': {'name': 'Cabang Demo'},
      'profile': {
        'id': 4,
        'number': 'TL-001',
        'full_name': 'Tour Leader Mobile',
      },
    });

    expect(profile.role, 'tour-leader');
    expect(profile.registrationNumber, 'TL-001');
  });

  test('parses assigned pilgrim location and SOS response', () {
    final pilgrimJson = {
      'id': 10,
      'registration_number': 'JMH-001',
      'full_name': 'Jamaah Demo',
      'status': 'active',
      'monitoring_status': 'sos',
      'latest_location': {
        'latitude': -7.1,
        'longitude': 112.7,
        'recorded_at': '2026-06-29T10:00:00+08:00',
      },
    };
    final pilgrim = StaffPilgrim.fromJson(pilgrimJson);
    final report = StaffSos.fromJson({
      'id': 7,
      'pilgrim': pilgrimJson,
      'latitude': -7.1,
      'longitude': 112.7,
      'status': 'pending',
    });

    expect(pilgrim.location?.longitude, 112.7);
    expect(report.pilgrim.monitoringStatus, 'sos');
  });
}
