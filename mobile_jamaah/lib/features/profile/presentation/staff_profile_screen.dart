import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../auth/presentation/auth_provider.dart';

class StaffProfileScreen extends StatelessWidget {
  const StaffProfileScreen({super.key});

  Future<void> _pickPhoto(BuildContext context) async {
    final image = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 82,
      maxWidth: 1200,
      maxHeight: 1200,
    );
    if (image == null || !context.mounted) return;

    final auth = context.read<AuthProvider>();
    final success = await auth.updateProfilePhoto(image.path);
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          success
              ? 'Foto profil berhasil diperbarui.'
              : auth.error ?? 'Foto gagal diperbarui.',
        ),
        backgroundColor: success ? Colors.green : Colors.red,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final profile = context.watch<AuthProvider>().profile!;
    final roleName =
        profile.role == 'tour-leader' ? 'Tour Leader' : 'Muthawwif';

    return Scaffold(
      appBar: AppBar(title: Text('Profil $roleName')),
      body: RefreshIndicator(
        onRefresh: context.read<AuthProvider>().refreshProfile,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
            Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 620),
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 44,
                          backgroundImage:
                              profile.photoUrl == null
                                  ? null
                                  : NetworkImage(profile.photoUrl!),
                          child:
                              profile.photoUrl == null
                                  ? Text(
                                    profile.name.substring(0, 1).toUpperCase(),
                                    style: const TextStyle(fontSize: 28),
                                  )
                                  : null,
                        ),
                        const SizedBox(height: 10),
                        OutlinedButton.icon(
                          onPressed:
                              context.watch<AuthProvider>().isPhotoUploading
                                  ? null
                                  : () => _pickPhoto(context),
                          icon:
                              context.watch<AuthProvider>().isPhotoUploading
                                  ? const SizedBox.square(
                                    dimension: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  )
                                  : const Icon(Icons.photo_camera_outlined),
                          label: const Text('Ganti Foto'),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          profile.name,
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.titleLarge
                              ?.copyWith(fontWeight: FontWeight.bold),
                        ),
                        Text(roleName),
                        const Divider(height: 36),
                        _ProfileRow(
                          icon: Icons.badge_outlined,
                          label: 'Nomor Petugas',
                          value: profile.registrationNumber,
                        ),
                        _ProfileRow(
                          icon: Icons.email_outlined,
                          label: 'Email',
                          value: profile.email,
                        ),
                        _ProfileRow(
                          icon: Icons.phone_outlined,
                          label: 'Telepon',
                          value: profile.phone ?? '-',
                        ),
                        _ProfileRow(
                          icon: Icons.apartment_rounded,
                          label: 'Cabang',
                          value: profile.branchName,
                        ),
                      ],
                    ),
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

class _ProfileRow extends StatelessWidget {
  const _ProfileRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => ListTile(
    contentPadding: EdgeInsets.zero,
    leading: Icon(icon),
    title: Text(label),
    subtitle: Text(value),
  );
}
