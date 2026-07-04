class Hotel {
  const Hotel({
    required this.id,
    required this.name,
    required this.city,
    this.address,
    this.latitude,
    this.longitude,
  });

  final int id;
  final String name;
  final String city;
  final String? address;
  final double? latitude;
  final double? longitude;

  factory Hotel.fromJson(Map<String, dynamic> json) => Hotel(
    id: json['id'] as int,
    name: json['name'].toString(),
    city: json['city'].toString(),
    address: json['address']?.toString(),
    latitude: (json['latitude'] as num?)?.toDouble(),
    longitude: (json['longitude'] as num?)?.toDouble(),
  );
}
