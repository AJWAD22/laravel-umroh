import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../../../core/utils/external_navigation.dart';
import '../domain/staff_pilgrim.dart';

class StaffPilgrimMapScreen extends StatelessWidget {
  const StaffPilgrimMapScreen({super.key, required this.pilgrim});

  final StaffPilgrim pilgrim;

  @override
  Widget build(BuildContext context) {
    final location = pilgrim.location!;
    final point = LatLng(location.latitude, location.longitude);
    final isSos = pilgrim.monitoringStatus == 'sos';

    return Scaffold(
      appBar: AppBar(title: Text('Lokasi ${pilgrim.fullName}')),
      body: Stack(
        children: [
          FlutterMap(
            options: MapOptions(initialCenter: point, initialZoom: 17),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'id.umrahmonitor.umrah_jamaah',
              ),
              MarkerLayer(
                markers: [
                  Marker(
                    point: point,
                    width: 64,
                    height: 64,
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        color: isSos ? Colors.red : Colors.blue,
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 3),
                        boxShadow: const [
                          BoxShadow(
                            color: Colors.black26,
                            blurRadius: 10,
                            offset: Offset(0, 4),
                          ),
                        ],
                      ),
                      child: const Icon(
                        Icons.person_pin_circle_rounded,
                        color: Colors.white,
                        size: 34,
                      ),
                    ),
                  ),
                ],
              ),
              RichAttributionWidget(
                attributions: const [
                  TextSourceAttribution('OpenStreetMap contributors'),
                ],
              ),
            ],
          ),
          Positioned(
            left: 16,
            right: 16,
            bottom: 18,
            child: SafeArea(
              top: false,
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              pilgrim.fullName,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 17,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          Chip(
                            label: Text(
                              isSos ? 'SOS' : 'LOKASI TERAKHIR',
                              style: TextStyle(
                                color: isSos ? Colors.red : Colors.blue,
                                fontWeight: FontWeight.w700,
                                fontSize: 11,
                              ),
                            ),
                          ),
                        ],
                      ),
                      Text(pilgrim.registrationNumber),
                      const SizedBox(height: 5),
                      Text(
                        location.recordedAt == null
                            ? 'Waktu lokasi tidak tersedia'
                            : 'Diperbarui ${DateFormat('dd MMM yyyy, HH:mm').format(location.recordedAt!.toLocal())}',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                      const SizedBox(height: 13),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: () => _navigate(context),
                          icon: const Icon(Icons.directions_rounded),
                          label: const Text('Mulai Navigasi'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _navigate(BuildContext context) async {
    final location = pilgrim.location!;
    final opened = await openNavigation(location.latitude, location.longitude);
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Aplikasi navigasi tidak dapat dibuka.')),
      );
    }
  }
}
