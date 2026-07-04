import 'staff_pilgrim.dart';

class StaffSos {
  const StaffSos({
    required this.id,
    required this.pilgrim,
    required this.latitude,
    required this.longitude,
    required this.status,
    this.message,
    this.reportedAt,
  });

  final int id;
  final StaffPilgrim pilgrim;
  final double latitude;
  final double longitude;
  final String? message;
  final String status;
  final DateTime? reportedAt;

  factory StaffSos.fromJson(Map<String, dynamic> json) {
    return StaffSos(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      pilgrim: StaffPilgrim.fromJson(
        json['pilgrim'] as Map<String, dynamic>? ?? {},
      ),
      latitude: double.tryParse(json['latitude']?.toString() ?? '') ?? 0,
      longitude: double.tryParse(json['longitude']?.toString() ?? '') ?? 0,
      message: json['message']?.toString(),
      status: json['status']?.toString() ?? '-',
      reportedAt: DateTime.tryParse(json['reported_at']?.toString() ?? ''),
    );
  }
}
