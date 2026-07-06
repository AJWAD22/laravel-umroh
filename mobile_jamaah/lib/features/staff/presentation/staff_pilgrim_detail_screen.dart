import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../../../core/utils/external_navigation.dart';
import '../domain/staff_pilgrim.dart';
import 'staff_pilgrim_map_screen.dart';

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
                      const SizedBox(height: 18),
                      if (pilgrim.phone != null &&
                          pilgrim.phone!.trim().isNotEmpty)
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton.icon(
                            onPressed: () => _copyPhone(context),
                            icon: const Icon(Icons.copy_rounded),
                            label: const Text('Salin Nomor WhatsApp'),
                          ),
                        ),
                      if (location != null) ...[
                        const SizedBox(height: 10),
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton.icon(
                            onPressed:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder:
                                        (_) => StaffPilgrimMapScreen(
                                          pilgrim: pilgrim,
                                        ),
                                  ),
                                ),
                            icon: const Icon(Icons.map_rounded),
                            label: const Text('Lihat di Peta'),
                          ),
                        ),
                        const SizedBox(height: 10),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: () => _navigate(context),
                            icon: const Icon(Icons.directions_rounded),
                            label: const Text('Navigasi ke Jamaah'),
                          ),
                        ),
                      ],
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

  Future<void> _copyPhone(BuildContext context) async {
    await Clipboard.setData(ClipboardData(text: pilgrim.phone!));
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nomor WhatsApp berhasil disalin.')),
      );
    }
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
