import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gofran_mobile/app/providers.dart';
import 'package:gofran_mobile/core/database/app_database.dart';
import 'package:gofran_mobile/features/daily/presentation/record_daily_screen.dart';
import 'package:gofran_mobile/features/students/data/mobile_student.dart';

void main() {
  late AppDatabase database;

  setUp(() async {
    database = AppDatabase.forTesting(NativeDatabase.memory());
    await database.replaceBootstrap({
      'server_time': '2026-09-09T08:00:00Z',
      'sync_cursor': 'attendance-ui',
      'teacher': {'id': 7, 'name': 'المحفظ الأول'},
      'halaqas': [
        {
          'id': 10,
          'name': 'حلقة الإتقان',
          'code': 'H-10',
          'center': {'id': 1, 'name': 'مركز الغفران'},
          'students': const [],
        },
      ],
      'quran': {
        'surahs': [
          {'id': 114, 'name_arabic': 'الناس', 'verses_count': 6},
        ],
        'ayahs': [
          {
            'id': 6231,
            'surah_id': 114,
            'ayah_number': 1,
            'global_order': 6231,
            'juz': 30,
          },
          {
            'id': 6236,
            'surah_id': 114,
            'ayah_number': 6,
            'global_order': 6236,
            'juz': 30,
          },
        ],
      },
    });
  });

  tearDown(() async {
    await database.close();
  });

  testWidgets('الغياب بعذر يخفي التسميع والتقييم', (tester) async {
    const halaqa = CachedHalaqa(
      id: 10,
      name: 'حلقة الإتقان',
      code: 'H-10',
      centerId: 1,
      centerName: 'مركز الغفران',
    );
    const student = MobileStudent(
      serverId: 21,
      halaqaId: 10,
      studentNumber: 'ST-21',
      fullName: 'الطالب الأول',
    );

    await tester.pumpWidget(
      ProviderScope(
        overrides: [databaseProvider.overrideWithValue(database)],
        child: const MaterialApp(
          home: Directionality(
            textDirection: TextDirection.rtl,
            child: RecordDailyScreen(halaqa: halaqa, student: student),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('التقييم العام'), findsOneWidget);

    await tester.tap(find.byType(DropdownButtonFormField<String>).first);
    await tester.pumpAndSettle();
    await tester.tap(find.text('بعذر').last);
    await tester.pumpAndSettle();

    const absenceMessage =
        'الطالب غائب بعذر؛ سيُحفظ الحضور دون تسميع أو تقييم.';
    await tester.scrollUntilVisible(
      find.text(absenceMessage),
      260,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.pumpAndSettle();
    expect(find.text(absenceMessage), findsOneWidget);
    expect(find.text('إضافة بند تسميع آخر'), findsNothing);
    expect(find.text('التقييم العام'), findsNothing);
  });
}
