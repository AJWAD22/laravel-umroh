import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'core/theme/app_theme.dart';
import 'features/auth/presentation/auth_provider.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/dashboard/presentation/dashboard_screen.dart';
import 'features/staff/presentation/staff_dashboard_screen.dart';

class UmrahJamaahApp extends StatelessWidget {
  const UmrahJamaahApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Umrah Jamaah',
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
          return auth.profile!.role == 'jamaah'
              ? const DashboardScreen()
              : const StaffDashboardScreen();
        },
      ),
    );
  }
}
