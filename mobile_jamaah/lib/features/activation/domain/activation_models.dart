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
