class ActivationPinInfo {
  const ActivationPinInfo({
    required this.pilgrimId,
    required this.registrationNumber,
    required this.fullName,
    required this.pin,
    this.generatedAt,
  });

  final int pilgrimId;
  final String registrationNumber;
  final String fullName;
  final String pin;
  final DateTime? generatedAt;

  factory ActivationPinInfo.fromJson(Map<String, dynamic> json) {
    return ActivationPinInfo(
      pilgrimId: int.tryParse(json['pilgrim_id']?.toString() ?? '') ?? 0,
      registrationNumber: json['registration_number']?.toString() ?? '-',
      fullName: json['full_name']?.toString() ?? '-',
      pin: json['pin']?.toString() ?? '',
      generatedAt: DateTime.tryParse(json['generated_at']?.toString() ?? ''),
    );
  }
}
