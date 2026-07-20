import 'package:flutter/material.dart';

class LocationPermissionGuideScreen extends StatelessWidget {
  const LocationPermissionGuideScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Panduan Izin Lokasi',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
        children: [
          Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 720),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Container(
                    padding: const EdgeInsets.all(22),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF0F2F6B), Color(0xFF2563EB)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(28),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(
                          Icons.location_on_rounded,
                          color: Colors.white,
                          size: 42,
                        ),
                        SizedBox(height: 14),
                        Text(
                          'Agar petugas dapat membantu, lokasi harus aktif.',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 21,
                            fontWeight: FontWeight.w900,
                            height: 1.25,
                          ),
                        ),
                        SizedBox(height: 8),
                        Text(
                          'Aplikasi akan mengirim posisi berkala saat perjalanan umroh berlangsung.',
                          style: TextStyle(color: Colors.white70, height: 1.45),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  const _GuideStep(
                    number: '1',
                    title: 'Aktifkan GPS perangkat',
                    description:
                        'Buka panel cepat HP lalu pastikan tombol Lokasi/GPS menyala.',
                    icon: Icons.gps_fixed_rounded,
                  ),
                  const _GuideStep(
                    number: '2',
                    title: 'Izinkan lokasi aplikasi',
                    description:
                        'Saat muncul permintaan izin, pilih Izinkan. Jika ada pilihan, gunakan “Selalu izinkan” agar tracking tetap stabil.',
                    icon: Icons.verified_user_rounded,
                  ),
                  const _GuideStep(
                    number: '3',
                    title: 'Matikan pembatas baterai',
                    description:
                        'Pada beberapa HP seperti Xiaomi, Oppo, Vivo, atau Samsung, buka Pengaturan Aplikasi lalu izinkan Mantau Umroh berjalan di latar belakang.',
                    icon: Icons.battery_saver_rounded,
                  ),
                  const _GuideStep(
                    number: '4',
                    title: 'Biarkan aplikasi tetap terpasang',
                    description:
                        'Jangan hapus aplikasi selama perjalanan. Jika HP diganti, minta bantuan Tour Leader untuk aktivasi ulang.',
                    icon: Icons.phone_android_rounded,
                  ),
                  const SizedBox(height: 12),
                  Card(
                    color: const Color(0xFFECFDF5),
                    child: Padding(
                      padding: const EdgeInsets.all(18),
                      child: Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: const Color(
                                0xFF22C55E,
                              ).withValues(alpha: 0.14),
                              borderRadius: BorderRadius.circular(16),
                            ),
                            child: const Icon(
                              Icons.support_agent_rounded,
                              color: Color(0xFF15803D),
                            ),
                          ),
                          const SizedBox(width: 14),
                          const Expanded(
                            child: Text(
                              'Jika bingung, langsung hubungi Tour Leader. Petugas dapat membantu mengecek izin lokasi di HP Anda.',
                              style: TextStyle(
                                color: Color(0xFF14532D),
                                fontWeight: FontWeight.w700,
                                height: 1.35,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _GuideStep extends StatelessWidget {
  const _GuideStep({
    required this.number,
    required this.title,
    required this.description,
    required this.icon,
  });

  final String number;
  final String title;
  final String description;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Stack(
              alignment: Alignment.center,
              children: [
                Container(
                  width: 54,
                  height: 54,
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(18),
                  ),
                ),
                Icon(icon, color: const Color(0xFF2563EB)),
                Positioned(
                  right: 5,
                  bottom: 5,
                  child: Container(
                    width: 19,
                    height: 19,
                    alignment: Alignment.center,
                    decoration: const BoxDecoration(
                      color: Color(0xFF22C55E),
                      shape: BoxShape.circle,
                    ),
                    child: Text(
                      number,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(description, style: const TextStyle(height: 1.45)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
