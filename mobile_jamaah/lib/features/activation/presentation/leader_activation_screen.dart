import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../data/activation_repository.dart';
import '../domain/activation_models.dart';

class LeaderActivationScreen extends StatefulWidget {
  const LeaderActivationScreen({super.key});

  @override
  State<LeaderActivationScreen> createState() => _LeaderActivationScreenState();
}

class _LeaderActivationScreenState extends State<LeaderActivationScreen> {
  final _search = TextEditingController();
  List<ActivationPilgrim> _pilgrims = const [];
  List<PendingActivation> _pending = const [];
  bool _loading = true;
  String? _error;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _load();
    _timer = Timer.periodic(
      const Duration(seconds: 5),
      (_) => _load(silent: true),
    );
  }

  @override
  void dispose() {
    _timer?.cancel();
    _search.dispose();
    super.dispose();
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent && mounted) setState(() => _loading = true);
    try {
      final repository = context.read<ActivationRepository>();
      final results = await Future.wait([
        repository.pilgrims(),
        repository.pending(),
      ]);
      if (!mounted) return;
      setState(() {
        _pilgrims = results[0] as List<ActivationPilgrim>;
        _pending = results[1] as List<PendingActivation>;
        _error = null;
      });
    } catch (error) {
      if (mounted && !silent) setState(() => _error = error.toString());
    } finally {
      if (mounted && !silent) setState(() => _loading = false);
    }
  }

  Future<void> _approve(PendingActivation request) async {
    final approved = await showDialog<bool>(
      context: context,
      builder:
          (context) => AlertDialog(
            icon: const Icon(
              Icons.verified_user_rounded,
              color: Colors.blue,
              size: 42,
            ),
            title: const Text('Setujui perangkat?'),
            content: Text(
              '${request.pilgrimName}\n${request.registrationNumber}\n\n'
              'Perangkat: ${request.deviceName}',
              textAlign: TextAlign.center,
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Batal'),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Setujui Aktivasi'),
              ),
            ],
          ),
    );
    if (approved != true || !mounted) return;

    try {
      await context.read<ActivationRepository>().approve(request.publicId);
      await _load();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Perangkat Jamaah berhasil disetujui.'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString()), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final keyword = _search.text.toLowerCase();
    final filtered =
        _pilgrims
            .where(
              (item) =>
                  item.fullName.toLowerCase().contains(keyword) ||
                  item.registrationNumber.toLowerCase().contains(keyword),
            )
            .toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Aktivasi Jamaah'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _load,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body:
          _loading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
              ? _ErrorState(message: _error!, retry: _load)
              : RefreshIndicator(
                onRefresh: _load,
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    if (_pending.isNotEmpty) ...[
                      Text(
                        'Menunggu Persetujuan',
                        style: Theme.of(context).textTheme.titleMedium
                            ?.copyWith(fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 8),
                      ..._pending.map(
                        (item) => Card(
                          color: Colors.amber.shade50,
                          child: ListTile(
                            leading: const CircleAvatar(
                              child: Icon(Icons.phone_android_rounded),
                            ),
                            title: Text(item.pilgrimName),
                            subtitle: Text(
                              '${item.registrationNumber}\n${item.deviceName}',
                            ),
                            isThreeLine: true,
                            trailing: FilledButton(
                              onPressed: () => _approve(item),
                              child: const Text('Setujui'),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                    ],
                    TextField(
                      controller: _search,
                      onChanged: (_) => setState(() {}),
                      decoration: const InputDecoration(
                        labelText: 'Cari nama atau nomor registrasi',
                        prefixIcon: Icon(Icons.search_rounded),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'PIN Jamaah Rombongan',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Berikan PIN sesuai nama Jamaah. PIN baru dibuat oleh Admin Cabang.',
                    ),
                    const SizedBox(height: 8),
                    if (filtered.isEmpty)
                      const Padding(
                        padding: EdgeInsets.all(32),
                        child: Text(
                          'Jamaah tidak ditemukan.',
                          textAlign: TextAlign.center,
                        ),
                      )
                    else
                      ...filtered.map(
                        (pilgrim) => Card(
                          child: ListTile(
                            leading: CircleAvatar(
                              backgroundImage:
                                  pilgrim.photoUrl == null
                                      ? null
                                      : NetworkImage(pilgrim.photoUrl!),
                              child:
                                  pilgrim.photoUrl == null
                                      ? Text(
                                        pilgrim.fullName
                                            .substring(0, 1)
                                            .toUpperCase(),
                                      )
                                      : null,
                            ),
                            title: Text(pilgrim.fullName),
                            subtitle: Text(
                              '${pilgrim.registrationNumber}\n'
                              '${pilgrim.activationStatus == 'active' ? 'Aktif: ${pilgrim.deviceName ?? 'Perangkat Jamaah'}' : 'Belum diaktifkan'}',
                            ),
                            isThreeLine: true,
                            trailing:
                                pilgrim.activationPin == null
                                    ? const Chip(label: Text('Tidak ada PIN'))
                                    : Column(
                                      mainAxisAlignment:
                                          MainAxisAlignment.center,
                                      crossAxisAlignment:
                                          CrossAxisAlignment.end,
                                      children: [
                                        const Text(
                                          'PIN',
                                          style: TextStyle(fontSize: 11),
                                        ),
                                        SelectableText(
                                          '${pilgrim.activationPin!.substring(0, 3)} '
                                          '${pilgrim.activationPin!.substring(3)}',
                                          style: const TextStyle(
                                            fontSize: 18,
                                            fontWeight: FontWeight.bold,
                                            letterSpacing: 2,
                                            color: Colors.blue,
                                          ),
                                        ),
                                      ],
                                    ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.retry});

  final String message;
  final Future<void> Function({bool silent}) retry;

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(message, textAlign: TextAlign.center),
          TextButton(onPressed: retry, child: const Text('Coba lagi')),
        ],
      ),
    ),
  );
}
