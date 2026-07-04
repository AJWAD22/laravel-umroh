class ActivationPilgrim {
  const ActivationPilgrim({
    required this.id,
    required this.registrationNumber,
    required this.fullName,
    required this.activationStatus,
    this.deviceName,
    this.activationPin,
    this.photoUrl,
  });

  final int id;
  final String registrationNumber;
  final String fullName;
  final String activationStatus;
  final String? deviceName;
  final String? activationPin;
  final String? photoUrl;

  factory ActivationPilgrim.fromJson(Map<String, dynamic> json) {
    return ActivationPilgrim(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      registrationNumber: json['registration_number']?.toString() ?? '-',
      fullName: json['full_name']?.toString() ?? '-',
      activationStatus:
          json['activation_status']?.toString() ?? 'not_activated',
      deviceName: json['device_name']?.toString(),
      activationPin: json['activation_pin']?.toString(),
      photoUrl: json['photo_url']?.toString(),
    );
  }
}

class PendingActivation {
  const PendingActivation({
    required this.publicId,
    required this.pilgrimName,
    required this.registrationNumber,
    required this.deviceName,
    required this.expiresAt,
  });

  final String publicId;
  final String pilgrimName;
  final String registrationNumber;
  final String deviceName;
  final DateTime expiresAt;

  factory PendingActivation.fromJson(Map<String, dynamic> json) {
    return PendingActivation(
      publicId: json['public_id'].toString(),
      pilgrimName: json['pilgrim_name']?.toString() ?? '-',
      registrationNumber: json['registration_number']?.toString() ?? '-',
      deviceName: json['device_name']?.toString() ?? 'Perangkat Android',
      expiresAt:
          DateTime.tryParse(json['expires_at']?.toString() ?? '') ??
          DateTime.now(),
    );
  }
}

class ActivationClaim {
  const ActivationClaim({
    required this.publicId,
    required this.claimSecret,
    required this.deviceUuid,
    required this.pilgrimName,
  });

  final String publicId;
  final String claimSecret;
  final String deviceUuid;
  final String pilgrimName;
}
