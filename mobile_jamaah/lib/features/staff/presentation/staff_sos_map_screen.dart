import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';

import '../../../core/widgets/internal_direction_map_screen.dart';
import '../../auth/presentation/auth_provider.dart';
import '../domain/staff_sos.dart';
import 'staff_provider.dart';

class StaffSosMapScreen extends StatefulWidget {
  const StaffSosMapScreen({super.key, required this.report});

  final StaffSos report;

  @override
  State<StaffSosMapScreen> createState() => _StaffSosMapScreenState();
}

class _StaffSosMapScreenState extends State<StaffSosMapScreen> {
  bool _resolving = false;

  @override
  Widget build(BuildContext context) {
    final report = widget.report;
    final point = LatLng(report.latitude, report.longitude);
    final time =
        report.reportedAt == null
            ? 'Waktu tidak tersedia'
            : DateFormat(
              'dd MMM yyyy, HH:mm',
            ).format(report.reportedAt!.toLocal());

    return InternalDirectionMapScreen(
      title: 'Lokasi SOS',
      target: point,
      targetName: report.pilgrim.fullName,
      targetSubtitle: time,
      targetIcon: Icons.sos_rounded,
      targetColor: Colors.red,
      bottom: Card(
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
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          if (report.pilgrim.phone != null &&
                              report.pilgrim.phone!.trim().isNotEmpty)
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: _copyPhone,
                                icon: const Icon(Icons.copy_rounded),
                                label: const Text('Salin WA'),
                              ),
                            ),
                          if (report.pilgrim.phone != null &&
                              report.pilgrim.phone!.trim().isNotEmpty)
                            const SizedBox(width: 8),
                          const Expanded(
                            child: Text(
                              'Ikuti titik hijau Anda mendekati titik SOS di peta.',
                              style: TextStyle(fontWeight: FontWeight.w700),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 9),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          style: FilledButton.styleFrom(
                            backgroundColor: Colors.green,
                            foregroundColor: Colors.white,
                          ),
                          onPressed: _resolving ? null : _resolve,
                          icon:
                              _resolving
                                  ? const SizedBox.square(
                                    dimension: 18,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                  : const Icon(Icons.health_and_safety_rounded),
                          label: const Text('Jamaah Sudah Diamankan'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
    );
  }

  Future<void> _copyPhone() async {
    await Clipboard.setData(ClipboardData(text: widget.report.pilgrim.phone!));
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nomor WhatsApp berhasil disalin.')),
      );
    }
  }

  Future<void> _resolve() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder:
          (context) => AlertDialog(
            icon: const Icon(
              Icons.health_and_safety_rounded,
              color: Colors.green,
            ),
            title: const Text('Jamaah sudah diamankan?'),
            content: const Text(
              'Laporan SOS akan diselesaikan dan status jamaah kembali normal.',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Batal'),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Ya, Selesaikan'),
              ),
            ],
          ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _resolving = true);
    try {
      final role = context.read<AuthProvider>().profile!.role;
      await context.read<StaffProvider>().resolveSos(role, widget.report.id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('SOS selesai. Jamaah telah ditandai aman.'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(error.toString()),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _resolving = false);
    }
  }
}
