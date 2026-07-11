import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../domain/staff_pilgrim.dart';
import 'staff_pilgrim_map_screen.dart';

class StaffPilgrimDetailScreen extends StatelessWidget {
  const StaffPilgrimDetailScreen({super.key, required this.pilgrim});

  final StaffPilgrim pilgrim;

  @override
  Widget build(BuildContext context) {
    final location = pilgrim.location;

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Detail Jamaah',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 680),
              child: Column(
                children: [
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: const [Color(0xFF0F2F6B), Color(0xFF2563EB)],
                      ),
                      borderRadius: BorderRadius.circular(28),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.blue.withValues(alpha: 0.18),
                          blurRadius: 28,
                          offset: const Offset(0, 14),
                        ),
                      ],
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(22),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 38,
                            backgroundColor: Colors.white.withValues(
                              alpha: 0.16,
                            ),
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
                                      style: const TextStyle(fontSize: 26),
                                    )
                                    : null,
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  pilgrim.fullName,
                                  style: Theme.of(
                                    context,
                                  ).textTheme.titleLarge?.copyWith(
                                    color: Colors.white,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  pilgrim.registrationNumber,
                                  style: const TextStyle(color: Colors.white70),
                                ),
                                const SizedBox(height: 10),
                                Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: [
                                    _StatusChip(
                                      icon:
                                          pilgrim.monitoringStatus == 'sos'
                                              ? Icons.sos_rounded
                                              : Icons.verified_user_rounded,
                                      label:
                                          pilgrim.monitoringStatus == 'sos'
                                              ? 'SOS'
                                              : 'Normal',
                                      color:
                                          pilgrim.monitoringStatus == 'sos'
                                              ? Colors.red
                                              : Colors.green,
                                    ),
                                    _StatusChip(
                                      icon: Icons.location_on_rounded,
                                      label:
                                          location == null
                                              ? 'Belum ada lokasi'
                                              : 'Lokasi tersedia',
                                      color:
                                          location == null
                                              ? Colors.orange
                                              : Colors.blue,
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Informasi Kontak',
                            style: Theme.of(context).textTheme.titleMedium
                                ?.copyWith(fontWeight: FontWeight.w800),
                          ),
                          const SizedBox(height: 8),
                          _InfoRow(
                            icon: Icons.phone_outlined,
                            label: 'WhatsApp',
                            value: pilgrim.phone ?? '-',
                          ),
                          _InfoRow(
                            icon: Icons.apartment_rounded,
                            label: 'Cabang',
                            value: pilgrim.branchName ?? '-',
                          ),
                          if (pilgrim.phone != null &&
                              pilgrim.phone!.trim().isNotEmpty) ...[
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: OutlinedButton.icon(
                                onPressed: () => _copyPhone(context),
                                icon: const Icon(Icons.copy_rounded),
                                label: const Text('Salin Nomor WhatsApp'),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Status Lokasi',
                            style: Theme.of(context).textTheme.titleMedium
                                ?.copyWith(fontWeight: FontWeight.w800),
                          ),
                          const SizedBox(height: 8),
                          if (location == null)
                            const _EmptyLocation()
                          else ...[
                            _InfoRow(
                              icon: Icons.schedule_rounded,
                              label: 'Update terakhir',
                              value:
                                  location.recordedAt == null
                                      ? '-'
                                      : DateFormat(
                                        'dd MMM yyyy, HH:mm',
                                      ).format(location.recordedAt!.toLocal()),
                            ),
                            _InfoRow(
                              icon: Icons.gps_fixed_rounded,
                              label: 'Koordinat',
                              value:
                                  '${location.latitude.toStringAsFixed(6)}, ${location.longitude.toStringAsFixed(6)}',
                            ),
                            _InfoRow(
                              icon: Icons.radar_rounded,
                              label: 'Akurasi GPS',
                              value:
                                  location.accuracy == null
                                      ? '-'
                                      : '±${location.accuracy!.round()} meter',
                            ),
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: FilledButton.icon(
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
                                label: const Text('Buka Peta Internal'),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ],
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
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({
    required this.icon,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 15, color: color),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(color: color, fontWeight: FontWeight.w800),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon),
      title: Text(label),
      subtitle: Text(value),
    );
  }
}

class _EmptyLocation extends StatelessWidget {
  const _EmptyLocation();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.orange.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(16),
      ),
      child: const Text(
        'Lokasi jamaah belum tersedia. Minta jamaah membuka aplikasi dan mengaktifkan izin lokasi.',
      ),
    );
  }
}
