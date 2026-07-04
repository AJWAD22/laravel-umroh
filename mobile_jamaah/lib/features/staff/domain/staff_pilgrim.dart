class StaffPilgrim {
  const StaffPilgrim({
    required this.id,
    required this.registrationNumber,
    required this.fullName,
    required this.status,
    required this.monitoringStatus,
    this.phone,
    this.photoUrl,
    this.branchName,
    this.location,
  });

  final int id;
  final String registrationNumber;
  final String fullName;
  final String? phone;
  final String? photoUrl;
  final String status;
  final String monitoringStatus;
  final String? branchName;
  final StaffLocation? location;

  factory StaffPilgrim.fromJson(Map<String, dynamic> json) {
    return StaffPilgrim(
      id: _asInt(json['id']),
      registrationNumber: json['registration_number']?.toString() ?? '-',
      fullName: json['full_name']?.toString() ?? '-',
      phone: json['phone']?.toString(),
      photoUrl: json['photo_url']?.toString(),
      status: json['status']?.toString() ?? '-',
      monitoringStatus: json['monitoring_status']?.toString() ?? 'normal',
      branchName:
          (json['branch'] as Map<String, dynamic>?)?['name']?.toString(),
      location:
          json['latest_location'] is Map<String, dynamic>
              ? StaffLocation.fromJson(
                json['latest_location'] as Map<String, dynamic>,
              )
              : null,
    );
  }
}

class StaffLocation {
  const StaffLocation({
    required this.latitude,
    required this.longitude,
    this.accuracy,
    this.speed,
    this.heading,
    this.batteryLevel,
    this.recordedAt,
  });

  final double latitude;
  final double longitude;
  final double? accuracy;
  final double? speed;
  final double? heading;
  final int? batteryLevel;
  final DateTime? recordedAt;

  factory StaffLocation.fromJson(Map<String, dynamic> json) {
    return StaffLocation(
      latitude: _asDouble(json['latitude']),
      longitude: _asDouble(json['longitude']),
      accuracy: _nullableDouble(json['accuracy']),
      speed: _nullableDouble(json['speed']),
      heading: _nullableDouble(json['heading']),
      batteryLevel:
          json['battery_level'] == null ? null : _asInt(json['battery_level']),
      recordedAt: DateTime.tryParse(json['recorded_at']?.toString() ?? ''),
    );
  }
}

int _asInt(Object? value) => int.tryParse(value?.toString() ?? '') ?? 0;
double _asDouble(Object? value) =>
    double.tryParse(value?.toString() ?? '') ?? 0;
double? _nullableDouble(Object? value) =>
    value == null ? null : double.tryParse(value.toString());
