import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/app_theme.dart';
import '../../../app/providers.dart';
import '../../../core/database/app_database.dart';
import '../../../core/database/profile_snapshots.dart';
import '../../../core/network/api_client.dart';
import '../../daily/presentation/record_daily_screen.dart';
import '../data/mobile_student.dart';
import 'student_form_screen.dart';

class StudentProfileScreen extends ConsumerWidget {
  const StudentProfileScreen({
    super.key,
    required this.halaqa,
    required this.student,
  });

  final CachedHalaqa halaqa;
  final MobileStudent student;

  Future<void> _archive(
    BuildContext context,
    WidgetRef ref,
    MobileStudent current,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        icon: const Icon(Icons.person_off_outlined, color: Color(0xFF9A2D25)),
        title: const Text('أرشفة الطالب؟'),
        content: Text(
          current.isLocalDraft
              ? 'سيُلغى الطالب المحلي قبل اعتماده. لا يمكن ذلك إذا سُجّل له تسميع غير متزامن.'
              : 'سيختفي الطالب من الحلقة، وستُرسل عملية الأرشفة عند توفر الإنترنت. السجلات السابقة لن تُحذف.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('إلغاء'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFF9A2D25),
            ),
            child: const Text('تأكيد الأرشفة'),
          ),
        ],
      ),
    );
    if (confirmed != true || !context.mounted) return;
    try {
      final repository = ref.read(studentManagementRepositoryProvider);
      if (current.isLocalDraft) {
        await repository.discardDraft(current.clientUuid!);
      } else {
        final baseUpdatedAt = current.profile?.updatedAt;
        if (baseUpdatedAt == null) {
          throw const ApiFailure('زامن بيانات الطالب قبل أرشفته.');
        }
        await repository.archiveServer(
          studentId: current.serverId!,
          halaqaId: current.halaqaId,
          baseUpdatedAt: baseUpdatedAt,
        );
      }
      await ref.read(appControllerProvider.notifier).syncNow(silent: true);
      if (context.mounted) Navigator.of(context).pop();
    } catch (error) {
      if (!context.mounted) return;
      final message =
          error is StateError &&
              error.message == 'local_student_has_daily_records'
          ? 'لا يمكن إلغاء الطالب لأن لديه سجلًا يوميًا غير متزامن.'
          : error is ApiFailure
          ? error.message
          : 'تعذر حفظ عملية الأرشفة.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message), behavior: SnackBarBehavior.floating),
      );
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    var currentStudent = student;
    if (student.serverId != null) {
      final cached = ref
          .watch(cachedStudentProvider(student.serverId!))
          .valueOrNull;
      if (cached != null) currentStudent = MobileStudent.fromCached(cached);
    } else if (student.clientUuid != null) {
      final draft = ref
          .watch(studentDraftProvider(student.clientUuid!))
          .valueOrNull;
      if (draft != null) currentStudent = MobileStudent.fromDraft(draft);
    }
    final AsyncValue<StudentProfileSnapshot?> snapshot =
        student.serverId != null
        ? ref.watch(studentProfileProvider(student.serverId!))
        : AsyncData(currentStudent.profile);
    currentStudent = currentStudent.withProfile(
      snapshot.valueOrNull ?? currentStudent.profile,
    );
    final teacher = ref.watch(teacherProfileProvider).valueOrNull;
    final halaqas =
        ref.watch(halaqasProvider).valueOrNull ?? const <CachedHalaqa>[];
    final studentOperations =
        ref.watch(studentOperationsProvider).valueOrNull ??
        const <PendingStudentOperation>[];
    final hasPendingStudentOperation = studentOperations.any(
      (operation) =>
          operation.status != 'synced' &&
          !(currentStudent.isLocalDraft &&
              operation.operationType == 'create' &&
              operation.status != 'syncing') &&
          ((currentStudent.serverId != null &&
                  operation.studentId == currentStudent.serverId) ||
              (currentStudent.clientUuid != null &&
                  operation.clientStudentUuid == currentStudent.clientUuid)),
    );
    final outbox =
        ref.watch(outboxProvider).valueOrNull ?? const <PendingDailyRecord>[];
    final statusDate = ref.watch(studentStatusRecordDateProvider).valueOrNull;
    final today = DateTime.now();
    final local = outbox
        .where(
          (record) => record.belongsToStudentOn(
            currentStudent.serverId,
            currentStudent.clientUuid,
            today,
          ),
        )
        .toList(growable: false);
    final recorded =
        currentStudent.recordedToday && statusDate == recordDateKey(today);
    final pending = local.any(
      (record) => ['pending', 'syncing', 'failed'].contains(record.status),
    );
    final conflict = local.any((record) => record.status == 'conflict');

    return Scaffold(
      appBar: AppBar(
        title: const Text('ملف الطالب'),
        actions: [
          if (teacher?.canUpdateStudents == true)
            IconButton(
              tooltip: hasPendingStudentOperation
                  ? 'بانتظار تسوية العملية السابقة'
                  : 'تعديل الطالب',
              onPressed: hasPendingStudentOperation
                  ? null
                  : () => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => StudentFormScreen(
                          halaqas: halaqas,
                          initialHalaqaId: currentStudent.halaqaId,
                          student: currentStudent,
                        ),
                      ),
                    ),
              icon: const Icon(Icons.edit_outlined),
            ),
          if (teacher?.canArchiveStudents == true)
            IconButton(
              tooltip: hasPendingStudentOperation
                  ? 'بانتظار تسوية العملية السابقة'
                  : 'أرشفة الطالب',
              onPressed: hasPendingStudentOperation
                  ? null
                  : () => _archive(context, ref, currentStudent),
              icon: const Icon(Icons.person_off_outlined),
            ),
          const SizedBox(width: 5),
        ],
      ),
      body: snapshot.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, _) => _ProfileBody(
          student: currentStudent,
          halaqa: halaqa,
          profile: null,
          recorded: recorded,
          pending: pending,
          conflict: conflict,
          localRecord: local.isEmpty ? null : local.first,
        ),
        data: (profile) => _ProfileBody(
          student: currentStudent,
          halaqa: halaqa,
          profile: profile,
          recorded: recorded,
          pending: pending,
          conflict: conflict,
          localRecord: local.isEmpty ? null : local.first,
        ),
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(18, 8, 18, 14),
        child: FilledButton.icon(
          onPressed: recorded || pending || conflict
              ? null
              : () => Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => RecordDailyScreen(
                      halaqa: halaqa,
                      student: currentStudent,
                    ),
                  ),
                ),
          icon: Icon(
            recorded
                ? Icons.verified_rounded
                : pending
                ? Icons.cloud_upload_outlined
                : conflict
                ? Icons.warning_amber_rounded
                : Icons.edit_calendar_rounded,
          ),
          label: Text(
            recorded
                ? 'تم اعتماد سجل اليوم'
                : pending
                ? 'السجل محفوظ وبانتظار المزامنة'
                : conflict
                ? 'راجع التعارض من مركز المزامنة'
                : 'تسجيل الحضور والتسميع اليوم',
          ),
        ),
      ),
    );
  }
}

