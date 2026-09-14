import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/app_theme.dart';
import '../../../app/providers.dart';

class AppUpdateBanner extends ConsumerWidget {
  const AppUpdateBanner({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(appUpdateControllerProvider);
    final release = state.release;

    if (release == null || state.dismissed) return const SizedBox.shrink();

    return AnimatedSize(
      duration: const Duration(milliseconds: 240),
      curve: Curves.easeOutCubic,
      child: Container(
        width: double.infinity,
        margin: const EdgeInsets.fromLTRB(16, 10, 16, 2),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: const Color(0xFFFFF7DE),
          border: Border.all(color: const Color(0xFFE8C76D)),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(
                  Icons.system_update_alt_rounded,
                  color: Color(0xFF805B00),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        release.requiredUpdate
                            ? 'تحديث مهم متوفر'
                            : 'يتوفر إصدار جديد ${release.versionName}',
                        style: const TextStyle(
                          color: Color(0xFF604500),
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        state.installerOpened
                            ? 'تم التحقق من الملف. أكمل التثبيت من شاشة Android.'
                            : release.releaseNotes ??
                                  'زامن السجلات أولًا، ثم نزّل التحديث وثبّته دون حذف التطبيق.',
                        style: const TextStyle(
                          color: Color(0xFF775F21),
                          fontSize: 12,
                          height: 1.45,
                        ),
                      ),
                    ],
                  ),
                ),
                if (!release.requiredUpdate && !state.downloading)
                  IconButton(
                    tooltip: 'لاحقًا',
                    visualDensity: VisualDensity.compact,
                    onPressed: () => ref
                        .read(appUpdateControllerProvider.notifier)
                        .dismiss(),
                    icon: const Icon(Icons.close_rounded, size: 20),
                  ),
              ],
            ),
            if (state.error != null) ...[
              const SizedBox(height: 8),
              Text(
                state.error!,
                style: const TextStyle(
                  color: Color(0xFF9A2D25),
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
            if (state.downloading) ...[
              const SizedBox(height: 10),
              LinearProgressIndicator(
                value: state.progress > 0 ? state.progress : null,
                minHeight: 6,
                borderRadius: BorderRadius.circular(10),
                color: AppTheme.emerald,
                backgroundColor: Colors.white,
              ),
              const SizedBox(height: 5),
              Text(
                state.progress > 0
                    ? 'جاري تنزيل التحديث ${(state.progress * 100).round()}%'
                    : 'جاري بدء التنزيل…',
                style: const TextStyle(fontSize: 11, color: Color(0xFF775F21)),
              ),
            ] else ...[
              const SizedBox(height: 10),
              FilledButton.icon(
                onPressed: () =>
                    ref.read(appUpdateControllerProvider.notifier).download(),
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF735400),
                ),
                icon: const Icon(Icons.download_rounded, size: 19),
                label: Text(
                  state.installerOpened
                      ? 'إعادة فتح التثبيت'
                      : 'تنزيل التحديث الآمن',
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
