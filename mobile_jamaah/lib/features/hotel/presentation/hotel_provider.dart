import 'package:flutter/foundation.dart';

import '../data/hotel_repository.dart';
import '../domain/hotel.dart';

class HotelProvider extends ChangeNotifier {
  HotelProvider(this._repository);

  final HotelRepository _repository;
  List<Hotel> hotels = [];
  bool isLoading = false;
  String? error;

  Future<void> load() async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      hotels = await _repository.getHotels();
    } catch (exception) {
      error = exception.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
