import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import 'staff_pilgrim_detail_screen.dart';
import 'staff_provider.dart';

class StaffSosScreen extends StatelessWidget {
  const StaffSosScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final reports = context.watch<StaffProvider>().sosReports;
    final role = context.read<AuthProvider>().profile!.role;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Laporan SOS'),
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
            reports.isEmpty
                ? ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  children: const [
                    SizedBox(height: 180),
                    Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.health_and_safety_outlined,
                          size: 56,
                          color: Colors.green,
                        ),
                        SizedBox(height: 12),
                        Text('Tidak ada laporan SOS.'),
                      ],
                    ),
                  ],
                )
                : ListView.separated(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(16),
                  itemCount: reports.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (context, index) {
                    final report = reports[index];
                    final time =
                        report.reportedAt == null
                            ? '-'
                            : DateFormat(
                              'dd MMM yyyy, HH:mm',
                            ).format(report.reportedAt!.toLocal());
                    return Card(
                      child: ListTile(
                        leading: const CircleAvatar(
                          backgroundColor: Colors.red,
                          foregroundColor: Colors.white,
                          child: Icon(Icons.sos_rounded),
                        ),
                        title: Text(report.pilgrim.fullName),
                        subtitle: Text(
                          '${report.message ?? 'Permintaan bantuan'}\n$time\n'
                          '${report.latitude}, ${report.longitude}',
                        ),
                        isThreeLine: true,
                        trailing: Chip(
                          label: Text(report.status.toUpperCase()),
                        ),
                        onTap:
                            () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder:
                                    (_) => StaffPilgrimDetailScreen(
                                      pilgrim: report.pilgrim,
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
