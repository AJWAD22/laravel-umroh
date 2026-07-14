import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'core/notifications/firebase_notification_service.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/presentation/auth_provider.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/dashboard/presentation/dashboard_screen.dart';
import 'features/staff/presentation/staff_dashboard_screen.dart';
import 'features/staff/presentation/staff_provider.dart';
import 'features/staff/presentation/staff_sos_map_screen.dart';
import 'features/staff/presentation/staff_sos_screen.dart';

// Widget root aplikasi. Navigator key dipakai agar notifikasi FCM dapat
// membuka halaman SOS walaupun navigasi dipicu dari service.
class UmrahJamaahApp extends StatefulWidget {
  const UmrahJamaahApp({super.key});

  @override
  State<UmrahJamaahApp> createState() => _UmrahJamaahAppState();
}

class _UmrahJamaahAppState extends State<UmrahJamaahApp> {
  final _navigatorKey = GlobalKey<NavigatorState>();
  StreamSubscription<Map<String, dynamic>>? _notificationSubscription;

  @override
  void initState() {
    super.initState();
    final notifications = context.read<FirebaseNotificationService>();
    _notificationSubscription = notifications.notificationIntents.listen(
      _openNotification,
    );
  }

  @override
  void dispose() {
    _notificationSubscription?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: _navigatorKey,
      title: 'Mantau Umroh',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      darkTheme: AppTheme.dark,
      themeMode: ThemeMode.system,
      home: Consumer<AuthProvider>(
        builder: (context, auth, _) {
          if (auth.isInitializing) {
            return const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }
          if (!auth.isAuthenticated) return const LoginScreen();
          WidgetsBinding.instance.addPostFrameCallback((_) {
            final intent =
                context.read<FirebaseNotificationService>().takePendingIntent();
            if (intent != null) _openNotification(intent);
          });
          return auth.profile!.role == 'jamaah'
              ? const DashboardScreen()
              : const StaffDashboardScreen();
        },
      ),
    );
  }

  Future<void> _openNotification(Map<String, dynamic> data) async {
    if (data['type']?.toString() != 'sos') return;

    final context = _navigatorKey.currentContext;
    if (context == null || !context.mounted) return;

    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated || auth.profile?.role == 'jamaah') return;

    final sosId = int.tryParse(data['sos_report_id']?.toString() ?? '');
    final provider = context.read<StaffProvider>();
    await provider.load(auth.profile!.role, force: true);

    if (!context.mounted) return;
    final report = sosId == null ? null : provider.findSosById(sosId);
    _navigatorKey.currentState?.push(
      MaterialPageRoute(
        builder:
            (_) =>
                report == null
                    ? const StaffSosScreen()
                    : StaffSosMapScreen(report: report),
      ),
    );
  }
}
