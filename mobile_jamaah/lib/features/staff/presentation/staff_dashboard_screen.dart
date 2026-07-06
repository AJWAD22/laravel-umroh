import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../activation/presentation/leader_activation_screen.dart';
import '../../auth/presentation/auth_provider.dart';
import '../../hotel/presentation/hotel_screen.dart';
import '../../profile/presentation/staff_profile_screen.dart';
import 'staff_locations_screen.dart';
import 'staff_pilgrims_screen.dart';
import 'staff_provider.dart';
import 'staff_sos_screen.dart';

class StaffDashboardScreen extends StatefulWidget {
  const StaffDashboardScreen({super.key});

  @override
  State<StaffDashboardScreen> createState() => _StaffDashboardScreenState();
}

class _StaffDashboardScreenState extends State<StaffDashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<StaffProvider>().load(
        context.read<AuthProvider>().profile!.role,
      );
    });
  }

  Future<void> _logout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder:
          (context) => AlertDialog(
            icon: const Icon(Icons.logout_rounded),
            title: const Text('Keluar dari aplikasi?'),
            content: const Text(
              'Sesi akun pada perangkat ini akan diakhiri. Anda perlu login kembali untuk mengakses sistem.',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Batal'),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Ya, keluar'),
              ),
            ],
          ),
    );
    if (confirmed != true || !mounted) return;
    context.read<StaffProvider>().clear();
    await context.read<AuthProvider>().logout();
  }

  @override
  Widget build(BuildContext context) {
    final profile = context.watch<AuthProvider>().profile!;
    final staff = context.watch<StaffProvider>();
    final isLeader = profile.role == 'tour-leader';
    final roleName = isLeader ? 'Tour Leader' : 'Muthawwif';

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Beranda',
          style: TextStyle(fontWeight: FontWeight.w700),
        ),
        actions: [
          IconButton(
            tooltip: 'Keluar dari aplikasi',
            onPressed: _logout,
            icon: const Icon(Icons.logout_rounded),
          ),
          const SizedBox(width: 6),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => staff.load(profile.role, force: true),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
          children: [
            Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 820),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _StaffHeader(
                      name: profile.name,
                      roleName: roleName,
                      branchName: profile.branchName,
                      photoUrl: profile.photoUrl,
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Menu Utama',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      'Pilih layanan yang ingin Anda buka.',
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                    const SizedBox(height: 14),
                    if (staff.isLoading)
                      const _LoadingPanel()
                    else if (staff.error != null)
                      _ErrorPanel(
                        message: staff.error!,
                        onRetry: () => staff.load(profile.role, force: true),
                      )
                    else
                      GridView(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate:
                            const SliverGridDelegateWithMaxCrossAxisExtent(
                              maxCrossAxisExtent: 300,
                              mainAxisExtent: 158,
                              mainAxisSpacing: 12,
                              crossAxisSpacing: 12,
                            ),
                        children: [
                          _DashboardMenu(
                            icon: Icons.groups_rounded,
                            label: 'Cari Jamaah',
                            description:
                                isLeader
                                    ? 'Cari dan buka profil jamaah'
                                    : 'Cari jamaah dalam bimbingan Anda',
                            count: staff.pilgrims.length,
                            onTap:
                                () =>
                                    _open(context, const StaffPilgrimsScreen()),
                          ),
                          _DashboardMenu(
                            icon: Icons.location_on_rounded,
                            label: 'Lokasi Jamaah',
                            description: 'Lihat posisi terbaru pada peta',
                            count: staff.locations.length,
                            onTap:
                                () => _open(
                                  context,
                                  const StaffLocationsScreen(),
                                ),
                          ),
                          _DashboardMenu(
                            icon: Icons.sos_rounded,
                            label: 'Laporan SOS',
                            description: 'Pantau permintaan bantuan jamaah',
                            count: staff.sosReports.length,
                            accentColor: Colors.red,
                            onTap: () => _open(context, const StaffSosScreen()),
                          ),
                          if (isLeader)
                            _DashboardMenu(
                              icon: Icons.phonelink_lock_rounded,
                              label: 'Aktivasi Jamaah',
                              description: 'Setujui perangkat milik jamaah',
                              onTap:
                                  () => _open(
                                    context,
                                    const LeaderActivationScreen(),
                                  ),
                            ),
                          _DashboardMenu(
                            icon: Icons.hotel_rounded,
                            label: 'Hotel Rombongan',
                            description: 'Lihat dan navigasi menuju hotel',
                            onTap:
                                () => _open(
                                  context,
                                  HotelScreen(staffRole: profile.role),
                                ),
                          ),
                        ],
                      ),
                    const SizedBox(height: 18),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 13,
                      ),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                          color: Theme.of(
                            context,
                          ).dividerColor.withValues(alpha: 0.5),
                        ),
                      ),
                      child: const Row(
                        children: [
                          Icon(
                            Icons.swipe_down_alt_rounded,
                            size: 20,
                            color: Colors.blueGrey,
                          ),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Tarik layar ke bawah untuk memperbarui data.',
                              style: TextStyle(fontSize: 13),
                            ),
                          ),
                        ],
                      ),
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

  void _open(BuildContext context, Widget screen) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => screen));
  }
}

