import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';
import '../data/activation_repository.dart';
import '../domain/activation_models.dart';

class JamaahActivationScreen extends StatelessWidget {
  const JamaahActivationScreen({super.key});

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
              child: const JamaahActivationForm(),
            ),
          ),
        ),
      ),
    );
  }
}

class JamaahActivationForm extends StatefulWidget {
  const JamaahActivationForm({
    super.key,
    this.autofocus = true,
    this.embedded = false,
  });

  final bool autofocus;
  final bool embedded;

  @override
  State<JamaahActivationForm> createState() => _JamaahActivationFormState();
}

class _JamaahActivationFormState extends State<JamaahActivationForm> {
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
      final route = MaterialPageRoute(
        builder: (_) => ActivationWaitingScreen(claim: claim),
      );
      if (widget.embedded) {
        await Navigator.push(context, route);
      } else {
        await Navigator.pushReplacement(context, route);
      }
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
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Card(
      color: isDark ? const Color(0xFF1E293B) : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Align(
                alignment: Alignment.centerLeft,
                child: Container(
                  width: 54,
                  height: 54,
                  decoration: BoxDecoration(
                    color: const Color(0xFF22C55E).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Icon(
                    Icons.key_rounded,
                    size: 28,
                    color: Color(0xFF22C55E),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                'Aktivasi Jamaah',
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 6),
              Text(
                'Masukkan PIN Aktivasi yang diberikan oleh Tour Leader.',
                style: Theme.of(
                  context,
                ).textTheme.bodyMedium?.copyWith(height: 1.5),
              ),
              const SizedBox(height: 22),
              TextFormField(
                controller: _pin,
                autofocus: widget.autofocus,
                keyboardType: TextInputType.number,
                textInputAction: TextInputAction.done,
                maxLength: 6,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 9,
                ),
                decoration: const InputDecoration(
                  labelText: 'PIN Aktivasi',
                  hintText: '000000',
                  counterText: '',
                  prefixIcon: Icon(Icons.pin_outlined),
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
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ],
              const SizedBox(height: 18),
              FilledButton.icon(
                onPressed: _processing ? null : _activate,
                style: FilledButton.styleFrom(
                  minimumSize: const Size.fromHeight(56),
                  backgroundColor: const Color(0xFF22C55E),
                  foregroundColor: const Color(0xFF052E16),
                ),
                icon:
                    _processing
                        ? const SizedBox.square(
                          dimension: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Color(0xFF052E16),
                          ),
                        )
                        : const Icon(Icons.verified_user_outlined),
                label: const Text('Aktivasi Jamaah'),
              ),
              const SizedBox(height: 16),
              Text.rich(
                const TextSpan(
                  text: 'Belum memiliki PIN?\n',
                  children: [
                    TextSpan(
                      text: 'Silakan hubungi Tour Leader.',
                      style: TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
                textAlign: TextAlign.center,
                style: Theme.of(
                  context,
                ).textTheme.bodySmall?.copyWith(height: 1.5),
              ),
            ],
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
