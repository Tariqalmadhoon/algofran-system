import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/app_theme.dart';
import '../../../app/providers.dart';
import '../../../core/database/app_database.dart';
import '../../../shared/widgets/brand_logo.dart';
import '../../daily/presentation/halaqa_students_screen.dart';
import '../../profile/presentation/teacher_profile_screen.dart';
import '../../update/presentation/app_update_banner.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen>
    with WidgetsBindingObserver {
  int _tab = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    Future.microtask(() {
      if (mounted) ref.read(appUpdateControllerProvider.notifier).check();
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.read(appUpdateControllerProvider.notifier).check();
    }
  }

  @override
  Widget build(BuildContext context) {
    final appState = ref.watch(appControllerProvider);
    ref.listen(appControllerProvider, (previous, next) {
      if (previous?.syncing == true && !next.syncing && next.error == null) {
        Future.microtask(() {
          if (mounted) ref.read(appUpdateControllerProvider.notifier).check();
        });
      }
      final text = next.error ?? next.message;
      if (text != null &&
          text != previous?.error &&
          text != previous?.message) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(
            SnackBar(
              content: Text(text),
              backgroundColor: next.error == null
                  ? AppTheme.emerald
                  : const Color(0xFF9A2D25),
              behavior: SnackBarBehavior.floating,
            ),
          );
      }
    });

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 20,
        title: Row(
          children: [
            const BrandLogo(size: 42),
            const SizedBox(width: 11),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('مركز الغفران'),
                  Text(
                    appState.userName,
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFF6A7C75),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'مزامنة الآن',
            onPressed: appState.syncing
                ? null
                : () => ref.read(appControllerProvider.notifier).syncNow(),
            icon: appState.syncing
                ? const SizedBox.square(
                    dimension: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.sync_rounded),
          ),
          PopupMenuButton<String>(
            onSelected: (value) async {
              if (value == 'logout') {
                ref.read(appControllerProvider.notifier).logout();
              } else if (value == 'updates') {
                await ref
                    .read(appUpdateControllerProvider.notifier)
                    .check(force: true);
                if (!mounted) return;
                if (ref.read(appUpdateControllerProvider).release == null) {
                  ScaffoldMessenger.of(this.context).showSnackBar(
                    const SnackBar(
                      content: Text(
                        'لم يتوفر تحديث من الخادم الآن. تأكد من اتصالك بالإنترنت.',
                      ),
                    ),
                  );
                }
              }
            },
            itemBuilder: (_) => const [
              PopupMenuItem(
                value: 'updates',
                child: Text('التحقق من تحديث التطبيق'),
              ),
              PopupMenuItem(value: 'logout', child: Text('تسجيل الخروج')),
            ],
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Column(
        children: [
          const AppUpdateBanner(),
          Expanded(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 280),
              switchInCurve: Curves.easeOutCubic,
              child: switch (_tab) {
                0 => const _HalaqasTab(key: ValueKey('halaqas')),
                1 => const TeacherProfileScreen(key: ValueKey('profile')),
                _ => const _OutboxTab(key: ValueKey('outbox')),
              },
            ),
          ),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (value) => setState(() => _tab = value),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.mosque_outlined),
            selectedIcon: Icon(Icons.mosque),
            label: 'حلقاتي',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline_rounded),
            selectedIcon: Icon(Icons.person_rounded),
            label: 'ملفي',
          ),
          NavigationDestination(
            icon: _OutboxNavigationIcon(),
            selectedIcon: _OutboxNavigationIcon(selected: true),
            label: 'المزامنة',
          ),
        ],
      ),
    );
  }
}

class _OutboxNavigationIcon extends ConsumerWidget {
  const _OutboxNavigationIcon({this.selected = false});

