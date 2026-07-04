import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import '../data/activation_repository.dart';
import '../domain/activation_models.dart';

class JamaahActivationScreen extends StatefulWidget {
  const JamaahActivationScreen({super.key});

  @override
  State<JamaahActivationScreen> createState() => _JamaahActivationScreenState();
}

class _JamaahActivationScreenState extends State<JamaahActivationScreen> {
  final _formKey = GlobalKey<FormState>();
  final _pin = TextEditingController();
  bool _processing = false;
  String? _error;

  @override
  void dispose() {
    _pin.dispose();
    super.dispose();
  }

  Future<void> _activate() async {
    if (!_formKey.currentState!.validate() || _processing) return;
    FocusScope.of(context).unfocus();
    final repository = context.read<ActivationRepository>();
    setState(() {
      _processing = true;
      _error = null;
    });

    try {
      final claim = await repository.claim(numericCode: _pin.text.trim());
      if (!mounted) return;
      await Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => ActivationWaitingScreen(claim: claim),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.toString();
        _processing = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Aktivasi Jamaah')),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(28),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const CircleAvatar(
                          radius: 38,
                          backgroundColor: Color(0xFFE8F5E9),
                          child: Icon(
                            Icons.pin_rounded,
                            size: 42,
                            color: Colors.green,
                          ),
                        ),
                        const SizedBox(height: 20),
                        Text(
                          'Masukkan PIN Jamaah',
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.headlineSmall
                              ?.copyWith(fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'PIN enam digit tersedia dari Admin Cabang dan dapat dilihat oleh Tour Leader rombongan Anda.',
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 28),
                        TextFormField(
                          controller: _pin,
                          autofocus: true,
                          keyboardType: TextInputType.number,
                          textInputAction: TextInputAction.done,
                          maxLength: 6,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                          ],
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 32,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 10,
                          ),
                          decoration: const InputDecoration(
                            labelText: 'PIN Aktivasi',
                            hintText: '000000',
                            counterText: '',
                          ),
                          validator:
                              (value) =>
                                  value?.length == 6
                                      ? null
                                      : 'PIN harus terdiri dari 6 angka.',
                          onFieldSubmitted: (_) => _activate(),
                        ),
                        if (_error != null) ...[
                          const SizedBox(height: 12),
                          Text(
                            _error!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: Colors.red),
                          ),
                        ],
                        const SizedBox(height: 22),
                        FilledButton.icon(
                          onPressed: _processing ? null : _activate,
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(56),
                            backgroundColor: Colors.green.shade700,
                          ),
                          icon:
                              _processing
                                  ? const SizedBox.square(
                                    dimension: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                  : const Icon(Icons.lock_open_rounded),
                          label: const Text('Aktifkan Perangkat'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class ActivationWaitingScreen extends StatefulWidget {
  const ActivationWaitingScreen({super.key, required this.claim});

  final ActivationClaim claim;

  @override
  State<ActivationWaitingScreen> createState() =>
      _ActivationWaitingScreenState();
}

class _ActivationWaitingScreenState extends State<ActivationWaitingScreen> {
  Timer? _timer;
  bool _checking = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _check();
    _timer = Timer.periodic(const Duration(seconds: 3), (_) => _check());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _check() async {
    if (_checking) return;
    _checking = true;
    try {
      final profile = await context
          .read<ActivationRepository>()
          .activationStatus(widget.claim);
      if (profile != null && mounted) {
        _timer?.cancel();
        context.read<AuthProvider>().completeActivation(profile);
        Navigator.popUntil(context, (route) => route.isFirst);
      }
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      _checking = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Menunggu Persetujuan'),
        automaticallyImplyLeading: false,
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 440),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const SizedBox.square(
                  dimension: 64,
                  child: CircularProgressIndicator(strokeWidth: 6),
                ),
                const SizedBox(height: 28),
                Text(
                  'Aktivasi ${widget.claim.pilgrimName}',
                  textAlign: TextAlign.center,
                  style: Theme.of(
                    context,
                  ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 12),
                const Text(
                  'Minta Tour Leader memeriksa nama dan perangkat Anda, lalu menekan tombol Setujui Aktivasi.',
                  textAlign: TextAlign.center,
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(
                    _error!,
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.red),
                  ),
                ],
                const SizedBox(height: 20),
                OutlinedButton(
                  onPressed: _check,
                  child: const Text('Periksa Sekarang'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