class _ProfileBody extends StatelessWidget {
  const _ProfileBody({
    required this.student,
    required this.halaqa,
    required this.profile,
    required this.recorded,
    required this.pending,
    required this.conflict,
    required this.localRecord,
  });

  final MobileStudent student;
  final CachedHalaqa halaqa;
  final StudentProfileSnapshot? profile;
  final bool recorded;
  final bool pending;
  final bool conflict;
  final PendingDailyRecord? localRecord;

  @override
  Widget build(BuildContext context) {
    final name = profile?.fullName.isNotEmpty == true
        ? profile!.fullName
        : student.fullName;
    final progress = profile?.progress;
    final journey = profile?.journey;
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 6, 18, 28),
      children: [
        _IdentityCard(
          name: name,
          studentNumber: student.studentNumber,
          halaqaName: halaqa.name,
          statusLabel: profile?.statusLabel,
          recorded: recorded,
          pending: pending,
          conflict: conflict,
        ),
        const SizedBox(height: 14),
        if (journey != null) _JourneyCard(journey: journey),
        if (journey != null) const SizedBox(height: 14),
        if (progress != null) ...[
          _SectionTitle(
            icon: Icons.insights_rounded,
            title: 'ملخص التقدم',
            subtitle: progress.asOfDate == null
                ? null
                : 'آخر تحديث ${progress.asOfDate}',
          ),
          const SizedBox(height: 9),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 9,
            crossAxisSpacing: 9,
            childAspectRatio: 1.55,
            children: [
              _MetricCard(
                icon: Icons.menu_book_rounded,
                label: 'الأجزاء المكتملة',
                value: '${progress.completedJuz}',
              ),
              _MetricCard(
                icon: Icons.auto_stories_rounded,
                label: 'السور المكتملة',
                value: '${progress.completedSurahs}',
              ),
              _MetricCard(
                icon: Icons.stars_rounded,
                label: 'مؤشر الأداء',
                value: progress.score == null
                    ? '—'
                    : progress.score!.toStringAsFixed(0),
              ),
              _MetricCard(
                icon: Icons.how_to_reg_rounded,
                label: 'نسبة الحضور',
                value: progress.attendanceRate == null
                    ? '—'
                    : '${progress.attendanceRate!.toStringAsFixed(0)}%',
              ),
            ],
          ),
          const SizedBox(height: 18),
        ],
        _SectionTitle(
          icon: Icons.emoji_events_rounded,
          title: 'الإنجازات',
          subtitle: profile?.achievements.isNotEmpty == true
              ? '${profile!.achievements.length} إنجازات محفوظة على الجهاز'
              : null,
        ),
        const SizedBox(height: 9),
        if (profile == null || profile!.achievements.isEmpty)
          const _EmptyCard(
            icon: Icons.workspace_premium_outlined,
            text:
                'لا توجد إنجازات محفوظة بعد. حدّث البيانات عند توفر الإنترنت.',
          )
        else
          ...profile!.achievements.map(
            (achievement) => _AchievementCard(achievement: achievement),
          ),
        const SizedBox(height: 18),
        const _SectionTitle(
          icon: Icons.history_rounded,
          title: 'آخر السجلات',
          subtitle: 'نسخة محلية متاحة دون إنترنت',
        ),
        const SizedBox(height: 9),
        if (localRecord != null) ...[
          _PendingLocalRecordCard(record: localRecord!),
          const SizedBox(height: 8),
        ],
        if (profile == null || profile!.recentRecords.isEmpty)
          const _EmptyCard(
            icon: Icons.event_note_outlined,
            text: 'لا توجد سجلات سابقة محفوظة على هذا الجهاز.',
          )
        else
          ...profile!.recentRecords.map(
            (record) => _RecordCard(record: record),
          ),
        const SizedBox(height: 12),
        if (profile != null) _BasicInfoCard(profile: profile!),
      ],
    );
  }
}

