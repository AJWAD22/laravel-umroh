import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import '../../hotel/presentation/hotel_screen.dart';
import '../../location/data/location_repository.dart';
import '../../location/presentation/tracking_provider.dart';
import '../../profile/domain/jamaah_profile.dart';
import '../../profile/presentation/profile_screen.dart';
import '../../sos/data/sos_repository.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen>
    with WidgetsBindingObserver {
  bool _sendingSos = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final tracking = context.read<TrackingProvider>();
    if (state == AppLifecycleState.resumed) {
      tracking.resumeForLifecycle();
    } else if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive ||
        state == AppLifecycleState.detached) {
      tracking.pauseForLifecycle();
    }
  }

  Future<void> _sendSos() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder:
          (context) => AlertDialog(
            icon: const Icon(Icons.sos_rounded, color: Colors.red, size: 42),
            title: const Text('Kirim SOS?'),
            content: const Text(
              'Lokasi terkini Anda akan dikirim ke Admin Cabang dan petugas group.',
              textAlign: TextAlign.center,
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Batal'),
              ),
              FilledButton(
                style: FilledButton.styleFrom(backgroundColor: Colors.red),
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Kirim SOS'),
              ),
            ],
          ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _sendingSos = true);
    final tracking = context.read<TrackingProvider>();
    final locationRepository = context.read<LocationRepository>();
    final sosRepository = context.read<SosRepository>();
    final auth = context.read<AuthProvider>();
    try {
      final position =
          tracking.lastPosition ?? await locationRepository.currentPosition();
      await sosRepository.send(position);
      await auth.refreshProfile();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('SOS berhasil dikirim. Bantuan sedang diproses.'),
            backgroundColor: Colors.green,
          ),
        );
      }
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
      if (mounted) setState(() => _sendingSos = false);
    }
  }

  Future<void> _logout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder:
          (context) => AlertDialog(
            title: const Text('Keluar aplikasi?'),
            content: const Text('Tracking aktif akan dihentikan.'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Batal'),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Keluar'),
              ),
            ],
          ),
    );
    if (confirmed != true || !mounted) return;
    context.read<TrackingProvider>().stop();
    await context.read<AuthProvider>().logout();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final tracking = context.watch<TrackingProvider>();
    final profile = auth.profile!;
    final isSos = profile.monitoringStatus == 'sos';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard Jamaah'),
        actions: [
          IconButton(
            tooltip: 'Logout',
            onPressed: _logout,
            icon: const Icon(Icons.logout_rounded),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: auth.refreshProfile,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
          children: [
            Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 760),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(22),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF1D4ED8), Color(0xFF2563EB)],
                        ),
                        borderRadius: BorderRadius.circular(24),
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 30,
                            backgroundColor: Colors.white24,
                            backgroundImage:
                                profile.photoUrl == null
                                    ? null
                                    : NetworkImage(profile.photoUrl!),
                            child:
                                profile.photoUrl == null
                                    ? Text(
                                      profile.name
                                          .split(' ')
                                          .take(2)
                                          .map((word) => word[0])
                                          .join()
                                          .toUpperCase(),
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontWeight: FontWeight.bold,
                                        fontSize: 20,
                                      ),
                                    )
                                    : null,
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Assalamu’alaikum,',
                                  style: TextStyle(color: Colors.white70),
                                ),
                                Text(
                                  profile.name,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                Text(
                                  profile.registrationNumber,
                                  style: const TextStyle(color: Colors.white70),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    _JourneyCard(journey: profile.journey),
                    const SizedBox(height: 12),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(18),
                        child: Row(
                          children: [
                            Icon(
                              isSos
                                  ? Icons.warning_rounded
                                  : Icons.verified_user_rounded,
                              color: isSos ? Colors.red : Colors.green,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text('Status Monitoring'),
                                  Text(
                                    isSos ? 'SOS Aktif' : 'Normal',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      color: isSos ? Colors.red : Colors.green,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            if (tracking.isTracking)
                              const Chip(
                                avatar: Icon(
                                  Icons.gps_fixed,
                                  size: 16,
                                  color: Colors.green,
                                ),
                                label: Text('Tracking'),
                              ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    FilledButton.icon(
                      onPressed:
                          tracking.isSending
                              ? null
                              : tracking.isTracking
                              ? tracking.stop
                              : tracking.start,
                      style: FilledButton.styleFrom(
                        minimumSize: const Size.fromHeight(56),
                        backgroundColor:
                            tracking.isTracking ? Colors.orange : null,
                      ),
                      icon: Icon(
                        tracking.isTracking
                            ? Icons.stop_circle_outlined
                            : Icons.play_circle_outline,
                      ),
                      label: Text(
                        tracking.isTracking
                            ? 'Hentikan Tracking'
                            : 'Mulai Tracking',
                      ),
                    ),
                    if (tracking.error != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text(
                          tracking.error!,
                          style: const TextStyle(color: Colors.red),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    if (tracking.lastSentAt != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text(
                          'Lokasi terakhir terkirim ${DateFormat.Hms().format(tracking.lastSentAt!)}',
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ),
                    const SizedBox(height: 12),
                    FilledButton.icon(
                      onPressed: _sendingSos ? null : _sendSos,
                      style: FilledButton.styleFrom(
                        minimumSize: const Size.fromHeight(64),
                        backgroundColor: Colors.red,
                        foregroundColor: Colors.white,
                      ),
                      icon:
                          _sendingSos
                              ? const SizedBox.square(
                                dimension: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                              : const Icon(Icons.sos_rounded, size: 30),
                      label: const Text(
                        'TOMBOL DARURAT SOS',
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(height: 20),
                    GridView.count(
                      crossAxisCount:
                          MediaQuery.sizeOf(context).width >= 600 ? 3 : 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      mainAxisSpacing: 12,
                      crossAxisSpacing: 12,
                      childAspectRatio: 1.25,
                      children: [
                        _MenuCard(
                          icon: Icons.hotel_rounded,
                          label: 'Hotel',
                          onTap:
                              () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => const HotelScreen(),
                                ),
                              ),
                        ),
                        _MenuCard(
                          icon: Icons.person_rounded,
                          label: 'Profil',
                          onTap:
                              () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => const ProfileScreen(),
                                ),
                              ),
                        ),
                        _MenuCard(
                          icon: Icons.logout_rounded,
                          label: 'Logout',
                          onTap: _logout,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _JourneyCard extends StatelessWidget {
  const _JourneyCard({required this.journey});

  final JourneyInfo? journey;

  @override
  Widget build(BuildContext context) {
    if (journey == null) {
      return const Card(
        child: ListTile(
          leading: Icon(Icons.flight_outlined),
          title: Text('Informasi Perjalanan'),
          subtitle: Text('Belum ada keberangkatan aktif.'),
        ),
      );
    }

    final dateFormat = DateFormat('dd MMM yyyy');
    final dateRange =
        journey!.departureDate == null
            ? '-'
            : journey!.returnDate == null
            ? dateFormat.format(journey!.departureDate!)
            : '${dateFormat.format(journey!.departureDate!)} – '
                '${dateFormat.format(journey!.returnDate!)}';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.flight_takeoff_rounded, color: Colors.blue),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    journey!.programName,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                Chip(label: Text(journey!.status.toUpperCase())),
              ],
            ),
            const Divider(height: 24),
            _JourneyRow(
              icon: Icons.groups_rounded,
              label: '${journey!.groupName} (${journey!.groupCode})',
            ),
            _JourneyRow(icon: Icons.calendar_month_rounded, label: dateRange),
            _JourneyRow(
              icon: Icons.route_rounded,
              label:
                  '${journey!.departureAirport ?? '-'} → '
                  '${journey!.arrivalAirport ?? '-'}',
            ),
            _JourneyRow(
              icon: Icons.badge_rounded,
              label: 'Tour Leader: ${journey!.tourLeaderName ?? '-'}',
            ),
            _JourneyRow(
              icon: Icons.mosque_rounded,
              label: 'Muthawwif: ${journey!.muthawwifName ?? '-'}',
            ),
          ],
        ),
      ),
    );
  }
}

class _JourneyRow extends StatelessWidget {
  const _JourneyRow({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) => Padding(
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

class _MenuCard extends StatelessWidget {
  const _MenuCard({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 34, color: Theme.of(context).colorScheme.primary),
            const SizedBox(height: 8),
            Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }
}
