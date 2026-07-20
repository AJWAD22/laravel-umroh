import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import 'staff_provider.dart';
import 'staff_sos_map_screen.dart';

class StaffSosScreen extends StatelessWidget {
  const StaffSosScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<StaffProvider>();
    final role = context.read<AuthProvider>().profile!.role;
    final active =
        provider.sosReports.where((report) => report.isActive).toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'SOS Jamaah',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            tooltip: 'Perbarui',
            onPressed:
                () => context.read<StaffProvider>().load(role, force: true),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => context.read<StaffProvider>().load(role, force: true),
        child:
            active.isEmpty
                ? ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  children: const [
                    SizedBox(height: 220),
                    Icon(Icons.shield_rounded, size: 58, color: Colors.green),
                    SizedBox(height: 12),
                    Center(
                      child: Text(
                        'Belum ada SOS aktif',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    SizedBox(height: 6),
                    Center(
                      child: Text(
                        'Jika jamaah menekan SOS, datanya muncul di sini.',
                      ),
                    ),
                  ],
                )
                : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: active.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final report = active[index];
                    final time =
                        report.reportedAt == null
                            ? '-'
                            : DateFormat(
                              'dd MMM, HH:mm',
                            ).format(report.reportedAt!.toLocal());
                    return Card(
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 8,
                        ),
                        leading: CircleAvatar(
                          backgroundColor: Colors.red.withValues(alpha: 0.12),
                          child: const Icon(
                            Icons.sos_rounded,
                            color: Colors.red,
                          ),
                        ),
                        title: Text(
                          report.pilgrim.fullName,
                          style: const TextStyle(fontWeight: FontWeight.w900),
                        ),
                        subtitle: Text(
                          '${report.pilgrim.registrationNumber} • $time\n${report.groupName ?? 'Rombongan'}',
                        ),
                        isThreeLine: true,
                        trailing: const Icon(Icons.map_rounded),
                        onTap:
                            () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder:
                                    (_) => StaffSosMapScreen(report: report),
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