class _PendingLocalRecordCard extends StatelessWidget {
  const _PendingLocalRecordCard({required this.record});

  final PendingDailyRecord record;

  @override
  Widget build(BuildContext context) {
    Map<String, dynamic> payload = const {};
    try {
      payload = Map<String, dynamic>.from(
        jsonDecode(record.payloadJson) as Map,
      );
    } catch (_) {}
    final attendance = _attendanceLabel(
      payload['attendance_status']?.toString(),
    );
    final evaluation = _evaluationLabel(
      payload['general_evaluation']?.toString(),
    );
    final items = payload['items'] is List
        ? List<dynamic>.from(payload['items'] as List)
        : const <dynamic>[];
    final status = switch (record.status) {
      'conflict' => 'يحتاج حل تعارض',
      'failed' => 'بانتظار إعادة المحاولة',
      'syncing' => 'يجري رفعه الآن',
      _ => 'محفوظ على الجهاز',
    };
    return Card(
      color: const Color(0xFFEAF4FF),
      child: Padding(
        padding: const EdgeInsets.all(15),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.offline_pin_rounded, color: Color(0xFF2766A5)),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'سجل اليوم المحلي · $status',
                    style: const TextStyle(
                      color: Color(0xFF174F86),
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 9),
            Text('الحضور: $attendance · التقييم: $evaluation'),
            const SizedBox(height: 5),
            Text(
              items.isEmpty
                  ? 'لا توجد بنود تسميع في هذا السجل.'
                  : '${items.length} بنود تسميع: ${items.map((raw) {
                      final item = raw is Map ? raw : const <String, dynamic>{};
                      return _recitationTypeLabel(item['type']?.toString());
                    }).join('، ')}',
              style: const TextStyle(fontSize: 12, color: Color(0xFF456681)),
            ),
          ],
        ),
      ),
    );
  }

  static String _attendanceLabel(String? value) => switch (value) {
    'present' => 'حاضر',
    'absent' => 'غائب',
    'excused' => 'غياب بعذر',
    'late' => 'متأخر',
    _ => 'غير محدد',
  };

  static String _evaluationLabel(String? value) => switch (value) {
    'poor' => 'ضعيف',
    'good' => 'جيد',
    'very_good' => 'جيد جدًا',
    'excellent' => 'ممتاز',
    _ => 'غير محدد',
  };

  static String _recitationTypeLabel(String? value) => switch (value) {
    'new_memorization' => 'حفظ جديد',
    'recent_revision' => 'مراجعة قريبة',
    'old_revision' => 'مراجعة قديمة',
    'recitation' => 'سرد',
    'exam' => 'اختبار',
    'tajweed' => 'تجويد',
    _ => 'تسميع',
  };
}

