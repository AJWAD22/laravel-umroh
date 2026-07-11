import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import 'staff_pilgrim_detail_screen.dart';
import 'staff_provider.dart';

class StaffLocationsScreen extends StatelessWidget {
  const StaffLocationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final pilgrims = context.watch<StaffProvider>().locations;
    final role = context.read<AuthProvider>().profile!.role;
    if (pilgrims.isEmpty) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Lokasi Jamaah'),
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
          onRefresh:
              () => context.read<StaffProvider>().load(role, force: true),
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: const [
              SizedBox(height: 220),
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 32),
                child: Column(
                  children: [
                    Icon(
                      Icons.location_off_outlined,
                      size: 54,
                      color: Colors.blueGrey,
                    ),
                    SizedBox(height: 12),
                    Text(
                      'Belum ada lokasi jamaah',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 17,
                      ),
                    ),
                    SizedBox(height: 7),
                    Text(
                      'Pastikan petugas sudah ditentukan pada rombongan dan aplikasi jamaah sedang mengirim lokasi.',
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    }
    final first = pilgrims.first.location!;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Lokasi Jamaah'),
        actions: [
          IconButton(
            tooltip: 'Refresh data',
            onPressed:
                () => context.read<StaffProvider>().load(role, force: true),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: Stack(
        children: [
          FlutterMap(
            options: MapOptions(
              initialCenter: LatLng(first.latitude, first.longitude),
              initialZoom: 14,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'id.umrahmonitor.umrah_jamaah',
              ),
              MarkerLayer(
                markers:
                    pilgrims.map((pilgrim) {
                      final location = pilgrim.location!;
                      return Marker(
                        point: LatLng(location.latitude, location.longitude),
                        width: 54,
                        height: 54,
                        child: Tooltip(
                          message: pilgrim.fullName,
                          child: IconButton.filled(
                            style: IconButton.styleFrom(
                              backgroundColor: Colors.blue,
                            ),
                            onPressed:
                                () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder:
                                        (_) => StaffPilgrimDetailScreen(
                                          pilgrim: pilgrim,
                                        ),
                                  ),
                                ),
                            icon: const Icon(Icons.person_pin_circle_rounded),
                          ),
                        ),
                      );
                    }).toList(),
              ),
              RichAttributionWidget(
                attributions: const [
                  TextSourceAttribution('OpenStreetMap contributors'),
                ],
              ),
            ],
          ),
          if (context.watch<StaffProvider>().isLoading)
            const Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: LinearProgressIndicator(),
            ),
        ],
      ),
    );
  }
}
