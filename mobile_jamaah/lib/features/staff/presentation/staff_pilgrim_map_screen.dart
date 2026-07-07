import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';

import '../../../core/widgets/internal_direction_map_screen.dart';
import '../domain/staff_pilgrim.dart';

class StaffPilgrimMapScreen extends StatelessWidget {
  const StaffPilgrimMapScreen({super.key, required this.pilgrim});

  final StaffPilgrim pilgrim;

  @override
  Widget build(BuildContext context) {
    final location = pilgrim.location!;
    final isSos = pilgrim.monitoringStatus == 'sos';

    return InternalDirectionMapScreen(
      title: 'Lokasi ${pilgrim.fullName}',
      target: LatLng(location.latitude, location.longitude),
      targetName: pilgrim.fullName,
      targetSubtitle: pilgrim.registrationNumber,
      targetIcon: Icons.person_pin_circle_rounded,
      targetColor: isSos ? Colors.red : Colors.blue,
      bottom: Card(
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
              const SizedBox(height: 10),
              const Text(
                'Ikuti titik hijau Anda mendekati titik jamaah di peta.',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
