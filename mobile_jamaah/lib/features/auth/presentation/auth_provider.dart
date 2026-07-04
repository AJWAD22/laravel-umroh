import 'package:flutter/foundation.dart';

import '../../profile/domain/jamaah_profile.dart';
import '../data/auth_repository.dart';

class AuthProvider extends ChangeNotifier {
  AuthProvider(this._repository);

  final AuthRepository _repository;
  JamaahProfile? profile;
  bool isInitializing = true;
  bool isLoading = false;
  bool isPhotoUploading = false;
  String? error;

  bool get isAuthenticated => profile != null;

  Future<void> initialize() async {
    if (await _repository.hasToken()) {
      try {
        profile = await _repository.profile();
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

  void completeActivation(JamaahProfile activatedProfile) {
    profile = activatedProfile;
    error = null;
    notifyListeners();
  }

  Future<bool> updateProfilePhoto(String filePath) async {
    isPhotoUploading = true;
    error = null;
    notifyListeners();
    try {
      profile = await _repository.updateProfilePhoto(filePath);
      return true;
    } catch (exception) {
      error = exception.toString();
      return false;
    } finally {
      isPhotoUploading = false;
      notifyListeners();
    }
  }

  Future<void> logout() async {
    await _repository.logout();
    profile = null;
    notifyListeners();
  }
}
