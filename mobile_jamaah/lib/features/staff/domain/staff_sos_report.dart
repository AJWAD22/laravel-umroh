import 'staff_pilgrim.dart';

class StaffSosReport {
  const StaffSosReport({
    required this.id,
    required this.status,
    required this.latitude,
    required this.longitude,
    required this.reportedAt,
    required this.pilgrim,
    this.accuracy,
    this.message,
    this.groupName,
    this.handlerName,
  });

  final int id;
  final String status;
  final double latitude;
  final double longitude;
  final double? accuracy;
  final String? message;
  final DateTime? reportedAt;
  final StaffPilgrim pilgrim;
  final String? groupName;
  final String? handlerName;

  bool get isActive => status == 'new' || status == 'handling';

  factory StaffSosReport.fromJson(Map<String, dynamic> json) {
    return StaffSosReport(
      id: _asInt(json['id']),
      status: json['status']?.toString() ?? 'new',
      latitude: _asDouble(json['latitude']),
      longitude: _asDouble(json['longitude']),
      accuracy: _nullableDouble(json['accuracy']),
      message: json['message']?.toString(),
      reportedAt: DateTime.tryParse(json['reported_at']?.toString() ?? ''),
      pilgrim: StaffPilgrim.fromJson(
        Map<String, dynamic>.from(json['pilgrim'] as Map? ?? {}),
      ),
      groupName: (json['group'] as Map?)?['name']?.toString(),
      handlerName: (json['handler'] as Map?)?['name']?.toString(),
    );
  }
}

int _asInt(Object? value) => int.tryParse(value?.toString() ?? '') ?? 0;
double _asDouble(Object? value) =>
    double.tryParse(value?.toString() ?? '') ?? 0;
double? _nullableDouble(Object? value) =>
    value == null ? null : double.tryParse(value.toString());
