import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import '../domain/staff_pilgrim.dart';
import 'staff_provider.dart';

class StaffActivationScreen extends StatefulWidget {
  const StaffActivationScreen({super.key});

  @override
  State<StaffActivationScreen> createState() => _StaffActivationScreenState();
}

class _StaffActivationScreenState extends State<StaffActivationScreen> {
  final _searchController = TextEditingController();
  String _query = '';
  int? _loadingPilgrimId;

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<StaffProvider>();
    final role = context.read<AuthProvider>().profile!.role;
    final query = _query.trim().toLowerCase();
    final pilgrims =
        provider.pilgrims.where((pilgrim) {
          if (query.isEmpty) return true;
          return pilgrim.fullName.toLowerCase().contains(query) ||
              pilgrim.registrationNumber.toLowerCase().contains(query);
        }).toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Aktivasi Jamaah',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            tooltip: 'Perbarui data',
            onPressed: () => provider.load(role, force: true),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => provider.load(role, force: true),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
          children: [
            TextField(
              controller: _searchController,
              onChanged: (value) => setState(() => _query = value),
              decoration: InputDecoration(
                hintText: 'Cari nama atau nomor registrasi',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon:
                    query.isEmpty
                        ? null
                        : IconButton(
                          tooltip: 'Hapus pencarian',
                          onPressed: () {
                            _searchController.clear();
                            setState(() => _query = '');
                          },
                          icon: const Icon(Icons.close_rounded),
                        ),
              ),
            ),
            const SizedBox(height: 14),
            Text(
              '${pilgrims.length} jamaah dalam rombongan',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: 10),
            if (pilgrims.isEmpty)
              const _EmptyActivation()
            else
              ...pilgrims.map(
                (pilgrim) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: _ActivationCard(
                    pilgrim: pilgrim,
                    isLoading: _loadingPilgrimId == pilgrim.id,
                    onReveal: () => _revealPin(pilgrim),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Future<void> _revealPin(StaffPilgrim pilgrim) async {
    if (_loadingPilgrimId != null) return;
    setState(() => _loadingPilgrimId = pilgrim.id);

    try {
      final info = await context.read<StaffProvider>().revealActivationPin(
        pilgrim.id,
      );
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder:
            (dialogContext) => AlertDialog(
              title: const Text('PIN Aktivasi Jamaah'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('${info.fullName} (${info.registrationNumber})'),
                  const SizedBox(height: 18),
                  Center(
                    child: SelectableText(
                      info.pin,
                      style: Theme.of(
                        dialogContext,
                      ).textTheme.headlineMedium?.copyWith(
                        fontWeight: FontWeight.w900,
                        letterSpacing: 6,
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  const Text(
                    'Berikan PIN hanya kepada jamaah ini untuk aktivasi aplikasi.',
                  ),
                ],
              ),
              actions: [
                TextButton.icon(
                  onPressed: () async {
                    await Clipboard.setData(ClipboardData(text: info.pin));
                    if (dialogContext.mounted) {
                      ScaffoldMessenger.of(dialogContext).showSnackBar(
                        const SnackBar(content: Text('PIN berhasil disalin.')),
                      );
                    }
                  },
                  icon: const Icon(Icons.copy_rounded),
                  label: const Text('Salin'),
                ),
                FilledButton(
                  onPressed: () => Navigator.pop(dialogContext),
                  child: const Text('Selesai'),
                ),
              ],
            ),
      );
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    } finally {
      if (mounted) setState(() => _loadingPilgrimId = null);
    }
  }
}

class _ActivationCard extends StatelessWidget {
  const _ActivationCard({
    required this.pilgrim,
    required this.isLoading,
    required this.onReveal,
  });

  final StaffPilgrim pilgrim;
  final bool isLoading;
  final VoidCallback onReveal;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              pilgrim.fullName,
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 3),
            Text(pilgrim.registrationNumber),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _StatusLabel(
                  icon: Icons.key_rounded,
                  label:
                      pilgrim.activationPinAvailable
                          ? 'PIN tersedia'
                          : 'PIN belum dibuat',
                  color:
                      pilgrim.activationPinAvailable
                          ? Colors.green
                          : Colors.orange,
                ),
                _StatusLabel(
                  icon: Icons.smartphone_rounded,
                  label:
                      pilgrim.deviceActive ? 'Perangkat aktif' : 'Belum aktif',
                  color: pilgrim.deviceActive ? Colors.blue : Colors.blueGrey,
                ),
              ],
            ),
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed:
                    pilgrim.activationPinAvailable && !isLoading
                        ? onReveal
                        : null,
                icon:
                    isLoading
                        ? const SizedBox.square(
                          dimension: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                        : const Icon(Icons.visibility_rounded),
                label: Text(isLoading ? 'Membuka PIN...' : 'Lihat PIN'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatusLabel extends StatelessWidget {
  const _StatusLabel({
    required this.icon,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 15, color: color),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(color: color, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}

class _EmptyActivation extends StatelessWidget {
  const _EmptyActivation();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 48),
      child: Column(
        children: [
          Icon(Icons.key_off_rounded, size: 56, color: Colors.blueGrey),
          SizedBox(height: 12),
          Text('Belum ada jamaah dalam rombongan aktif.'),
        ],
      ),
    );
  }
}
