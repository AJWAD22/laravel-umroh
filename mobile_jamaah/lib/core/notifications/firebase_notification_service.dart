import 'dart:async';
import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../network/api_client.dart';
import '../storage/secure_storage_service.dart';

const _notificationChannel = AndroidNotificationChannel(
  'umrah_alerts',
  'Peringatan Umrah',
  description: 'Notifikasi SOS dan peringatan monitoring jamaah.',
  importance: Importance.high,
);

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

class FirebaseNotificationService {
  FirebaseNotificationService({
    required ApiClient apiClient,
    required SecureStorageService storage,
    FirebaseMessaging? messaging,
    FlutterLocalNotificationsPlugin? localNotifications,
  }) : _apiClient = apiClient,
       _storage = storage,
       _messaging = messaging ?? FirebaseMessaging.instance,
       _localNotifications =
           localNotifications ?? FlutterLocalNotificationsPlugin();

  final ApiClient _apiClient;
  final SecureStorageService _storage;
  final FirebaseMessaging _messaging;
  final FlutterLocalNotificationsPlugin _localNotifications;
  final StreamController<Map<String, dynamic>> _notificationIntents =
      StreamController<Map<String, dynamic>>.broadcast();
  Map<String, dynamic>? _pendingIntent;

  Stream<Map<String, dynamic>> get notificationIntents =>
      _notificationIntents.stream;

  Map<String, dynamic>? takePendingIntent() {
    final intent = _pendingIntent;
    _pendingIntent = null;
    return intent;
  }

  Future<void> initialize() async {
    const initializationSettings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
    );

    await _localNotifications.initialize(
      initializationSettings,
      onDidReceiveNotificationResponse: (response) {
        final payload = response.payload;
        if (payload == null || payload.isEmpty) return;
        try {
          final data = jsonDecode(payload);
          if (data is Map<String, dynamic>) _emitIntent(data);
        } catch (_) {
          // Payload notifikasi versi lama diabaikan dengan aman.
        }
      },
    );
    await _localNotifications
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.createNotificationChannel(_notificationChannel);

    await _messaging.requestPermission(alert: true, badge: true, sound: true);

    FirebaseMessaging.onMessage.listen(_showForegroundNotification);
    FirebaseMessaging.onMessageOpenedApp.listen(
      (message) => _emitIntent(message.data),
    );
    _messaging.onTokenRefresh.listen((_) => syncToken());

    final initialMessage = await _messaging.getInitialMessage();
    if (initialMessage != null) {
      _pendingIntent = Map<String, dynamic>.from(initialMessage.data);
    }
  }

  Future<String?> token() => _messaging.getToken();

  Stream<String> get onTokenRefresh => _messaging.onTokenRefresh;

  Future<void> syncToken() async {
    try {
      final fcmToken = await token();
      if (fcmToken == null || fcmToken.isEmpty) return;

      await _apiClient.dio.post<void>(
        '/api/mobile/device-token',
        data: {
          'device_uuid': await _storage.deviceUuid(),
          'device_name': 'Mantau Umroh Android',
          'platform': 'android',
          'fcm_token': fcmToken,
        },
      );
    } catch (_) {
      // Sinkronisasi dicoba kembali saat token berubah atau pengguna login lagi.
    }
  }

  Future<void> _showForegroundNotification(RemoteMessage message) async {
    final notification = message.notification;
    if (notification == null) return;

    await _localNotifications.show(
      notification.hashCode,
      notification.title ?? 'Mantau Umroh',
      notification.body,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'umrah_alerts',
          'Peringatan Umrah',
          channelDescription: 'Notifikasi SOS dan peringatan monitoring jamaah.',
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
      ),
      payload: jsonEncode(message.data),
    );
  }

  void _emitIntent(Map<String, dynamic> data) {
    if (data.isEmpty) return;
    _pendingIntent = Map<String, dynamic>.from(data);
    _notificationIntents.add(Map<String, dynamic>.from(data));
  }
}
