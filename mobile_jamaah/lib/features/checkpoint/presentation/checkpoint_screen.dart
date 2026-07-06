import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../core/utils/external_navigation.dart';
import '../domain/checkpoint.dart';
import 'checkpoint_provider.dart';

class CheckpointScreen extends StatefulWidget {
  const CheckpointScreen({super.key});

  @override
  State<CheckpointScreen> createState() => _CheckpointScreenState();
}

class _CheckpointScreenState extends State<CheckpointScreen> {
  final _searchController = TextEditingController();
  String _query = '';
  String _city = 'semua';
  String _category = 'semua';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<CheckpointProvider>().load(),
    );
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<CheckpointProvider>();
    final filtered =
        provider.checkpoints.where((checkpoint) {
          final query = _query.trim().toLowerCase();
          final matchesQuery =
              query.isEmpty ||
              checkpoint.name.toLowerCase().contains(query) ||
              (checkpoint.address?.toLowerCase().contains(query) ?? false);
          return matchesQuery &&
              (_city == 'semua' || checkpoint.city == _city) &&
              (_category == 'semua' || checkpoint.category == _category);
        }).toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Tujuan Perjalanan'),
        actions: [
          IconButton(
            tooltip: 'Perbarui tujuan',
            onPressed: provider.load,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: provider.load,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
          children: [
            Text(
              'Mau ke mana?',
              style: Theme.of(
                context,
              ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 4),
            Text(
              'Pilih tujuan, lalu buka navigasi dari lokasi Anda saat ini.',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 18),
            TextField(
              controller: _searchController,
              onChanged: (value) => setState(() => _query = value),
              decoration: const InputDecoration(
                hintText: 'Cari Masjidil Haram, hotel, rumah sakit...',
                prefixIcon: Icon(Icons.search_rounded),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _city,
                    decoration: const InputDecoration(labelText: 'Kota'),
                    items: const [
                      DropdownMenuItem(value: 'semua', child: Text('Semua')),
                      DropdownMenuItem(value: 'makkah', child: Text('Makkah')),
                      DropdownMenuItem(
                        value: 'madinah',
                        child: Text('Madinah'),
                      ),
                      DropdownMenuItem(value: 'jeddah', child: Text('Jeddah')),
                      DropdownMenuItem(value: 'other', child: Text('Lainnya')),
                    ],
                    onChanged:
                        (value) => setState(() => _city = value ?? 'semua'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _category,
                    decoration: const InputDecoration(labelText: 'Kategori'),
                    items: const [
                      DropdownMenuItem(value: 'semua', child: Text('Semua')),
                      DropdownMenuItem(value: 'ibadah', child: Text('Ibadah')),
                      DropdownMenuItem(value: 'hotel', child: Text('Hotel')),
                      DropdownMenuItem(
                        value: 'titik_kumpul',
                        child: Text('Titik Kumpul'),
                      ),
                      DropdownMenuItem(
                        value: 'kesehatan',
                        child: Text('Kesehatan'),
                      ),
                      DropdownMenuItem(
                        value: 'transportasi',
                        child: Text('Transportasi'),
                      ),
                      DropdownMenuItem(
                        value: 'belanja',
                        child: Text('Belanja'),
                      ),
                      DropdownMenuItem(
                        value: 'lainnya',
                        child: Text('Lainnya'),
                      ),
                    ],
                    onChanged:
                        (value) => setState(() => _category = value ?? 'semua'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 18),
            if (provider.isLoading)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(32),
                  child: CircularProgressIndicator(),
                ),
              )
            else if (provider.error != null)
              _MessageCard(
                icon: Icons.cloud_off_rounded,
                message: provider.error!,
                action: FilledButton.icon(
                  onPressed: provider.load,
                  icon: const Icon(Icons.refresh_rounded),
                  label: const Text('Coba Lagi'),
                ),
              )
            else if (filtered.isEmpty)
              const _MessageCard(
                icon: Icons.location_off_outlined,
                message: 'Tujuan dengan filter tersebut belum tersedia.',
              )
            else ...[
              Text(
                '${filtered.length} tujuan ditemukan',
                style: Theme.of(context).textTheme.labelLarge,
              ),
              const SizedBox(height: 10),
              ...filtered.map(
                (checkpoint) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: _CheckpointCard(checkpoint: checkpoint),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _CheckpointCard extends StatelessWidget {
  const _CheckpointCard({required this.checkpoint});

  final Checkpoint checkpoint;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(child: Icon(_categoryIcon(checkpoint.category))),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    checkpoint.name,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${_categoryLabel(checkpoint.category)} • '
                    '${_cityLabel(checkpoint.city)}',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                  if (checkpoint.address?.trim().isNotEmpty ?? false) ...[
                    const SizedBox(height: 7),
                    Text(checkpoint.address!),
                  ],
                  if (checkpoint.description?.trim().isNotEmpty ?? false) ...[
                    const SizedBox(height: 5),
                    Text(
                      checkpoint.description!,
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                  const SizedBox(height: 12),
                  FilledButton.icon(
                    onPressed: () => _navigate(context),
                    icon: const Icon(Icons.directions_rounded),
                    label: const Text('Navigasi'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _navigate(BuildContext context) async {
    final opened = await openNavigation(
      checkpoint.latitude,
      checkpoint.longitude,
    );
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Aplikasi navigasi tidak dapat dibuka.')),
      );
    }
  }
}

class _MessageCard extends StatelessWidget {
  const _MessageCard({required this.icon, required this.message, this.action});

  final IconData icon;
  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsets.all(28),
      child: Column(
        children: [
          Icon(icon, size: 48, color: Colors.blueGrey),
          const SizedBox(height: 12),
          Text(message, textAlign: TextAlign.center),
          if (action != null) ...[const SizedBox(height: 14), action!],
        ],
      ),
    ),
  );
}

IconData _categoryIcon(String category) => switch (category) {
  'ibadah' => Icons.mosque_rounded,
  'hotel' => Icons.hotel_rounded,
  'titik_kumpul' => Icons.groups_rounded,
  'kesehatan' => Icons.local_hospital_rounded,
  'transportasi' => Icons.directions_bus_rounded,
  'belanja' => Icons.shopping_bag_rounded,
  _ => Icons.place_rounded,
};

String _categoryLabel(String category) => switch (category) {
  'ibadah' => 'Tempat Ibadah',
  'hotel' => 'Hotel',
  'titik_kumpul' => 'Titik Kumpul',
  'kesehatan' => 'Kesehatan',
  'transportasi' => 'Transportasi',
  'belanja' => 'Belanja',
  _ => 'Lainnya',
};

String _cityLabel(String city) => switch (city) {
  'makkah' => 'Makkah',
  'madinah' => 'Madinah',
  'jeddah' => 'Jeddah',
  _ => 'Lainnya',
};
