import 'package:flutter/foundation.dart';

import '../../profile/domain/jamaah_profile.dart';
import '../../../core/notifications/firebase_notification_service.dart';
import '../data/auth_repository.dart';

class AuthProvider extends ChangeNotifier {
  AuthProvider(this._repository, this._notifications);

  final AuthRepository _repository;
  final FirebaseNotificationService _notifications;
  JamaahProfile? profile;
  bool isInitializing = true;
  bool isLoading = false;
  String? error;

  bool get isAuthenticated => profile != null;

  Future<void> initialize() async {
    if (await _repository.hasToken()) {
      try {
        profile = await _repository.profile();
        await _notifications.syncToken();
      } catch (_) {
        await _repository.logout();
      }
    }
    isInitializing = false;
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      profile = await _repository.login(email: email, password: password);
      await _notifications.syncToken();
      return true;
    } catch (exception) {
      error = exception.toString();
      return false;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> refreshProfile() async {
    profile = await _repository.profile();
    notifyListeners();
  }

  Future<void> completeActivation(JamaahProfile activatedProfile) async {
    profile = activatedProfile;
    error = null;
    await _notifications.syncToken();
    notifyListeners();
  }

  Future<void> logout() async {
    await _repository.logout();
    profile = null;
    notifyListeners();
  }
}
