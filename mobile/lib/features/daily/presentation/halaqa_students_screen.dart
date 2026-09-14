import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

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

  @override
  Widget build(BuildContext context) {
    final currentRecordDate = DateTime.now();
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
                            currentRecordDate,
                          ),
                        )
                        .toList();
                    final recordedOnCurrentDate =
                        student.recordedToday &&
                        studentStatusRecordDate ==
                            recordDateKey(currentRecordDate);
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
                        if (recordedOnCurrentDate || pending || conflict) {
                          final message = recordedOnCurrentDate
                              ? 'تم اعتماد سجل هذا الطالب اليوم.'
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
    required this.animationIndex,
    required this.onTap,
    required this.onRecord,
  });

  final MobileStudent student;
  final bool recordedOnCurrentDate;
  final bool pending;
  final bool conflict;
  final int animationIndex;
  final VoidCallback onTap;
  final VoidCallback onRecord;

  @override
  Widget build(BuildContext context) {
    final status = recordedOnCurrentDate
        ? (Icons.verified_rounded, const Color(0xFF167A57), 'تم اليوم')
        : conflict
        ? (Icons.warning_amber_rounded, const Color(0xFF9B6500), 'تعارض')
        : pending
        ? (Icons.cloud_upload_outlined, const Color(0xFF2766A5), 'محفوظ محليًا')
        : (Icons.edit_calendar_rounded, AppTheme.emerald, 'تسجيل الآن');
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
