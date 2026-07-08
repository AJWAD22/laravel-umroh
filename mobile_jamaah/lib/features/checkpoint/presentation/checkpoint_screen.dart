import 'package:flutter/material.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';

import '../../../core/widgets/internal_direction_map_screen.dart';
import '../../location/data/location_repository.dart';
import '../domain/checkpoint.dart';
import 'checkpoint_provider.dart';

class CheckpointScreen extends StatefulWidget {
  const CheckpointScreen({super.key, this.allowCreate = false});

  final bool allowCreate;

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
          if (widget.allowCreate)
            IconButton(
              tooltip: 'Tambah titik kumpul',
              onPressed: () => _openCreateForm(context),
              icon: const Icon(Icons.add_location_alt_rounded),
            ),
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
              'Pilih tujuan, lalu buka peta dari posisi Anda saat ini.',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 18),
            TextField(
              controller: _searchController,
              onChanged: (value) => setState(() => _query = value),
              decoration: const InputDecoration(
                hintText: 'Cari Masjidil Haram, hotel, klinik...',
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

  Future<void> _openCreateForm(BuildContext context) async {
    final created = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const MeetingPointFormScreen()),
    );
    if (created == true && mounted) {
      await context.read<CheckpointProvider>().load();
    }
  }
}

class MeetingPointFormScreen extends StatefulWidget {
  const MeetingPointFormScreen({super.key});

  @override
  State<MeetingPointFormScreen> createState() => _MeetingPointFormScreenState();
}

class _MeetingPointFormScreenState extends State<MeetingPointFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _addressController = TextEditingController();
  final _descriptionController = TextEditingController();
  String _city = 'other';
  String? _error;

  @override
  void dispose() {
    _nameController.dispose();
    _addressController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSaving = context.watch<CheckpointProvider>().isCreating;

    return Scaffold(
      appBar: AppBar(title: const Text('Tambah Titik Kumpul')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(18),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 46,
                          height: 46,
                          decoration: BoxDecoration(
                            color: const Color(
                              0xFF3B82F6,
                            ).withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: const Icon(
                            Icons.groups_2_rounded,
                            color: Color(0xFF2563EB),
                          ),
                        ),
                        const SizedBox(width: 12),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Buat patokan berkumpul',
                                style: TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              SizedBox(height: 3),
                              Text(
                                'Lokasi yang tersimpan memakai GPS perangkat ini.',
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    Form(
                      key: _formKey,
                      child: Column(
                        children: [
                          TextFormField(
                            controller: _nameController,
                            textInputAction: TextInputAction.next,
                            decoration: const InputDecoration(
                              labelText: 'Nama titik kumpul',
                              hintText: 'Contoh: Depan Lobby Hotel',
                              prefixIcon: Icon(Icons.place_rounded),
                            ),
                            validator:
                                (value) =>
                                    value == null || value.trim().isEmpty
                                        ? 'Nama titik kumpul wajib diisi.'
                                        : null,
                          ),
                          const SizedBox(height: 12),
                          DropdownButtonFormField<String>(
                            value: _city,
                            decoration: const InputDecoration(
                              labelText: 'Kota',
                              prefixIcon: Icon(Icons.location_city_rounded),
                            ),
                            items: const [
                              DropdownMenuItem(
                                value: 'makkah',
                                child: Text('Makkah'),
                              ),
                              DropdownMenuItem(
                                value: 'madinah',
                                child: Text('Madinah'),
                              ),
                              DropdownMenuItem(
                                value: 'jeddah',
                                child: Text('Jeddah'),
                              ),
                              DropdownMenuItem(
                                value: 'other',
                                child: Text('Lainnya'),
                              ),
                            ],
                            onChanged:
                                (value) =>
                                    setState(() => _city = value ?? 'other'),
                          ),
                          const SizedBox(height: 12),
                          TextFormField(
                            controller: _addressController,
                            textInputAction: TextInputAction.next,
                            decoration: const InputDecoration(
                              labelText: 'Catatan lokasi',
                              hintText: 'Contoh: dekat pintu utama',
                              prefixIcon: Icon(Icons.notes_rounded),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextFormField(
                            controller: _descriptionController,
                            minLines: 2,
                            maxLines: 4,
                            decoration: const InputDecoration(
                              labelText: 'Keterangan',
                              hintText:
                                  'Contoh: titik kumpul setelah shalat Isya',
                              prefixIcon: Icon(Icons.info_outline_rounded),
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (_error != null) ...[
                      const SizedBox(height: 14),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEF2F2),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFFECACA)),
                        ),
                        child: Text(
                          _error!,
                          style: const TextStyle(
                            color: Color(0xFF991B1B),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 18),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: isSaving ? null : _save,
                        icon:
                            isSaving
                                ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                                : const Icon(Icons.my_location_rounded),
                        label: Text(
                          isSaving
                              ? 'Menyimpan titik...'
                              : 'Gunakan Lokasi Saat Ini & Simpan',
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      'Pastikan petugas sedang berada di titik kumpul sebelum menyimpan.',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _error = null);
    try {
      final position =
          await context.read<LocationRepository>().currentPosition();
      if (!mounted) return;
      await context.read<CheckpointProvider>().createMeetingPoint(
        name: _nameController.text.trim(),
        city: _city,
        address: _addressController.text.trim(),
        description: _descriptionController.text.trim(),
        latitude: position.latitude,
        longitude: position.longitude,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Titik kumpul berhasil disimpan.')),
      );
      Navigator.pop(context, true);
    } catch (exception) {
      if (!mounted) return;
      setState(() => _error = exception.toString());
    }
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
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      _InfoChip(
                        icon: Icons.category_rounded,
                        label:
                            '${_categoryLabel(checkpoint.category)} • ${_cityLabel(checkpoint.city)}',
                      ),
                      _InfoChip(
                        icon:
                            checkpoint.groupId != null
                                ? Icons.groups_rounded
                                : checkpoint.departureId != null
                                ? Icons.flight_takeoff_rounded
                                : Icons.public_rounded,
                        label:
                            checkpoint.groupId != null
                                ? 'Khusus rombongan'
                                : checkpoint.departureId != null
                                ? 'Khusus keberangkatan'
                                : 'Umum cabang',
                      ),
                    ],
                  ),
                  if (checkpoint.address?.trim().isNotEmpty ?? false) ...[
                    const SizedBox(height: 10),
                    Text(checkpoint.address!),
                  ],
                  if (checkpoint.description?.trim().isNotEmpty ?? false) ...[
                    const SizedBox(height: 6),
                    Text(
                      checkpoint.description!,
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                  const SizedBox(height: 12),
                  FilledButton.icon(
                    onPressed: () => _openMap(context),
                    icon: const Icon(Icons.map_rounded),
                    label: const Text('Lihat Arah di Peta'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _openMap(BuildContext context) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder:
            (_) => InternalDirectionMapScreen(
              title: checkpoint.name,
              target: LatLng(checkpoint.latitude, checkpoint.longitude),
              targetName: checkpoint.name,
              targetSubtitle:
                  checkpoint.address ?? _categoryLabel(checkpoint.category),
              targetIcon: _categoryIcon(checkpoint.category),
              targetColor: Colors.blue,
            ),
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  const _InfoChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14),
          const SizedBox(width: 6),
          Text(label, style: Theme.of(context).textTheme.labelSmall),
        ],
      ),
    );
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
