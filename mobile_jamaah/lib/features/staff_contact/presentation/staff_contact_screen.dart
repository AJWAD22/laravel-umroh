import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';

import '../../../core/widgets/internal_direction_map_screen.dart';
import '../data/staff_contact_repository.dart';
import '../domain/staff_contact.dart';

class StaffContactScreen extends StatefulWidget {
  const StaffContactScreen({super.key});

  @override
  State<StaffContactScreen> createState() => _StaffContactScreenState();
}

class _StaffContactScreenState extends State<StaffContactScreen> {
  late Future<List<StaffContact>> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<StaffContactRepository>().getStaffContacts();
  }

  Future<void> _refresh() async {
    setState(() {
      _future = context.read<StaffContactRepository>().getStaffContacts();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Petugas Rombongan',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: FutureBuilder<List<StaffContact>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(24),
                children: [
                  const Icon(
                    Icons.cloud_off_rounded,
                    color: Colors.red,
                    size: 48,
                  ),
                  const SizedBox(height: 12),
                  Text(snapshot.error.toString(), textAlign: TextAlign.center),
                  const SizedBox(height: 12),
                  FilledButton.icon(
                    onPressed: _refresh,
                    icon: const Icon(Icons.refresh_rounded),
                    label: const Text('Coba Lagi'),
                  ),
                ],
              );
            }

            final staff = snapshot.data ?? [];
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
              children: [
                Center(
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 720),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEFF6FF),
                            borderRadius: BorderRadius.circular(24),
                            border: Border.all(color: const Color(0xFFBFDBFE)),
                          ),
                          child: const Row(
                            children: [
                              Icon(
                                Icons.support_agent_rounded,
                                color: Color(0xFF2563EB),
                                size: 32,
                              ),
                              SizedBox(width: 14),
                              Expanded(
                                child: Text(
                                  'Lihat petugas rombongan Anda. Jika lokasi tersedia, buka peta internal untuk mendekat ke titik petugas.',
                                  style: TextStyle(
                                    color: Color(0xFF1E3A8A),
                                    fontWeight: FontWeight.w700,
                                    height: 1.35,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 14),
                        if (staff.isEmpty)
                          const _EmptyStaff()
                        else
                          ...staff.map((item) => _StaffContactCard(item: item)),
                      ],
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _StaffContactCard extends StatelessWidget {
  const _StaffContactCard({required this.item});

  final StaffContact item;

  @override
  Widget build(BuildContext context) {
    final location = item.location;
    final updatedAt =
        location?.recordedAt == null
            ? null
            : DateFormat(
              'dd MMM yyyy, HH:mm',
            ).format(location!.recordedAt!.toLocal());

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 26,
                  backgroundColor: const Color(0xFFDBEAFE),
                  child: Icon(
                    item.role == 'tour-leader'
                        ? Icons.groups_2_rounded
                        : Icons.menu_book_rounded,
                    color: const Color(0xFF2563EB),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.label,
                        style: const TextStyle(
                          color: Colors.blueGrey,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        item.hasProfile ? item.fullName! : 'Belum ditentukan',
                        style: Theme.of(context).textTheme.titleMedium
                            ?.copyWith(fontWeight: FontWeight.w900),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            _InfoLine(
              icon: Icons.phone_outlined,
              label:
                  item.phone?.isNotEmpty == true
                      ? item.phone!
                      : 'Nomor WhatsApp belum tersedia',
            ),
            _InfoLine(
              icon:
                  item.hasLocation
                      ? Icons.location_on_rounded
                      : Icons.location_off_rounded,
              label:
                  item.hasLocation
                      ? 'Lokasi tersedia${updatedAt == null ? '' : ' • $updatedAt'}'
                      : 'Lokasi petugas belum tersedia',
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed:
                        item.phone?.isNotEmpty == true
                            ? () => _copyPhone(context, item.phone!)
                            : null,
                    icon: const Icon(Icons.copy_rounded),
                    label: const Text('Salin WA'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: FilledButton.icon(
                    onPressed:
                        location == null ? null : () => _openMap(context, item),
                    icon: const Icon(Icons.map_rounded),
                    label: const Text('Lihat Posisi'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _copyPhone(BuildContext context, String phone) async {
    await Clipboard.setData(ClipboardData(text: phone));
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nomor WhatsApp petugas disalin.')),
      );
    }
  }

  void _openMap(BuildContext context, StaffContact item) {
    final location = item.location!;
    Navigator.push(
      context,
      MaterialPageRoute(
        builder:
            (_) => InternalDirectionMapScreen(
              title: item.label,
              target: LatLng(location.latitude, location.longitude),
              targetName: item.fullName ?? item.label,
              targetSubtitle: item.phone,
              targetIcon: Icons.support_agent_rounded,
              targetColor: const Color(0xFF2563EB),
            ),
      ),
    );
  }
}

class _InfoLine extends StatelessWidget {
  const _InfoLine({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 19, color: Colors.blueGrey),
          const SizedBox(width: 10),
          Expanded(child: Text(label)),
        ],
      ),
    );
  }
}

class _EmptyStaff extends StatelessWidget {
  const _EmptyStaff();

  @override
  Widget build(BuildContext context) {
    return const Card(
      child: Padding(
        padding: EdgeInsets.all(24),
        child: Column(
          children: [
            Icon(Icons.groups_outlined, color: Colors.blueGrey, size: 46),
            SizedBox(height: 10),
            Text(
              'Petugas rombongan belum ditentukan.',
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