class _StaffHeader extends StatelessWidget {
  const _StaffHeader({
    required this.name,
    required this.roleName,
    required this.branchName,
    required this.photoUrl,
  });

  final String name;
  final String roleName;
  final String branchName;
  final String? photoUrl;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(28),
      onTap:
          () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const StaffProfileScreen()),
          ),
      child: Container(
        padding: const EdgeInsets.all(22),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF0F2F6B), Color(0xFF2563EB)],
          ),
          borderRadius: BorderRadius.circular(28),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF1D4ED8).withValues(alpha: 0.2),
              blurRadius: 26,
              offset: const Offset(0, 12),
            ),
          ],
        ),
        child: Row(
          children: [
            CircleAvatar(
              radius: 32,
              backgroundColor: Colors.white.withValues(alpha: 0.16),
              backgroundImage:
                  photoUrl == null || photoUrl!.isEmpty
                      ? null
                      : NetworkImage(photoUrl!),
              child:
                  photoUrl == null || photoUrl!.isEmpty
                      ? Text(
                        _initials(name),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
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
                    'Selamat bertugas,',
                    style: TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 7),
                  Text(
                    '$roleName • $branchName',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _initials(String value) {
    return value
        .trim()
        .split(RegExp(r'\s+'))
        .take(2)
        .where((part) => part.isNotEmpty)
        .map((part) => part[0].toUpperCase())
        .join();
  }
}

class _DashboardMenu extends StatelessWidget {
  const _DashboardMenu({
    required this.icon,
    required this.label,
    required this.description,
    required this.onTap,
    this.count,
    this.accentColor,
  });

  final IconData icon;
  final String label;
  final String description;
  final VoidCallback onTap;
  final int? count;
  final Color? accentColor;

  @override
  Widget build(BuildContext context) {
    final color = accentColor ?? Theme.of(context).colorScheme.primary;
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(17),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 43,
                    height: 43,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.11),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Icon(icon, size: 23, color: color),
                  ),
                  const Spacer(),
                  if (count != null)
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 9,
                        vertical: 5,
                      ),
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(99),
                      ),
                      child: Text(
                        '$count',
                        style: TextStyle(
                          color: color,
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    )
                  else
                    const Icon(
                      Icons.arrow_forward_rounded,
                      size: 19,
                      color: Colors.blueGrey,
                    ),
                ],
              ),
              const Spacer(),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                description,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(
                  context,
                ).textTheme.bodySmall?.copyWith(height: 1.35),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LoadingPanel extends StatelessWidget {
  const _LoadingPanel();

  @override
  Widget build(BuildContext context) {
    return const Card(
      child: Padding(
        padding: EdgeInsets.all(32),
        child: Column(
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 14),
            Text('Memuat data operasional...'),
          ],
        ),
      ),
    );
  }
}

class _ErrorPanel extends StatelessWidget {
  const _ErrorPanel({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const Icon(Icons.cloud_off_rounded, color: Colors.red, size: 38),
            const SizedBox(height: 10),
            const Text(
              'Data belum dapat dimuat',
              style: TextStyle(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 5),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Coba Lagi'),
            ),
          ],
        ),
      ),
    );
  }
}
