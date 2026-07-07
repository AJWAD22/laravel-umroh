import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';

import '../../../core/widgets/app_error_view.dart';
import '../../../core/widgets/internal_direction_map_screen.dart';
import '../domain/hotel.dart';
import 'hotel_provider.dart';

class HotelScreen extends StatefulWidget {
  const HotelScreen({super.key, this.staffRole});

  final String? staffRole;

  @override
  State<HotelScreen> createState() => _HotelScreenState();
}

class _HotelScreenState extends State<HotelScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<HotelProvider>().load(staffRole: widget.staffRole),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<HotelProvider>();
    return Scaffold(
      appBar: AppBar(title: const Text('Hotel Jamaah')),
      body:
          provider.isLoading
              ? const Center(child: CircularProgressIndicator())
              : provider.error != null
              ? AppErrorView(
                message: provider.error!,
                onRetry: () => provider.load(staffRole: widget.staffRole),
              )
              : provider.hotels.isEmpty
              ? const AppErrorView(
                message: 'Hotel belum ditentukan untuk keberangkatan Anda.',
              )
              : _HotelContent(hotels: provider.hotels),
    );
  }
}

class _HotelContent extends StatelessWidget {
  const _HotelContent({required this.hotels});

  final List<Hotel> hotels;

  @override
  Widget build(BuildContext context) {
    final mapped =
        hotels
            .where((hotel) => hotel.latitude != null && hotel.longitude != null)
            .toList();
    final center =
        mapped.isNotEmpty
            ? LatLng(mapped.first.latitude!, mapped.first.longitude!)
            : const LatLng(21.4225, 39.8262);

    return LayoutBuilder(
      builder: (context, constraints) {
        final map = SizedBox(
          height: constraints.maxWidth >= 700 ? constraints.maxHeight : 320,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: FlutterMap(
              options: MapOptions(initialCenter: center, initialZoom: 15),
              children: [
                TileLayer(
                  urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                  userAgentPackageName: 'id.umrahmonitor.umrah_jamaah',
                ),
                MarkerLayer(
                  markers:
                      mapped
                          .map(
                            (hotel) => Marker(
                              point: LatLng(hotel.latitude!, hotel.longitude!),
                              width: 54,
                              height: 54,
                              child: const Icon(
                                Icons.location_pin,
                                color: Colors.red,
                                size: 46,
                              ),
                            ),
                          )
                          .toList(),
                ),
              ],
            ),
          ),
        );

        final list = ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: hotels.length,
          separatorBuilder: (_, __) => const SizedBox(height: 10),
          itemBuilder: (context, index) {
            final hotel = hotels[index];
            return Card(
              child: ListTile(
                leading: const CircleAvatar(child: Icon(Icons.hotel_rounded)),
                title: Text(
                  hotel.name,
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                subtitle: Text(
                  '${hotel.address ?? '-'}\n'
                  '${hotel.latitude?.toStringAsFixed(7) ?? '-'}, '
                  '${hotel.longitude?.toStringAsFixed(7) ?? '-'}',
                ),
                isThreeLine: true,
                trailing:
                    hotel.latitude == null || hotel.longitude == null
                        ? null
                        : IconButton.filledTonal(
                          tooltip: 'Lihat arah ke hotel',
                          onPressed:
                              () => _openMap(
                                context,
                                hotel,
                              ),
                          icon: const Icon(Icons.map_rounded),
                        ),
              ),
            );
          },
        );

        if (constraints.maxWidth >= 700) {
          return Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(flex: 3, child: map),
                const SizedBox(width: 12),
                Expanded(flex: 2, child: list),
              ],
            ),
          );
        }
        return ListView(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
          children: [
            map,
            ...hotels.map(
              (hotel) => Card(
                child: ListTile(
                  leading: const Icon(Icons.hotel_rounded),
                  title: Text(hotel.name),
                  subtitle: Text(
                    '${hotel.address ?? '-'}\n'
                    '${hotel.latitude?.toStringAsFixed(7) ?? '-'}, '
                    '${hotel.longitude?.toStringAsFixed(7) ?? '-'}',
                  ),
                  isThreeLine: true,
                  trailing:
                      hotel.latitude == null || hotel.longitude == null
                          ? null
                          : IconButton.filledTonal(
                            tooltip: 'Lihat arah ke hotel',
                            onPressed:
                                () => _openMap(
                                  context,
                                  hotel,
                                ),
                            icon: const Icon(Icons.map_rounded),
                          ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  void _openMap(BuildContext context, Hotel hotel) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder:
            (_) => InternalDirectionMapScreen(
              title: hotel.name,
              target: LatLng(hotel.latitude!, hotel.longitude!),
              targetName: hotel.name,
              targetSubtitle: hotel.address,
              targetIcon: Icons.hotel_rounded,
              targetColor: Colors.indigo,
            ),
      ),
    );
  }
}
