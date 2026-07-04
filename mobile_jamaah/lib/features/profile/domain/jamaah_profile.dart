class JamaahProfile {
  const JamaahProfile({
    required this.userId,
    required this.name,
    required this.email,
    required this.role,
    required this.profileId,
    required this.registrationNumber,
    required this.branchName,
    this.phone,
    this.photoUrl,
    this.monitoringStatus = 'normal',
    this.journey,
  });

  final int userId;
  final String name;
  final String email;
  final String role;
  final int profileId;
  final String registrationNumber;
  final String branchName;
  final String? phone;
  final String? photoUrl;
  final String monitoringStatus;
  final JourneyInfo? journey;

  factory JamaahProfile.fromJson(Map<String, dynamic> json) {
    final profile = json['profile'] as Map<String, dynamic>? ?? {};
    final branch = json['branch'] as Map<String, dynamic>? ?? {};
    return JamaahProfile(
      userId: json['id'] as int,
      name: profile['full_name']?.toString() ?? json['name']?.toString() ?? '-',
      email: json['email'].toString(),
      role: json['role']?.toString() ?? 'jamaah',
      profileId: profile['id'] as int? ?? 0,
      registrationNumber: profile['number']?.toString() ?? '-',
      branchName: branch['name']?.toString() ?? '-',
      phone: profile['phone']?.toString() ?? json['phone_number']?.toString(),
      photoUrl: profile['photo_url']?.toString(),
      monitoringStatus: profile['monitoring_status']?.toString() ?? 'normal',
      journey:
          json['journey'] is Map
              ? JourneyInfo.fromJson(
                Map<String, dynamic>.from(json['journey'] as Map),
              )
              : null,
    );
  }
}

class JourneyInfo {
  const JourneyInfo({
    required this.groupName,
    required this.groupCode,
    required this.programName,
    required this.status,
    this.departureDate,
    this.returnDate,
    this.departureAirport,
    this.arrivalAirport,
    this.tourLeaderName,
    this.muthawwifName,
  });

  final String groupName;
  final String groupCode;
  final String programName;
  final String status;
  final DateTime? departureDate;
  final DateTime? returnDate;
  final String? departureAirport;
  final String? arrivalAirport;
  final String? tourLeaderName;
  final String? muthawwifName;

  factory JourneyInfo.fromJson(Map<String, dynamic> json) {
    return JourneyInfo(
      groupName: json['group_name']?.toString() ?? '-',
      groupCode: json['group_code']?.toString() ?? '-',
      programName: json['program_name']?.toString() ?? '-',
      status: json['status']?.toString() ?? '-',
      departureDate: DateTime.tryParse(
        json['departure_date']?.toString() ?? '',
      ),
      returnDate: DateTime.tryParse(json['return_date']?.toString() ?? ''),
      departureAirport: json['departure_airport']?.toString(),
      arrivalAirport: json['arrival_airport']?.toString(),
      tourLeaderName: json['tour_leader_name']?.toString(),
      muthawwifName: json['muthawwif_name']?.toString(),
    );
  }
}
