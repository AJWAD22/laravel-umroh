import 'package:url_launcher/url_launcher.dart';

Future<bool> openNavigation(double latitude, double longitude) {
  final destination = '$latitude,$longitude';
  return launchUrl(
    Uri.https('www.google.com', '/maps/dir/', {
      'api': '1',
      'destination': destination,
      'travelmode': 'walking',
    }),
    mode: LaunchMode.externalApplication,
  );
}
