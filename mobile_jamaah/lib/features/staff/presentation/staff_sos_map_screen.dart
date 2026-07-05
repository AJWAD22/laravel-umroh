import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../domain/staff_sos.dart';

class StaffSosMapScreen extends StatelessWidget {
  const StaffSosMapScreen({super.key, required this.report});

  final StaffSos report;

  @override
  Widget build(BuildContext context) {
    final point = LatLng(report.latitude, report.longitude);
    final time =
        report.reportedAt == null
            ? 'Waktu tidak tersedia'
            : DateFormat(
              'dd MMM yyyy, HH:mm',
            ).format(report.reportedAt!.toLocal());

    return Scaffold(
      appBar: AppBar(title: const Text('Lokasi SOS')),
      body: Stack(
        children: [
          FlutterMap(
            options: MapOptions(initialCenter: point, initialZoom: 16),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'id.umrahmonitor.umrah_jamaah',
              ),
              MarkerLayer(
                markers: [
                  Marker(
                    point: point,
                    width: 72,
                    height: 72,
                    child: const Icon(
                      Icons.sos_rounded,
                      size: 54,
                      color: Colors.red,
                      shadows: [Shadow(color: Colors.white, blurRadius: 10)],
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
            bottom: 20,
            child: SafeArea(
              child: Card(
                elevation: 8,
                child: Padding(
                  padding: const EdgeInsets.all(18),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Row(
                        children: [
                          const CircleAvatar(
                            backgroundColor: Colors.red,
                            foregroundColor: Colors.white,
                            child: Icon(Icons.person_pin_circle_rounded),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  report.pilgrim.fullName,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 16,
                                  ),
                                ),
                                Text(time),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(report.message ?? 'Permintaan bantuan darurat'),
                      const SizedBox(height: 5),
                      SelectableText(
                        '${report.latitude}, ${report.longitude}',
                        style: Theme.of(context).textTheme.bodySmall,
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
}