  final bool selected;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final count =
        ref
            .watch(outboxProvider)
            .valueOrNull
            ?.where((item) => item.status != 'synced')
            .length ??
        0;
    final studentCount =
        ref
            .watch(studentOperationsProvider)
            .valueOrNull
            ?.where((item) => item.status != 'synced')
            .length ??
        0;
    return Badge(
      isLabelVisible: count + studentCount > 0,
      label: Text('${count + studentCount}'),
      child: Icon(
        selected ? Icons.cloud_done_rounded : Icons.cloud_sync_outlined,
      ),
    );
  }
}

class _HalaqasTab extends ConsumerWidget {
  const _HalaqasTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final halaqas = ref.watch(halaqasProvider);
    return RefreshIndicator(
      onRefresh: () async {
        try {
          await ref.read(syncRepositoryProvider).bootstrap();
          await ref.read(appControllerProvider.notifier).syncNow(silent: true);
        } catch (_) {}
      },
      child: ListView(
        padding: const EdgeInsets.fromLTRB(18, 8, 18, 24),
        children: [
          const _WelcomePanel(),
          const SizedBox(height: 20),
          Text(
            'حلقاتك اليوم',
            style: Theme.of(
              context,
            ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 10),
          halaqas.when(
            loading: () => const Padding(
              padding: EdgeInsets.all(50),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (_, _) => const _EmptyState(
              icon: Icons.cloud_off_rounded,
              title: 'تعذر قراءة بيانات الحلقة',
              subtitle: 'أعد المحاولة عند توفر الاتصال.',
            ),
            data: (items) => items.isEmpty
                ? const _EmptyState(
                    icon: Icons.mosque_outlined,
                    title: 'لا توجد حلقة متاحة',
                    subtitle: 'تأكد من إسناد حلقة فعالة إلى حسابك.',
                  )
                : Column(
                    children: items
                        .map((halaqa) => _HalaqaCard(halaqa: halaqa))
                        .toList(),
                  ),
          ),
        ],
      ),
    );
  }
}

class _WelcomePanel extends ConsumerWidget {
  const _WelcomePanel();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(appControllerProvider);
    final outbox =
        ref.watch(outboxProvider).valueOrNull ?? const <PendingDailyRecord>[];
    final pending = outbox
        .where((item) => ['pending', 'syncing', 'failed'].contains(item.status))
        .length;
    final conflicts = outbox.where((item) => item.status == 'conflict').length;
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(26),
        gradient: const LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [Color(0xFF086B4B), Color(0xFF0E8A61)],
        ),
        boxShadow: const [
          BoxShadow(
            color: Color(0x30086B4B),
            blurRadius: 24,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.auto_stories_rounded,
                color: Color(0xFFFFD75A),
                size: 30,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'مرحبًا ${state.userName}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 19,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          const Text(
            'سجّل حضور الطلاب وتسميعهم حتى دون إنترنت، وسيتولى التطبيق الاعتماد تلقائيًا.',
            style: TextStyle(color: Color(0xFFDDF4E9), height: 1.55),
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _StatusPill(
                icon: Icons.cloud_upload_outlined,
                text: '$pending بانتظار المزامنة',
              ),
              if (conflicts > 0)
                _StatusPill(
                  icon: Icons.warning_amber_rounded,
                  text: '$conflicts تحتاج مراجعة',
                  warning: true,
                ),
              if (pending == 0 && conflicts == 0)
                const _StatusPill(
                  icon: Icons.verified_rounded,
                  text: 'كل البيانات متزامنة',
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({
    required this.icon,
    required this.text,
    this.warning = false,
  });

  final IconData icon;
  final String text;
  final bool warning;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        color: warning
            ? const Color(0xFFFFE7A8)
            : Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 16,
            color: warning ? const Color(0xFF775300) : Colors.white,
          ),
          const SizedBox(width: 6),
          Text(
            text,
            style: TextStyle(
              color: warning ? const Color(0xFF775300) : Colors.white,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _HalaqaCard extends ConsumerWidget {
  const _HalaqaCard({required this.halaqa});

  final CachedHalaqa halaqa;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final students =
        ref.watch(studentsProvider(halaqa.id)).valueOrNull ??
        const <CachedStudent>[];
    final statusRecordDate = ref
        .watch(studentStatusRecordDateProvider)
        .valueOrNull;
    final currentRecordDate = DateTime.now();
    final recorded = students
        .where(
          (student) =>
              student.isRecordedOn(currentRecordDate, statusRecordDate),
        )
        .length;
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(24),
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => HalaqaStudentsScreen(halaqa: halaqa),
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Row(
            children: [
              Container(
                width: 58,
                height: 58,
                decoration: BoxDecoration(
                  color: const Color(0xFFE4F3EB),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: const Icon(
                  Icons.mosque_rounded,
                  color: AppTheme.emerald,
                  size: 30,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      halaqa.name,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      halaqa.centerName,
                      style: const TextStyle(
                        color: Color(0xFF71817A),
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 9),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: LinearProgressIndicator(
                        value: students.isEmpty
                            ? 0
                            : recorded / students.length,
                        minHeight: 6,
                        backgroundColor: const Color(0xFFEAF0EC),
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      '$recorded من ${students.length} تم تسجيلهم اليوم',
                      style: const TextStyle(
                        fontSize: 11,
                        color: Color(0xFF667871),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              const Icon(
                Icons.arrow_back_ios_new_rounded,
                size: 17,
                color: Color(0xFF819088),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _OutboxTab extends ConsumerWidget {
  const _OutboxTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final outbox = ref.watch(outboxProvider);
    final studentOperations = ref.watch(studentOperationsProvider);
    if (outbox.isLoading || studentOperations.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (outbox.hasError || studentOperations.hasError) {
      return const _EmptyState(
        icon: Icons.error_outline_rounded,
        title: 'تعذر عرض طابور المزامنة',
        subtitle: 'البيانات ما زالت محفوظة محليًا.',
      );
    }
    final records = outbox.valueOrNull ?? const <PendingDailyRecord>[];
    final operations =
        studentOperations.valueOrNull ?? const <PendingStudentOperation>[];
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 8, 18, 28),
      children: [
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'مركز المزامنة',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const Text(
                    'تظل العمليات محفوظة على الجهاز حتى يؤكدها الخادم.',
                    style: TextStyle(color: Color(0xFF6E7E77)),
                  ),
                ],
              ),
            ),
            FilledButton.tonalIcon(
              onPressed: () =>
                  ref.read(appControllerProvider.notifier).syncNow(),
              icon: const Icon(Icons.sync_rounded),
              label: const Text('مزامنة'),
            ),
          ],
        ),
        const SizedBox(height: 16),
        if (records.isEmpty && operations.isEmpty)
          const _EmptyState(
            icon: Icons.cloud_done_rounded,
            title: 'لا توجد عمليات معلقة',
            subtitle: 'كل البيانات المحلية متزامنة.',
          )
        else ...[
          ...operations.map(
            (operation) => _StudentOperationCard(operation: operation),
          ),
          ...records.map((record) => _OutboxCard(record: record)),
        ],
      ],
    );
  }
}

class _StudentOperationCard extends ConsumerWidget {
  const _StudentOperationCard({required this.operation});
  final PendingStudentOperation operation;

  Future<void> _dismiss(BuildContext context, WidgetRef ref) async {
    final needsConfirmation =
        operation.status == 'conflict' || operation.status == 'rejected';
    if (needsConfirmation) {
      final confirmed = await showDialog<bool>(
        context: context,
        barrierDismissible: false,
        builder: (dialogContext) => AlertDialog(
          icon: const Icon(
            Icons.warning_amber_rounded,
            color: Color(0xFF9B6500),
          ),
          title: const Text('إلغاء التغيير المحلي؟'),
          content: Text(
            operation.operationType == 'create'
                ? 'سيُحذف ملف الطالب الذي لم يعتمده الخادم من هذا الجهاز. لا يمكن التراجع عن ذلك.'
                : 'سيُزال التغيير المحلي وتبقى نسخة الطالب الرسمية على الخادم دون تعديل.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: const Text('رجوع'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(dialogContext, true),
              child: const Text('إلغاء التغيير'),
            ),
          ],
        ),
      );
      if (confirmed != true || !context.mounted) return;
    }

    try {
      await ref
          .read(databaseProvider)
          .dismissStudentOperation(operation.operationUuid);
      if (needsConfirmation) {
        try {
          await ref.read(syncRepositoryProvider).bootstrap();
        } catch (_) {
          // The official snapshot will refresh automatically when online.
        }
      }
    } on StateError {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'لا يمكن إلغاء الطالب لأن لديه سجلًا يوميًا محليًا. زامن السجل أولًا.',
          ),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final payload = Map<String, dynamic>.from(
      jsonDecode(operation.payloadJson) as Map,
    );
    final name = [
      payload['first_name'],
      payload['father_name'],
      payload['grandfather_name'],
      payload['family_name'],
    ].whereType<String>().where((part) => part.trim().isNotEmpty).join(' ');
    final action = switch (operation.operationType) {
      'create' => 'إضافة طالب',
      'update' => 'تعديل طالب',
      _ => 'أرشفة طالب',
    };
    final status = switch (operation.status) {
      'synced' => 'تم الاعتماد',
      'syncing' => 'جارٍ الإرسال',
      'conflict' => 'تعارض مع نسخة الخادم',
      'rejected' => 'رفض الخادم العملية',
      'failed' => 'تعذر الإرسال وسيعاد لاحقًا',
      _ => 'بانتظار المزامنة',
    };
    final needsResolution = ['conflict', 'rejected'].contains(operation.status);
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: const CircleAvatar(child: Icon(Icons.person_outline_rounded)),
        title: Text(
          '$action${name.isNotEmpty
              ? ' — $name'
              : operation.studentId == null
              ? ''
              : ' — رقم ${operation.studentId}'}',
        ),
        subtitle: Text(
          '$status${operation.lastError == null ? '' : '\n${operation.lastError}'}',
        ),
        isThreeLine: operation.lastError != null,
        trailing: needsResolution || operation.status == 'synced'
            ? IconButton(
                tooltip: needsResolution ? 'إلغاء التغيير المحلي' : 'إخفاء',
                icon: Icon(
                  needsResolution
                      ? Icons.delete_outline_rounded
                      : Icons.close_rounded,
                ),
                onPressed: () => _dismiss(context, ref),
              )
            : operation.status == 'failed'
            ? IconButton(
                tooltip: 'إعادة المحاولة',
                icon: const Icon(Icons.refresh_rounded),
                onPressed: () async {
                  await ref
                      .read(databaseProvider)
                      .retryStudentOperation(operation.operationUuid);
                  await ref.read(appControllerProvider.notifier).syncNow();
                },
              )
            : null,
      ),
    );
  }
}

class _OutboxCard extends ConsumerWidget {
  const _OutboxCard({required this.record});

  final PendingDailyRecord record;

  Future<void> _dismissRecord(BuildContext context, WidgetRef ref) async {
    final requiresConfirmation =
        record.status == 'rejected' || record.status == 'conflict';

    if (requiresConfirmation) {
      final confirmed = await showDialog<bool>(
        context: context,
        barrierDismissible: false,
        builder: (_) => OutboxDeleteConfirmationDialog(status: record.status),
      );

      if (confirmed != true || !context.mounted) {
        return;
      }
    }

    await ref.read(databaseProvider).dismissOutboxRecord(record.operationUuid);

    if (requiresConfirmation && context.mounted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(
          const SnackBar(
            content: Text('تم حذف النسخة المحلية نهائيًا من هذا الجهاز.'),
            behavior: SnackBarBehavior.floating,
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final presentation = switch (record.status) {
      'synced' => (
        Icons.verified_rounded,
        const Color(0xFF167A57),
        'تم الاعتماد',
      ),
      'syncing' => (
        Icons.sync_rounded,
        const Color(0xFF2766A5),
        'جارٍ الإرسال',
      ),
      'conflict' => (
        Icons.warning_amber_rounded,
        const Color(0xFF9B6500),
        'تعارض يحتاج مراجعة',
      ),
      'rejected' => (
        Icons.error_outline_rounded,
        const Color(0xFF9A2D25),
        'مرفوض — صحّح وأعد التسجيل',
      ),
      'failed' => (
        Icons.cloud_off_rounded,
        const Color(0xFF9A2D25),
        'سيعاد الإرسال',
      ),
      _ => (
        Icons.schedule_send_rounded,
        const Color(0xFF6C746F),
        'بانتظار المزامنة',
      ),
    };
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(15),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: presentation.$2.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(presentation.$1, color: presentation.$2),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    presentation.$3,
                    style: TextStyle(
                      color: presentation.$2,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    '${record.studentClientUuid == null ? 'الطالب رقم ${record.studentId}' : 'طالب جديد محفوظ محليًا'} • ${record.recordDate.toLocal().toString().split(' ').first}',
                  ),
                  if (record.lastError != null) ...[
                    const SizedBox(height: 5),
                    Text(
                      record.lastError!,
                      style: const TextStyle(
                        color: Color(0xFF7D3B35),
                        fontSize: 12,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            if (record.status == 'synced' ||
                record.status == 'conflict' ||
                record.status == 'rejected')
              IconButton(
                tooltip: record.status == 'synced'
                    ? 'إخفاء'
                    : 'حذف النسخة المحلية',
                onPressed: () => _dismissRecord(context, ref),
                icon: Icon(
                  record.status == 'synced'
                      ? Icons.close_rounded
                      : Icons.delete_outline_rounded,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class OutboxDeleteConfirmationDialog extends StatelessWidget {
  const OutboxDeleteConfirmationDialog({required this.status, super.key});

  final String status;

  @override
  Widget build(BuildContext context) {
    final rejected = status == 'rejected';

    return AlertDialog(
      icon: Icon(
        rejected ? Icons.error_outline_rounded : Icons.warning_amber_rounded,
        color: rejected ? const Color(0xFF9A2D25) : const Color(0xFF9B6500),
      ),
      title: Text(
        rejected
            ? 'حذف السجل المرفوض من الجهاز؟'
            : 'حذف السجل المتعارض من الجهاز؟',
      ),
      content: Text(
        rejected
            ? 'هذا السجل مرفوض ولم يُعتمد في النظام. حذفه سيزيل النسخة المحلية نهائيًا، ولن يمكن استعادتها من التطبيق.'
            : 'هذا السجل متعارض ويحتاج إلى مراجعة السجل الرسمي. حذفه سيزيل النسخة المحلية نهائيًا، ولن يمكن استعادتها من التطبيق.',
        textAlign: TextAlign.start,
        style: const TextStyle(height: 1.55),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(false),
          child: const Text('إلغاء والاحتفاظ بالسجل'),
        ),
        FilledButton.icon(
          onPressed: () => Navigator.of(context).pop(true),
          style: FilledButton.styleFrom(
            backgroundColor: const Color(0xFF9A2D25),
          ),
          icon: const Icon(Icons.delete_forever_rounded),
          label: const Text('حذف نهائي من الجهاز'),
        ),
      ],
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 42, horizontal: 20),
        child: Column(
          children: [
            Icon(icon, size: 48, color: const Color(0xFF8AA097)),
            const SizedBox(height: 12),
            Text(
              title,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 4),
            Text(
              subtitle,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Color(0xFF71817A)),
            ),
          ],
        ),
      ),
    );
  }
}
