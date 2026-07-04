import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import '../../activation/presentation/leader_activation_screen.dart';
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
            title: const Text('Keluar aplikasi?'),
            content: const Text('Sesi pada perangkat ini akan diakhiri.'),
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
    context.read<StaffProvider>().clear();
    await context.read<AuthProvider>().logout();
  }

  @override
  Widget build(BuildContext context) {
    final profile = context.watch<AuthProvider>().profile!;
    final staff = context.watch<StaffProvider>();
    final isLeader = profile.role == 'tour-leader';
    final title = isLeader ? 'Tour Leader' : 'Muthawwif';

    return Scaffold(
      appBar: AppBar(
        title: Text('Dashboard $title'),
        actions: [
          IconButton(
            tooltip: 'Logout',
            onPressed: _logout,
            icon: const Icon(Icons.logout_rounded),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => staff.load(profile.role, force: true),
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
                                    ? const Icon(
                                      Icons.badge_rounded,
                                      color: Colors.white,
                                      size: 30,
                                    )
                                    : null,
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  title,
                                  style: const TextStyle(color: Colors.white70),
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
                                  profile.branchName,
                                  style: const TextStyle(color: Colors.white70),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    if (staff.isLoading)
                      const Center(
                        child: Padding(
                          padding: EdgeInsets.all(32),
                          child: CircularProgressIndicator(),
                        ),
                      )
                    else if (staff.error != null)
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            children: [
                              const Icon(
                                Icons.cloud_off_rounded,
                                color: Colors.red,
                                size: 36,
                              ),
                              const SizedBox(height: 8),
                              Text(staff.error!, textAlign: TextAlign.center),
                              TextButton(
                                onPressed:
                                    () => staff.load(profile.role, force: true),
                                child: const Text('Coba lagi'),
                              ),
                            ],
                          ),
                        ),
                      )
                    else
                      GridView.count(
                        crossAxisCount:
                            MediaQuery.sizeOf(context).width >= 600 ? 3 : 2,
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        mainAxisSpacing: 12,
                        crossAxisSpacing: 12,
                        childAspectRatio: 1.15,
                        children: [
                          _DashboardMenu(
                            icon: Icons.groups_rounded,
                            label:
                                isLeader ? 'Jamaah Group' : 'Jamaah Bimbingan',
                            count: staff.pilgrims.length,
                            onTap:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => const StaffPilgrimsScreen(),
                                  ),
                                ),
                          ),
                          _DashboardMenu(
                            icon: Icons.map_rounded,
                            label: 'Lokasi Jamaah',
                            count: staff.locations.length,
                            onTap:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder:
                                        (_) => const StaffLocationsScreen(),
                                  ),
                                ),
                          ),
                          _DashboardMenu(
                            icon: Icons.sos_rounded,
                            label: 'Laporan SOS',
                            count: staff.sosReports.length,
                            color: Colors.red,
                            onTap:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => const StaffSosScreen(),
                                  ),
                                ),
                          ),
                          if (isLeader)
                            _DashboardMenu(
                              icon: Icons.pin_rounded,
                              label: 'Aktivasi Jamaah',
                              onTap:
                                  () => Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder:
                                          (_) => const LeaderActivationScreen(),
                                    ),
                                  ),
                            ),
                          _DashboardMenu(
                            icon: Icons.person_rounded,
                            label: 'Profil',
                            onTap:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => const StaffProfileScreen(),
                                  ),
                                ),
                          ),
                          _DashboardMenu(
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

class _DashboardMenu extends StatelessWidget {
  const _DashboardMenu({
    required this.icon,
    required this.label,
    required this.onTap,
    this.count,
    this.color,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final int? count;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 34,
              color: color ?? Theme.of(context).colorScheme.primary,
            ),
            const SizedBox(height: 8),
            Text(
              label,
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            if (count != null)
              Text('$count data', style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}
