class Checkpoint {
  const Checkpoint({
    required this.id,
    required this.name,
    required this.category,
    required this.city,
    required this.latitude,
    required this.longitude,
    this.departureId,
    this.groupId,
    this.address,
    this.geofenceRadiusMeters,
    this.description,
  });

  final int id;
  final String name;
  final String category;
  final String city;
  final double latitude;
  final double longitude;
  final int? departureId;
  final int? groupId;
  final String? address;
  final int? geofenceRadiusMeters;
  final String? description;

  factory Checkpoint.fromJson(Map<String, dynamic> json) => Checkpoint(
    id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
    name: json['name']?.toString() ?? '-',
    category: json['category']?.toString() ?? 'lainnya',
    city: json['city']?.toString() ?? 'other',
    latitude: double.tryParse(json['latitude']?.toString() ?? '') ?? 0,
    longitude: double.tryParse(json['longitude']?.toString() ?? '') ?? 0,
    departureId: int.tryParse(json['departure_id']?.toString() ?? ''),
    groupId: int.tryParse(json['group_id']?.toString() ?? ''),
    address: json['address']?.toString(),
    geofenceRadiusMeters: int.tryParse(
      json['geofence_radius_meters']?.toString() ?? '',
    ),
    description: json['description']?.toString(),
  );
}
