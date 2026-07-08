import '../../staff/domain/staff_pilgrim.dart';

class StaffContact {
  const StaffContact({
    required this.role,
    required this.label,
    required this.fullName,
    this.phone,
    this.location,
  });

  final String role;
  final String label;
  final String? fullName;
  final String? phone;
  final StaffLocation? location;

  bool get hasProfile => fullName != null && fullName!.trim().isNotEmpty;
  bool get hasLocation => location != null;

  factory StaffContact.fromJson(Map<String, dynamic> json) {
    return StaffContact(
      role: json['role']?.toString() ?? '',
      label: json['label']?.toString() ?? 'Petugas',
      fullName: json['full_name']?.toString(),
      phone: json['phone']?.toString(),
      location:
          json['location'] is Map<String, dynamic>
              ? StaffLocation.fromJson(json['location'] as Map<String, dynamic>)
              : null,
    );
  }
}