class _IdentityCard extends StatelessWidget {
  const _IdentityCard({
    required this.name,
    required this.studentNumber,
    required this.halaqaName,
    required this.statusLabel,
    required this.recorded,
    required this.pending,
    required this.conflict,
  });

  final String name;
  final String studentNumber;
  final String halaqaName;
  final String? statusLabel;
  final bool recorded;
  final bool pending;
  final bool conflict;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [Color(0xFF086B4B), Color(0xFF0E8A61)],
        ),
        borderRadius: BorderRadius.circular(28),
        boxShadow: const [
          BoxShadow(
            color: Color(0x30086B4B),
            blurRadius: 22,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 34,
            backgroundColor: Colors.white.withValues(alpha: .16),
            child: Text(
              name.trim().isEmpty ? 'ط' : name.trim()[0],
              style: const TextStyle(
                color: Colors.white,
                fontSize: 26,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 19,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  '$studentNumber · $halaqaName',
                  style: const TextStyle(color: Color(0xFFDDF4E9)),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 7,
                  runSpacing: 7,
                  children: [
                    if (statusLabel != null) _WhitePill(text: statusLabel!),
                    _WhitePill(
                      text: recorded
                          ? 'تم اليوم'
                          : pending
                          ? 'محفوظ محليًا'
                          : conflict
                          ? 'يوجد تعارض'
                          : 'بانتظار التسجيل',
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _JourneyCard extends StatelessWidget {
  const _JourneyCard({required this.journey});

  final MemorizationJourneySnapshot journey;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: const Color(0xFFFFFAE7),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(
                  Icons.auto_awesome_rounded,
                  color: Color(0xFFB27A00),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    journey.encouragementTitle ?? 'رحلة مباركة مع القرآن',
                    style: const TextStyle(
                      color: Color(0xFF704D00),
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                Text(
                  '${journey.completedJuz}/30',
                  style: const TextStyle(
                    color: AppTheme.emerald,
                    fontSize: 19,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 11),
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: LinearProgressIndicator(
                value: (journey.completedPercentage / 100).clamp(0, 1),
                minHeight: 9,
                backgroundColor: const Color(0xFFFFEDB4),
              ),
            ),
            if (journey.currentSurahName != null) ...[
              const SizedBox(height: 10),
              Text(
                'المحطة الحالية: سورة ${journey.currentSurahName}'
                '${journey.currentAyahNumber == null ? '' : ' · آية ${journey.currentAyahNumber}'}',
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ],
            if (journey.encouragementMessage != null) ...[
              const SizedBox(height: 7),
              Text(
                journey.encouragementMessage!,
                style: const TextStyle(color: Color(0xFF796A42), height: 1.5),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.icon, required this.title, this.subtitle});

  final IconData icon;
  final String title;
  final String? subtitle;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Container(
        width: 42,
        height: 42,
        decoration: BoxDecoration(
          color: const Color(0xFFE4F3EB),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Icon(icon, color: AppTheme.emerald),
      ),
      const SizedBox(width: 10),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
            ),
            if (subtitle != null)
              Text(
                subtitle!,
                style: const TextStyle(fontSize: 11, color: Color(0xFF71817A)),
              ),
          ],
        ),
      ),
    ],
  );
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Card(
    margin: EdgeInsets.zero,
    child: Padding(
      padding: const EdgeInsets.all(13),
      child: Row(
        children: [
          Icon(icon, color: AppTheme.emerald, size: 23),
          const SizedBox(width: 9),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 19,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 10,
                    color: Color(0xFF71817A),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    ),
  );
}

class _AchievementCard extends StatelessWidget {
  const _AchievementCard({required this.achievement});

  final AchievementSnapshot achievement;

  @override
  Widget build(BuildContext context) => Card(
    margin: const EdgeInsets.only(bottom: 8),
    child: ListTile(
      leading: const CircleAvatar(
        backgroundColor: Color(0xFFFFF0BE),
        child: Icon(Icons.workspace_premium_rounded, color: Color(0xFF9B6500)),
      ),
      title: Text(
        achievement.title,
        style: const TextStyle(fontWeight: FontWeight.w800),
      ),
      subtitle: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            [
              achievement.typeLabel,
              achievement.achievedAt,
            ].whereType<String>().join(' · '),
          ),
          if (achievement.description != null)
            Text(
              achievement.description!,
              style: const TextStyle(height: 1.45),
            ),
        ],
      ),
    ),
  );
}

class _RecordCard extends StatelessWidget {
  const _RecordCard({required this.record});

  final RecentDailyRecordSnapshot record;

  @override
  Widget build(BuildContext context) => Card(
    margin: const EdgeInsets.only(bottom: 8),
    child: Padding(
      padding: const EdgeInsets.all(15),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.event_available_rounded,
                color: AppTheme.emerald,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  record.recordDate ?? 'سجل سابق',
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
              if (record.evaluationLabel != null)
                Text(
                  record.evaluationLabel!,
                  style: const TextStyle(
                    color: AppTheme.emerald,
                    fontWeight: FontWeight.w800,
                  ),
                ),
            ],
          ),
          if (record.attendanceLabel != null) ...[
            const SizedBox(height: 7),
            Text('الحضور: ${record.attendanceLabel}'),
          ],
          ...record.recitations.map(
            (item) => Padding(
              padding: const EdgeInsets.only(top: 6),
              child: Text(
                [
                  item.typeLabel,
                  item.startLabel,
                  item.endLabel,
                  item.evaluationLabel,
                ].whereType<String>().join(' · '),
                style: const TextStyle(fontSize: 12, color: Color(0xFF64756E)),
              ),
            ),
          ),
        ],
      ),
    ),
  );
}

