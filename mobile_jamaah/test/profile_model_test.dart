import 'package:flutter_test/flutter_test.dart';
import 'package:umrah_jamaah/features/profile/domain/jamaah_profile.dart';

void main() {
  test('parses jamaah profile returned by Laravel API', () {
    final profile = JamaahProfile.fromJson({
      'id': 10,
      'name': 'Jamaah Demo',
      'email': 'jamaah@umrah.test',
      'role': 'jamaah',
      'branch': {'id': 1, 'name': 'Cabang Banjarmasin'},
      'profile': {
        'id': 20,
        'number': 'JMH-001',
        'full_name': 'Jamaah Demo',
        'phone': '08123456789',
        'photo_url': null,
        'monitoring_status': 'normal',
      },
    });

    expect(profile.name, 'Jamaah Demo');
    expect(profile.registrationNumber, 'JMH-001');
    expect(profile.branchName, 'Cabang Banjarmasin');
    expect(profile.monitoringStatus, 'normal');
  });
}
