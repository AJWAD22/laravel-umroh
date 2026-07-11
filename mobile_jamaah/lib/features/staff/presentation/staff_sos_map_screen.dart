import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';

import '../../../core/widgets/internal_direction_map_screen.dart';
import '../domain/staff_sos_report.dart';
import 'staff_provider.dart';

class StaffSosMapScreen extends StatelessWidget {
  const StaffSosMapScreen({super.key, required this.report});

  final StaffSosReport report;

  @override
  Widget build(BuildContext context) {
    return InternalDirectionMapScreen(
      title: 'SOS ${report.pilgrim.fullName}',
      target: LatLng(report.latitude, report.longitude),
      targetName: report.pilgrim.fullName,
      targetSubtitle: report.pilgrim.registrationNumber,
      targetIcon: Icons.sos_rounded,
      targetColor: Colors.red,
      bottom: _SosBottomCard(report: report),
    );
  }
}

class _SosBottomCard extends StatelessWidget {
  const _SosBottomCard({required this.report});

  final StaffSosReport report;

  @override
  Widget build(BuildContext context) {
    final time =
        report.reportedAt == null
            ? '-'
            : DateFormat('dd MMM yyyy, HH:mm').format(report.reportedAt!.toLocal());

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              children: [
                const Icon(Icons.warning_rounded, color: Colors.red),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    report.pilgrim.fullName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w900),
                  ),
                ),
                Chip(label: Text(report.status.toUpperCase())),
              ],
            ),
            const SizedBox(height: 4),
            Text('${report.pilgrim.registrationNumber} • $time'),
            const SizedBox(height: 10),
            const Text(
              'Ikuti titik hijau Anda mendekati titik merah jamaah.',
              style: TextStyle(fontWeight: FontWeight.w700),
            ),
            if (report.status != 'resolved') ...[
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => context.read<StaffProvider>().acknowledgeSos(report.id),
                      icon: const Icon(Icons.handshake_rounded),
                      label: const Text('Tangani'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: () => context.read<StaffProvider>().resolveSos(report.id),
                      icon: const Icon(Icons.check_circle_rounded),
                      label: const Text('Aman'),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
