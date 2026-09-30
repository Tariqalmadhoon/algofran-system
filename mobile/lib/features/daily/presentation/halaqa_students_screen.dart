import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../app/app_theme.dart';
import '../../../app/providers.dart';
import '../../../core/database/app_database.dart';
import '../../students/presentation/student_profile_screen.dart';
import '../../students/presentation/student_form_screen.dart';
import '../../students/data/mobile_student.dart';
import 'record_daily_screen.dart';

class HalaqaStudentsScreen extends ConsumerStatefulWidget {
  const HalaqaStudentsScreen({super.key, required this.halaqa});

  final CachedHalaqa halaqa;

  @override
  ConsumerState<HalaqaStudentsScreen> createState() =>
      _HalaqaStudentsScreenState();
}

class _HalaqaStudentsScreenState extends ConsumerState<HalaqaStudentsScreen> {
  String _search = '';
  DateTime _recordDate = DateTime.now();

  Future<void> _pickRecordDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _recordDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (picked != null && mounted) {
      setState(() => _recordDate = picked);
    }
  }

  Future<void> _recordTeacherAbsence() async {
    final controller = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => AlertDialog(
        icon: const Icon(Icons.event_busy_rounded, color: Color(0xFF9A2D25)),
        title: const Text('تسجيل غياب المحفّظ'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'سيُوقف تسجيل حفظ جميع طلاب الحلقة بتاريخ ${DateFormat('yyyy/MM/dd').format(_recordDate)}.',
              style: const TextStyle(height: 1.5),
            ),
            const SizedBox(height: 14),
            TextField(
              controller: controller,
              autofocus: true,
              maxLength: 1000,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'سبب الغياب',
                hintText: 'مثال: ظرف صحي أو إجازة معتمدة',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('رجوع'),
          ),
          FilledButton(
            onPressed: () {
              final value = controller.text.trim();
              if (value.isNotEmpty) Navigator.pop(dialogContext, value);
            },
            child: const Text('تأكيد الغياب'),
          ),
        ],
      ),
    );
    controller.dispose();
    if (reason == null || !mounted) return;

    try {
      await ref
          .read(syncRepositoryProvider)
          .queueTeacherAbsence(
            halaqaId: widget.halaqa.id,
            absenceDate: _recordDate,
            reason: reason,
          );
      await ref.read(dailyReminderServiceProvider).refreshSchedule();
      ref.read(appControllerProvider.notifier).syncNow(silent: true);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'تم حفظ غياب المحفّظ على الجهاز، وسيُعتمد تلقائيًا عند توفر الإنترنت.',
          ),
          behavior: SnackBarBehavior.floating,
        ),
      );
    } on StateError {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'يوجد سجل طالب محفوظ لهذا اليوم؛ لا يمكن تسجيل غياب المحفّظ بعده.',
          ),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final students = ref.watch(studentsProvider(widget.halaqa.id));
    final drafts =
        ref.watch(studentDraftsProvider(widget.halaqa.id)).valueOrNull ??
        const <LocalStudentDraft>[];
    final teacher = ref.watch(teacherProfileProvider).valueOrNull;
    final assignedHalaqas =
        ref.watch(halaqasProvider).valueOrNull ?? <CachedHalaqa>[widget.halaqa];
    final studentStatusRecordDate = ref
        .watch(studentStatusRecordDateProvider)
        .valueOrNull;
    final outbox =
        ref.watch(outboxProvider).valueOrNull ?? const <PendingDailyRecord>[];
    final recordKeys =
        ref.watch(dailyRecordKeysProvider(widget.halaqa.id)).valueOrNull ??
        const <CachedDailyRecordKey>[];
    final officialAbsences =
        ref.watch(teacherAbsencesProvider).valueOrNull ??
        const <CachedTeacherAbsence>[];
    final pendingAbsences =
        ref.watch(pendingTeacherAbsencesProvider).valueOrNull ??
        const <PendingTeacherAbsence>[];
    String? absenceReason;
    var absencePending = false;
    for (final absence in officialAbsences) {
      if (absence.halaqaId == widget.halaqa.id &&
          isSameRecordDate(absence.absenceDate, _recordDate)) {
        absenceReason = absence.reason;
        break;
      }
    }
    if (absenceReason == null) {
      for (final absence in pendingAbsences) {
        if (absence.halaqaId == widget.halaqa.id &&
            isSameRecordDate(absence.absenceDate, _recordDate) &&
            [
              'pending',
              'syncing',
              'failed',
              'synced',
            ].contains(absence.status)) {
          absenceReason = absence.reason;
          absencePending = absence.status != 'synced';
          break;
        }
      }
    }
    final teacherAbsent = absenceReason != null;
    final isToday = isSameRecordDate(_recordDate, DateTime.now());
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.halaqa.name),
            Text(
              widget.halaqa.centerName,
              style: const TextStyle(fontSize: 11, color: Color(0xFF6E7E77)),
            ),
          ],
        ),
        actions: [
          if (teacher?.canCreateStudents == true)
            IconButton(
              tooltip: 'إضافة طالب',
              icon: const Icon(Icons.person_add_alt_1_rounded),
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => StudentFormScreen(
                    halaqas: assignedHalaqas,
                    initialHalaqaId: widget.halaqa.id,
                  ),
                ),
              ),
            ),
          const SizedBox(width: 6),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 4, 18, 10),
            child: Card(
              color: teacherAbsent
                  ? const Color(0xFFFFF1F0)
                  : isToday
                  ? const Color(0xFFF0FAF5)
                  : const Color(0xFFFFF8E7),
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        ChoiceChip(
                          label: const Text('اليوم'),
                          selected: isToday,
                          onSelected: (_) =>
                              setState(() => _recordDate = DateTime.now()),
                        ),
                        ChoiceChip(
                          label: const Text('أمس'),
                          selected: isSameRecordDate(
                            _recordDate,
                            DateTime.now().subtract(const Duration(days: 1)),
                          ),
                          onSelected: (_) => setState(
                            () => _recordDate = DateTime.now().subtract(
                              const Duration(days: 1),
                            ),
                          ),
                        ),
                        ActionChip(
                          avatar: const Icon(
                            Icons.calendar_month_rounded,
                            size: 18,
                          ),
                          label: Text(
                            DateFormat('yyyy/MM/dd').format(_recordDate),
                          ),
                          onPressed: _pickRecordDate,
                        ),
                        if (!teacherAbsent)
                          ActionChip(
                            avatar: const Icon(
                              Icons.event_busy_rounded,
                              size: 18,
                            ),
                            label: const Text('غياب المحفّظ'),
                            onPressed: _recordTeacherAbsence,
                          ),
                      ],
                    ),
                    if (!isToday && !teacherAbsent) ...[
                      const SizedBox(height: 9),
                      const Text(
                        'وضع تسجيل سابق: ستُنسب الجلسات إلى هذا التاريخ عند المزامنة.',
                        style: TextStyle(
                          color: Color(0xFF8A6500),
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                    if (teacherAbsent) ...[
                      const SizedBox(height: 9),
                      Text(
                        'المحفّظ غائب — $absenceReason${absencePending ? ' (بانتظار المزامنة)' : ''}',
                        style: const TextStyle(
                          color: Color(0xFF9A2D25),
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 4, 18, 12),
            child: TextField(
              onChanged: (value) => setState(() => _search = value.trim()),
              decoration: const InputDecoration(
                hintText: 'ابحث عن الطالب بالاسم أو الرقم',
                prefixIcon: Icon(Icons.search_rounded),
              ),
            ),
          ),
          Expanded(
            child: students.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (_, _) => const Center(
                child: Text('تعذر قراءة الطلاب المحفوظين على الجهاز.'),
              ),
              data: (items) {
                final mobileStudents = <MobileStudent>[
                  ...items.map(MobileStudent.fromCached),
                  ...drafts.map(MobileStudent.fromDraft),
                ];
                final filtered = mobileStudents.where((student) {
                  if (_search.isEmpty) return true;
                  return student.fullName.contains(_search) ||
                      student.studentNumber.contains(_search);
                }).toList();
                if (filtered.isEmpty) {
                  return const Center(
                    child: Text('لا يوجد طلاب مطابقون للبحث.'),
                  );
                }
                return ListView.builder(
                  padding: const EdgeInsets.fromLTRB(18, 0, 18, 28),
                  itemCount: filtered.length,
                  itemBuilder: (context, index) {
                    final student = filtered[index];
                    final localRecords = outbox
                        .where(
                          (item) => item.belongsToStudentOn(
                            student.serverId,
                            student.clientUuid,
                            _recordDate,
                          ),
                        )
                        .toList();
                    final recordedOnCurrentDate =
                        recordKeys.any(
                          (record) =>
                              record.studentId == student.serverId &&
                              isSameRecordDate(record.recordDate, _recordDate),
                        ) ||
                        (student.recordedToday &&
                            studentStatusRecordDate ==
                                recordDateKey(_recordDate));
                    final pending = localRecords.any(
                      (item) => [
                        'pending',
                        'syncing',
                        'failed',
                      ].contains(item.status),
                    );
                    final conflict = localRecords.any(
                      (item) => item.status == 'conflict',
                    );
                    return _StudentCard(
                      student: student,
                      recordedOnCurrentDate: recordedOnCurrentDate,
                      pending: pending,
                      conflict: conflict,
                      teacherAbsent: teacherAbsent,
                      isToday: isToday,
                      animationIndex: index,
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => StudentProfileScreen(
                              halaqa: widget.halaqa,
                              student: student,
                            ),
                          ),
                        );
                      },
                      onRecord: () {
                        if (teacherAbsent ||
                            recordedOnCurrentDate ||
                            pending ||
                            conflict) {
                          final message = recordedOnCurrentDate
                              ? 'تم اعتماد سجل هذا الطالب في التاريخ المحدد.'
                              : teacherAbsent
                              ? 'تسجيل الطلاب متوقف لأن المحفّظ غائب في هذا التاريخ.'
                              : conflict
                              ? 'يوجد تعارض لهذا الطالب؛ راجعه من مركز المزامنة.'
                              : 'السجل محفوظ على الجهاز وبانتظار المزامنة.';
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(message),
                              behavior: SnackBarBehavior.floating,
                            ),
                          );
                          return;
                        }
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => RecordDailyScreen(
                              halaqa: widget.halaqa,
                              student: student,
                              initialRecordDate: _recordDate,
                            ),
                          ),
                        );
                      },
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _StudentCard extends StatelessWidget {
  const _StudentCard({
    required this.student,
    required this.recordedOnCurrentDate,
    required this.pending,
    required this.conflict,
    required this.teacherAbsent,
    required this.isToday,
    required this.animationIndex,
    required this.onTap,
    required this.onRecord,
  });

  final MobileStudent student;
  final bool recordedOnCurrentDate;
  final bool pending;
  final bool conflict;
  final bool teacherAbsent;
  final bool isToday;
  final int animationIndex;
  final VoidCallback onTap;
  final VoidCallback onRecord;

  @override
  Widget build(BuildContext context) {
    final status = teacherAbsent
        ? (Icons.block_rounded, const Color(0xFF9A2D25), 'متوقف')
        : recordedOnCurrentDate
        ? (
            Icons.verified_rounded,
            const Color(0xFF167A57),
            isToday ? 'تم اليوم' : 'تم التسجيل',
          )
        : conflict
        ? (Icons.warning_amber_rounded, const Color(0xFF9B6500), 'تعارض')
        : pending
        ? (Icons.cloud_upload_outlined, const Color(0xFF2766A5), 'محفوظ محليًا')
        : (
            Icons.edit_calendar_rounded,
            AppTheme.emerald,
            isToday ? 'تسجيل الآن' : 'تسجيل سابق',
          );
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: Duration(milliseconds: 280 + (animationIndex.clamp(0, 8) * 45)),
      curve: Curves.easeOutCubic,
      builder: (context, value, child) => Opacity(
        opacity: value,
        child: Transform.translate(
          offset: Offset(0, 12 * (1 - value)),
          child: child,
        ),
      ),
      child: Card(
        margin: const EdgeInsets.only(bottom: 10),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(24),
          child: Padding(
            padding: const EdgeInsets.all(15),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 25,
                  backgroundColor: const Color(0xFFE3F2EA),
                  child: Text(
                    student.fullName.trim().isEmpty
                        ? 'ط'
                        : student.fullName.trim()[0],
                    style: const TextStyle(
                      color: AppTheme.emerald,
                      fontSize: 19,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        student.fullName,
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      Text(
                        student.studentNumber,
                        style: const TextStyle(
                          fontSize: 11,
                          color: Color(0xFF71817A),
                        ),
                      ),
                      if (student.isLocalDraft)
                        const Text(
                          'طالب جديد محفوظ على الجهاز',
                          style: TextStyle(
                            fontSize: 10,
                            color: Color(0xFF2766A5),
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                    ],
                  ),
                ),
                InkWell(
                  onTap: onRecord,
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 9,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: status.$2.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Row(
                      children: [
                        Icon(status.$1, size: 16, color: status.$2),
                        const SizedBox(width: 5),
                        Text(
                          status.$3,
                          style: TextStyle(
                            fontSize: 11,
                            color: status.$2,
                            fontWeight: FontWeight.w800,
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
      ),
    );
  }
}
