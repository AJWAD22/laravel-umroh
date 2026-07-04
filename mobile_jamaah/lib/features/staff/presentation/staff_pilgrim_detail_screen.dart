import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../domain/staff_pilgrim.dart';

class StaffPilgrimDetailScreen extends StatelessWidget {
  const StaffPilgrimDetailScreen({super.key, required this.pilgrim});

  final StaffPilgrim pilgrim;

  @override
  Widget build(BuildContext context) {
    final location = pilgrim.location;
    return Scaffold(
      appBar: AppBar(title: const Text('Detail Jamaah')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 620),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    children: [
                      CircleAvatar(
                        radius: 42,
                        backgroundImage:
                            pilgrim.photoUrl == null
                                ? null
                                : NetworkImage(pilgrim.photoUrl!),
                        child:
                            pilgrim.photoUrl == null
                                ? Text(
                                  pilgrim.fullName
                                      .substring(0, 1)
                                      .toUpperCase(),
                                  style: const TextStyle(fontSize: 28),
                                )
                                : null,
                      ),
                      const SizedBox(height: 12),
                      Text(
                        pilgrim.fullName,
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const Divider(height: 32),
                      _Row('Nomor registrasi', pilgrim.registrationNumber),
                      _Row('Telepon', pilgrim.phone ?? '-'),
                      _Row('Cabang', pilgrim.branchName ?? '-'),
                      _Row('Status', pilgrim.status.toUpperCase()),
                      _Row(
                        'Monitoring',
                        pilgrim.monitoringStatus.toUpperCase(),
                      ),
                      _Row(
                        'Lokasi terakhir',
                        location == null
                            ? 'Belum tersedia'
                            : '${location.latitude}, ${location.longitude}',
                      ),
                      _Row(
                        'Waktu lokasi',
                        location?.recordedAt == null
                            ? '-'
                            : DateFormat(
                              'dd MMM yyyy, HH:mm',
                            ).format(location!.recordedAt!.toLocal()),
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

class _Row extends StatelessWidget {
  const _Row(this.label, this.value);
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => ListTile(
    contentPadding: EdgeInsets.zero,
    title: Text(label),
    subtitle: Text(value),
  );
}
