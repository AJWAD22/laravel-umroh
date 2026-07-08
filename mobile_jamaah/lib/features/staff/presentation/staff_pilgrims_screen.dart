import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import 'staff_pilgrim_detail_screen.dart';
import 'staff_provider.dart';

class StaffPilgrimsScreen extends StatefulWidget {
  const StaffPilgrimsScreen({super.key});

  @override
  State<StaffPilgrimsScreen> createState() => _StaffPilgrimsScreenState();
}

class _StaffPilgrimsScreenState extends State<StaffPilgrimsScreen> {
  final _searchController = TextEditingController();
  String _query = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final pilgrims = context.watch<StaffProvider>().pilgrims;
    final role = context.read<AuthProvider>().profile!.role;
    final query = _query.trim().toLowerCase();
    final filtered =
        pilgrims.where((pilgrim) {
          if (query.isEmpty) return true;
          return pilgrim.fullName.toLowerCase().contains(query) ||
              pilgrim.registrationNumber.toLowerCase().contains(query) ||
              (pilgrim.phone?.toLowerCase().contains(query) ?? false);
        }).toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Cari Jamaah',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            tooltip: 'Perbarui data',
            onPressed:
                () => context.read<StaffProvider>().load(role, force: true),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(
                  color: Theme.of(context).dividerColor.withValues(alpha: 0.45),
                ),
              ),
              child: TextField(
                controller: _searchController,
                autofocus: false,
                textInputAction: TextInputAction.search,
                onChanged: (value) => setState(() => _query = value),
                decoration: InputDecoration(
                  hintText: 'Cari nama, nomor jamaah, atau WhatsApp',
                  prefixIcon: const Icon(Icons.search_rounded),
                  suffixIcon:
                      query.isEmpty
                          ? null
                          : IconButton(
                            tooltip: 'Hapus pencarian',
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _query = '');
                            },
                            icon: const Icon(Icons.close_rounded),
                          ),
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 6, 20, 8),
            child: Align(
              alignment: Alignment.centerLeft,
              child: Text(
                query.isEmpty
                    ? '${pilgrims.length} jamaah dalam penugasan Anda'
                    : '${filtered.length} hasil ditemukan',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh:
                  () => context.read<StaffProvider>().load(role, force: true),
              child:
                  pilgrims.isEmpty
                      ? const _EmptyList(
                        icon: Icons.groups_outlined,
                        message: 'Belum ada jamaah yang ditugaskan.',
                      )
                      : filtered.isEmpty
                      ? const _EmptyList(
                        icon: Icons.search_off_rounded,
                        message: 'Jamaah yang dicari tidak ditemukan.',
                      )
                      : ListView.separated(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                        itemCount: filtered.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final pilgrim = filtered[index];
                          return Card(
                            child: ListTile(
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 6,
                              ),
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
                              title: Text(
                                pilgrim.fullName,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              subtitle: Text(
                                '${pilgrim.registrationNumber} • '
                                '${pilgrim.monitoringStatus.toUpperCase()}',
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
          ),
        ],
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
