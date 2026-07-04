import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import 'staff_pilgrim_detail_screen.dart';
import 'staff_provider.dart';

class StaffPilgrimsScreen extends StatelessWidget {
  const StaffPilgrimsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final pilgrims = context.watch<StaffProvider>().pilgrims;
    final role = context.read<AuthProvider>().profile!.role;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Daftar Jamaah'),
        actions: [
          IconButton(
            tooltip: 'Refresh data',
            onPressed:
                () => context.read<StaffProvider>().load(role, force: true),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => context.read<StaffProvider>().load(role, force: true),
        child:
            pilgrims.isEmpty
                ? const _EmptyList(
                  icon: Icons.groups_outlined,
                  message: 'Belum ada jamaah yang ditugaskan.',
                )
                : ListView.separated(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(16),
                  itemCount: pilgrims.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (context, index) {
                    final pilgrim = pilgrims[index];
                    return Card(
                      child: ListTile(
                        leading: CircleAvatar(
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
                                  )
                                  : null,
                        ),
                        title: Text(pilgrim.fullName),
                        subtitle: Text(
                          '${pilgrim.registrationNumber} • ${pilgrim.monitoringStatus.toUpperCase()}',
                        ),
                        trailing: const Icon(Icons.chevron_right_rounded),
                        onTap:
                            () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder:
                                    (_) => StaffPilgrimDetailScreen(
                                      pilgrim: pilgrim,
                                    ),
                              ),
                            ),
                      ),
                    );
                  },
                ),
      ),
    );
  }
}

class _EmptyList extends StatelessWidget {
  const _EmptyList({required this.icon, required this.message});
  final IconData icon;
  final String message;

  @override
  Widget build(BuildContext context) => ListView(
    physics: const AlwaysScrollableScrollPhysics(),
    children: [
      Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 56, color: Colors.blueGrey),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
          ],
        ),
      ),
    ],
  );
}
