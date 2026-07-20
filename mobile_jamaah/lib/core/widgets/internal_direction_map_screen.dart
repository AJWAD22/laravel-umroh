import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';

import '../../features/location/data/location_repository.dart';

class InternalDirectionMapScreen extends StatefulWidget {
  const InternalDirectionMapScreen({
    super.key,
    required this.title,
    required this.target,
    required this.targetName,
    this.targetSubtitle,
    this.targetIcon = Icons.location_pin,
    this.targetColor = Colors.blue,
    this.bottom,
  });

  final String title;
  final LatLng target;
  final String targetName;
  final String? targetSubtitle;
  final IconData targetIcon;
  final Color targetColor;
  final Widget? bottom;

  @override
  State<InternalDirectionMapScreen> createState() =>
      _InternalDirectionMapScreenState();
}

class _InternalDirectionMapScreenState
    extends State<InternalDirectionMapScreen> {
  final _mapController = MapController();
  StreamSubscription<Position>? _positionSubscription;
  LatLng? _myPoint;
  String? _locationError;
  bool _loadingLocation = true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _startLocation());
  }

  @override
  void dispose() {
    _positionSubscription?.cancel();
    super.dispose();
  }

  Future<void> _startLocation() async {
    final repository = context.read<LocationRepository>();

    try {
      final current = await repository.currentPosition();
      if (!mounted) return;
      setState(() {
        _myPoint = LatLng(current.latitude, current.longitude);
        _loadingLocation = false;
      });
      _fitToPoints();

      final positions = await repository.navigationPositions();
      _positionSubscription = positions.listen((position) {
        if (!mounted) return;
        setState(() {
          _myPoint = LatLng(position.latitude, position.longitude);
          _locationError = null;
          _loadingLocation = false;
        });
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _locationError = error.toString();
        _loadingLocation = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final myPoint = _myPoint;

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        actions: [
          IconButton(
            tooltip: 'Fokuskan peta',
            onPressed: _fitToPoints,
            icon: const Icon(Icons.my_location_rounded),
          ),
        ],
      ),
      body: Stack(
        children: [
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(initialCenter: widget.target, initialZoom: 17),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'id.umrahmonitor.umrah_jamaah',
              ),
              if (myPoint != null)
                PolylineLayer(
                  polylines: [
                    Polyline(
                      points: [myPoint, widget.target],
                      color: Theme.of(context).colorScheme.primary,
                      strokeWidth: 4,
                    ),
                  ],
                ),
              MarkerLayer(
                markers: [
                  if (myPoint != null)
                    Marker(
                      point: myPoint,
                      width: 58,
                      height: 58,
                      child: _MapMarker(
                        color: Colors.green,
                        icon: Icons.my_location_rounded,
                        label: 'Saya',
                      ),
                    ),
                  Marker(
                    point: widget.target,
                    width: 64,
                    height: 64,
                    child: _MapMarker(
                      color: widget.targetColor,
                      icon: widget.targetIcon,
                      label: widget.targetName,
                    ),
                  ),
                ],
              ),
              RichAttributionWidget(
                attributions: const [
                  TextSourceAttribution('OpenStreetMap contributors'),
                ],
              ),
            ],
          ),
          Positioned(
            top: 12,
            left: 12,
            right: 12,
            child: _StatusBanner(
              loading: _loadingLocation,
              error: _locationError,
              hasLocation: myPoint != null,
            ),
          ),
          Positioned(
            left: 16,
            right: 16,
            bottom: 18,
            child: SafeArea(
              top: false,
              child:
                  widget.bottom ??
                  _DefaultInfoCard(
                    targetName: widget.targetName,
                    targetSubtitle: widget.targetSubtitle,
                    myPoint: myPoint,
                    target: widget.target,
                  ),
            ),
          ),
        ],
      ),
    );
  }

  void _fitToPoints() {
    final myPoint = _myPoint;
    if (!mounted) return;

    if (myPoint == null) {
      _mapController.move(widget.target, 17);
      return;
    }

    final center = LatLng(
      (myPoint.latitude + widget.target.latitude) / 2,
      (myPoint.longitude + widget.target.longitude) / 2,
    );
    _mapController.move(center, 16);
  }
}

class _MapMarker extends StatelessWidget {
  const _MapMarker({
    required this.color,
    required this.icon,
    required this.label,
  });

  final Color color;
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: label,
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: color,
          shape: BoxShape.circle,
          border: Border.all(color: Colors.white, width: 3),
          boxShadow: const [
            BoxShadow(
              color: Colors.black26,
              blurRadius: 10,
              offset: Offset(0, 4),
            ),
          ],
        ),
        child: Icon(icon, color: Colors.white, size: 32),
      ),
    );
  }
}

class _StatusBanner extends StatelessWidget {
  const _StatusBanner({
    required this.loading,
    required this.error,
    required this.hasLocation,
  });

  final bool loading;
  final String? error;
  final bool hasLocation;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final text =
        error != null
            ? 'Posisi saya belum tersedia'
            : loading
            ? 'Mengambil posisi saya...'
            : hasLocation
            ? 'Titik saya aktif'
            : 'Posisi saya belum tersedia';
    final icon =
        error != null
            ? Icons.location_off_rounded
            : loading
            ? Icons.gps_fixed_rounded
            : Icons.my_location_rounded;

    return Material(
      color: Colors.transparent,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: colorScheme.surface.withValues(alpha: 0.94),
          borderRadius: BorderRadius.circular(16),
          boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 12)],
        ),
        child: Row(
          children: [
            Icon(
              icon,
              size: 18,
              color: error == null ? Colors.green : Colors.red,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                text,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DefaultInfoCard extends StatelessWidget {
  const _DefaultInfoCard({
    required this.targetName,
    required this.target,
    required this.myPoint,
    this.targetSubtitle,
  });

  final String targetName;
  final LatLng target;
  final LatLng? myPoint;
  final String? targetSubtitle;

  @override
  Widget build(BuildContext context) {
    final distanceText =
        myPoint == null
            ? 'Menunggu posisi saya'
            : _formatDistance(
              const Distance().as(LengthUnit.Meter, myPoint!, target),
            );

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              targetName,
              style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
            ),
            if (targetSubtitle?.trim().isNotEmpty ?? false) ...[
              const SizedBox(height: 6),
              Text(targetSubtitle!),
            ],
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primaryContainer,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Row(
                children: [
                  const Icon(Icons.social_distance_rounded, size: 18),
                  const SizedBox(width: 8),
                  Text(
                    'Jarak lurus: $distanceText',
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  static String _formatDistance(double meters) {
    if (meters >= 1000) return '${(meters / 1000).toStringAsFixed(2)} km';
    return '${meters.round()} m';
  }
}
