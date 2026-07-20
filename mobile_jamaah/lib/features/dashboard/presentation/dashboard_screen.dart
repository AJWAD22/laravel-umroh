import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import '../../checkpoint/presentation/checkpoint_screen.dart';
import '../../location/presentation/location_permission_guide_screen.dart';
import '../../location/presentation/tracking_provider.dart';
import '../../profile/domain/jamaah_profile.dart';
import '../../profile/presentation/profile_screen.dart';
import '../../sos/data/sos_repository.dart';
import '../../location/data/location_repository.dart';
import '../../staff_contact/presentation/staff_contact_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  bool _sendingSos = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) context.read<TrackingProvider>().start();
    });
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
    await context.read<TrackingProvider>().stop();
    if (!mounted) return;
    await context.read<AuthProvider>().logout();
  }

  Future<void> _sendSos() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder:
          (context) => AlertDialog(
            icon: const Icon(Icons.sos_rounded, color: Colors.red),
            title: const Text('Kirim SOS?'),
            content: const Text(
              'Gunakan tombol ini hanya saat membutuhkan bantuan. Lokasi Anda akan dikirim ke Tour Leader dan Muthawwif.',
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

    final locationRepository = context.read<LocationRepository>();
    final sosRepository = context.read<SosRepository>();
    setState(() => _sendingSos = true);
    try {
      final position = await locationRepository.currentPosition();
      await sosRepository.send(position: position);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'SOS terkirim. Tetap tenang, petugas sedang diberi tahu.',
          ),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(error.toString())));
    } finally {
      if (mounted) setState(() => _sendingSos = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final tracking = context.watch<TrackingProvider>();
    final profile = auth.profile!;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Beranda Jamaah'),
        actions: [
          IconButton(
            tooltip: 'Profil Saya',
            onPressed:
                () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const ProfileScreen()),
                ),
            icon: const Icon(Icons.person_rounded),
          ),
          IconButton(
            tooltip: 'Logout',
            onPressed: _logout,
            icon: const Icon(Icons.logout_rounded),
          ),
          const SizedBox(width: 6),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: auth.refreshProfile,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
          children: [
            Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 760),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _WelcomeHero(profile: profile),
                    const SizedBox(height: 14),
                    _MonitoringStatusCard(
                      isTracking: tracking.isTracking,
                      isSending: tracking.isSending,
                      lastSentAt: tracking.lastSentAt,
                      error: tracking.error,
                    ),
                    const SizedBox(height: 14),
                    _SosButton(isSending: _sendingSos, onPressed: _sendSos),
                    const SizedBox(height: 18),
                    _JourneyCard(journey: profile.journey),
                    const SizedBox(height: 18),
                    Text(
                      'Menu Jamaah',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 8),
                    GridView(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate:
                          const SliverGridDelegateWithMaxCrossAxisExtent(
                            maxCrossAxisExtent: 260,
                            mainAxisExtent: 132,
                            crossAxisSpacing: 12,
                            mainAxisSpacing: 12,
                          ),
                      children: [
                        _MenuCard(
                          icon: Icons.place_rounded,
                          title: 'Tujuan',
                          subtitle: 'Cari titik penting',
                          color: const Color(0xFF2563EB),
                          onTap:
                              () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => const CheckpointScreen(),
                                ),
                              ),
                        ),
                        _MenuCard(
                          icon: Icons.badge_rounded,
                          title: 'Profil',
                          subtitle: 'Data jamaah',
                          color: const Color(0xFF16A34A),
                          onTap:
                              () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => const ProfileScreen(),
                                ),
                              ),
                        ),
                        _MenuCard(
                          icon: Icons.support_agent_rounded,
                          title: 'Petugas',
                          subtitle: 'TL & Muthawwif',
                          color: const Color(0xFF7C3AED),
                          onTap:
                              () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => const StaffContactScreen(),
                                ),
                              ),
                        ),
                        _MenuCard(
                          icon: Icons.location_searching_rounded,
                          title: 'Izin Lokasi',
                          subtitle: 'Panduan GPS',
                          color: const Color(0xFFF59E0B),
                          onTap:
                              () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder:
                                      (_) =>
                                          const LocationPermissionGuideScreen(),
                                ),
                              ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    const _HelpCard(),
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

