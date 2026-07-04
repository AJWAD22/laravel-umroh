import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'app.dart';
import 'core/network/api_client.dart';
import 'core/notifications/firebase_notification_service.dart';
import 'core/storage/secure_storage_service.dart';
import 'features/auth/data/auth_repository.dart';
import 'features/auth/presentation/auth_provider.dart';
import 'features/hotel/data/hotel_repository.dart';
import 'features/hotel/presentation/hotel_provider.dart';
import 'features/location/data/location_repository.dart';
import 'features/location/presentation/tracking_provider.dart';
import 'features/sos/data/sos_repository.dart';
import 'features/staff/data/staff_repository.dart';
import 'features/staff/presentation/staff_provider.dart';
import 'features/activation/data/activation_repository.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

  final notificationService = FirebaseNotificationService();
  await notificationService.initialize();

  final storage = SecureStorageService();
  final apiClient = ApiClient(storage);

  runApp(
    MultiProvider(
      providers: [
        Provider.value(value: apiClient),
        Provider.value(value: notificationService),
        Provider.value(value: AuthRepository(apiClient, storage)),
        Provider.value(value: ActivationRepository(apiClient, storage)),
        Provider.value(value: LocationRepository(apiClient)),
        Provider.value(value: SosRepository(apiClient)),
        Provider.value(value: HotelRepository(apiClient)),
        Provider.value(value: StaffRepository(apiClient)),
        ChangeNotifierProvider(
          create:
              (context) =>
                  AuthProvider(context.read<AuthRepository>())..initialize(),
        ),
        ChangeNotifierProvider(
          create:
              (context) => TrackingProvider(context.read<LocationRepository>()),
        ),
        ChangeNotifierProvider(
          create: (context) => HotelProvider(context.read<HotelRepository>()),
        ),
        ChangeNotifierProvider(
          create: (context) => StaffProvider(context.read<StaffRepository>()),
        ),
      ],
      child: const UmrahJamaahApp(),
    ),
  );
}
