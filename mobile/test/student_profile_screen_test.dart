import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gofran_mobile/app/providers.dart';
import 'package:gofran_mobile/core/database/app_database.dart';
import 'package:gofran_mobile/core/database/profile_snapshots.dart';
import 'package:gofran_mobile/features/students/presentation/student_profile_screen.dart';
import 'package:gofran_mobile/features/students/data/mobile_student.dart';

void main() {
  testWidgets('student profile renders entirely from offline providers', (
    tester,
  ) async {
    const halaqa = CachedHalaqa(
      id: 10,
      name: 'حلقة الإتقان',
      code: 'H-10',
      centerId: 1,
      centerName: 'مركز الغفران',
    );
    const student = CachedStudent(
      id: 21,
      halaqaId: 10,
      studentNumber: 'ST-21',
      fullName: 'الطالب الأول',
      recordedToday: false,
      dailyRecordId: null,
    );
    final profile = StudentProfileSnapshot.fromJson({
      'id': 21,
      'student_number': 'ST-21',
      'full_name': 'الطالب الأول',
      'profile': {
        'status': 'active',
        'status_label': 'فعال',
        'progress': {
          'completed_juz': 3,
          'score': 88,
          'memorization_journey': {
            'has_progress': true,
            'completed_juz': 3,
            'remaining_juz': 27,
            'completed_percentage': 10,
            'frontier_surah_name': 'المجادلة',
            'frontier_ayah_number': 1,
            'encouragement_title': 'ثلاثة أجزاء بإتقان',
          },
        },
        'achievements': [
          {'id': 1, 'title': 'إكمال ثلاثة أجزاء'},
        ],
        'recent_records': const [],
      },
    });

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          cachedStudentProvider(
            student.id,
          ).overrideWith((ref) => Stream.value(student)),
          studentProfileProvider(
            student.id,
          ).overrideWith((ref) => Stream.value(profile)),
          outboxProvider.overrideWith((ref) => Stream.value(const [])),
          studentStatusRecordDateProvider.overrideWith(
            (ref) => Stream.value('2026-09-02'),
          ),
          studentOperationsProvider.overrideWith(
            (ref) => Stream.value(const []),
          ),
          teacherProfileProvider.overrideWith((ref) => Stream.value(null)),
          halaqasProvider.overrideWith((ref) => Stream.value(const [halaqa])),
        ],
        child: MaterialApp(
          home: StudentProfileScreen(
            halaqa: halaqa,
            student: MobileStudent.fromCached(student),
          ),
        ),
      ),
    );
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 100));

    expect(find.text('الطالب الأول'), findsOneWidget);
    expect(find.text('ثلاثة أجزاء بإتقان'), findsOneWidget);
    await tester.scrollUntilVisible(
      find.text('إكمال ثلاثة أجزاء'),
      320,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.pumpAndSettle();
    expect(find.text('إكمال ثلاثة أجزاء'), findsOneWidget);
    expect(find.text('تسجيل الحضور والتسميع اليوم'), findsOneWidget);
  });
}
