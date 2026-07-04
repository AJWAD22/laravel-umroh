import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final profile = context.watch<AuthProvider>().profile!;
    return Scaffold(
      appBar: AppBar(title: const Text('Profil Jamaah')),
      body: RefreshIndicator(
        onRefresh: context.read<AuthProvider>().refreshProfile,
        child: ListView(
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
                          radius: 44,
                          backgroundImage:
                              profile.photoUrl == null
                                  ? null
                                  : NetworkImage(profile.photoUrl!),
                          child:
                              profile.photoUrl == null
                                  ? Text(
                                    profile.name.substring(0, 1).toUpperCase(),
                                    style: const TextStyle(fontSize: 28),
                                  )
                                  : null,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          profile.name,
                          style: Theme.of(context).textTheme.titleLarge
                              ?.copyWith(fontWeight: FontWeight.bold),
                        ),
                        Text(profile.registrationNumber),
                        const Divider(height: 36),
                        _ProfileRow(
                          icon: Icons.email_outlined,
                          label: 'Email',
                          value: profile.email,
                        ),
                        _ProfileRow(
                          icon: Icons.phone_outlined,
                          label: 'Telepon',
                          value: profile.phone ?? '-',
                        ),
                        _ProfileRow(
                          icon: Icons.apartment_rounded,
                          label: 'Cabang',
                          value: profile.branchName,
                        ),
                        _ProfileRow(
                          icon: Icons.monitor_heart_outlined,
                          label: 'Status Monitoring',
                          value: profile.monitoringStatus.toUpperCase(),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ProfileRow extends StatelessWidget {
  const _ProfileRow({
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