class _SosButton extends StatelessWidget {
  const _SosButton({required this.isSending, required this.onPressed});

  final bool isSending;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: const Color(0xFFFFF1F2),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(
          children: [
            Container(
              width: 54,
              height: 54,
              decoration: BoxDecoration(
                color: const Color(0xFFDC2626).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(18),
              ),
              child: const Icon(Icons.sos_rounded, color: Color(0xFFDC2626)),
            ),
            const SizedBox(width: 14),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Butuh Bantuan?',
                    style: TextStyle(fontSize: 17, fontWeight: FontWeight.w900),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'Tekan SOS untuk mengirim lokasi Anda ke petugas.',
                    style: TextStyle(fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            FilledButton(
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFFDC2626),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(
                  horizontal: 18,
                  vertical: 14,
                ),
              ),
              onPressed: isSending ? null : onPressed,
              child:
                  isSending
                      ? const SizedBox.square(
                        dimension: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                      : const Text('SOS'),
            ),
          ],
        ),
      ),
    );
  }
}

class _WelcomeHero extends StatelessWidget {
  const _WelcomeHero({required this.profile});

  final JamaahProfile profile;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0B1F45), Color(0xFF1D4ED8)],
        ),
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1D4ED8).withValues(alpha: 0.22),
            blurRadius: 30,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 34,
            backgroundColor: Colors.white.withValues(alpha: 0.16),
            backgroundImage:
                profile.photoUrl == null
                    ? null
                    : NetworkImage(profile.photoUrl!),
            child:
                profile.photoUrl == null
                    ? Text(
                      _initials(profile.name),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.w900,
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
                  'Assalamu’alaikum',
                  style: TextStyle(
                    color: Color(0xFFBFDBFE),
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  profile.name,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    height: 1.2,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    profile.registrationNumber,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _MonitoringStatusCard extends StatelessWidget {
  const _MonitoringStatusCard({
    required this.isTracking,
    required this.isSending,
    required this.lastSentAt,
    required this.error,
  });

  final bool isTracking;
  final bool isSending;
  final DateTime? lastSentAt;
  final String? error;

  @override
  Widget build(BuildContext context) {
    final isHealthy = error == null && isTracking;
    final statusColor =
        isHealthy ? const Color(0xFF16A34A) : const Color(0xFFF59E0B);
    final statusText = isHealthy ? 'Aman & Terpantau' : 'Perlu Cek Lokasi';
    final subtitle =
        error != null
            ? error!
            : lastSentAt != null
            ? 'Lokasi terkirim ${DateFormat.Hm().format(lastSentAt!)}'
            : isSending
            ? 'Sedang mengirim lokasi...'
            : 'Menunggu update lokasi pertama.';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: statusColor.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(18),
              ),
              child: Icon(Icons.gps_fixed_rounded, color: statusColor),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    statusText,
                    style: TextStyle(
                      color: statusColor,
                      fontSize: 17,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
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
          leading: Icon(Icons.flight_takeoff_rounded),
          title: Text('Perjalanan Umroh'),
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
                const Icon(
                  Icons.flight_takeoff_rounded,
                  color: Color(0xFF2563EB),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    journey!.programName,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
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
    padding: const EdgeInsets.only(bottom: 10),
    child: Row(
      children: [
        Icon(icon, size: 20, color: Colors.blueGrey),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
        ),
      ],
    ),
  );
}

class _MenuCard extends StatelessWidget {
  const _MenuCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.color,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(icon, color: color, size: 27),
              ),
              const Spacer(),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: Theme.of(
                  context,
                ).textTheme.bodySmall?.copyWith(fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HelpCard extends StatelessWidget {
  const _HelpCard();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF16A34A).withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(22),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_rounded, color: Color(0xFF16A34A)),
          SizedBox(width: 12),
          Expanded(
            child: Text(
              'Jika tersesat, tetap tenang. Buka kontak petugas dan hubungi Tour Leader melalui nomor yang diberikan.',
              style: TextStyle(fontWeight: FontWeight.w700, height: 1.45),
            ),
          ),
        ],
      ),
    );
  }
}

String _initials(String name) {
  final parts = name.trim().split(RegExp(r'\s+')).where((e) => e.isNotEmpty);
  return parts.take(2).map((e) => e[0]).join().toUpperCase();
}