class _BasicInfoCard extends StatelessWidget {
  const _BasicInfoCard({required this.profile});

  final StudentProfileSnapshot profile;

  @override
  Widget build(BuildContext context) {
    final rows = <(String, String?)>[
      ('تاريخ الميلاد', profile.birthDate),
      ('تاريخ التسجيل', profile.registrationDate),
      ('رقم التواصل', profile.contactPhone),
    ].where((row) => row.$2 != null).toList();
    if (rows.isEmpty) return const SizedBox.shrink();
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(17),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'البيانات الأساسية',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 10),
            ...rows.map(
              (row) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 5),
                child: Row(
                  children: [
                    Expanded(child: Text(row.$1)),
                    Text(
                      row.$2!,
                      style: const TextStyle(fontWeight: FontWeight.w700),
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
}

class _EmptyCard extends StatelessWidget {
  const _EmptyCard({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsets.all(22),
      child: Row(
        children: [
          Icon(icon, color: const Color(0xFF8B9A93)),
          const SizedBox(width: 10),
          Expanded(
            child: Text(text, style: const TextStyle(color: Color(0xFF71817A))),
          ),
        ],
      ),
    ),
  );
}

class _WhitePill extends StatelessWidget {
  const _WhitePill({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
    decoration: BoxDecoration(
      color: Colors.white.withValues(alpha: .14),
      borderRadius: BorderRadius.circular(14),
    ),
    child: Text(
      text,
      style: const TextStyle(color: Colors.white, fontSize: 11),
    ),
  );
}
