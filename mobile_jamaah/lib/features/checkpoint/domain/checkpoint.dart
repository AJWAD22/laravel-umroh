class Checkpoint {
  const Checkpoint({
    required this.id,
    required this.name,
    required this.category,
    required this.city,
    required this.latitude,
    required this.longitude,
    this.address,
    this.description,
  });

  final int id;
  final String name;
  final String category;
  final String city;
  final double latitude;
  final double longitude;
  final String? address;
  final String? description;

  factory Checkpoint.fromJson(Map<String, dynamic> json) => Checkpoint(
    id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
    name: json['name']?.toString() ?? '-',
    category: json['category']?.toString() ?? 'lainnya',
    city: json['city']?.toString() ?? 'other',
    latitude: double.tryParse(json['latitude']?.toString() ?? '') ?? 0,
    longitude: double.tryParse(json['longitude']?.toString() ?? '') ?? 0,
    address: json['address']?.toString(),
    description: json['description']?.toString(),
  );
}
