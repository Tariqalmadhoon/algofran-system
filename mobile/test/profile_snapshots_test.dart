import 'package:flutter_test/flutter_test.dart';
import 'package:gofran_mobile/core/database/profile_snapshots.dart';
import 'package:gofran_mobile/features/reports/data/report_export_repository.dart';

void main() {
  test('parses schema v3 teacher and nested student profile snapshots', () {
    final teacher = TeacherProfileSnapshot.fromJson({
      'id': 7,
      'name': 'المحفظ الأول',
      'email': 'teacher@gofran.com',
      'employee_number': 'T-7',
      'specialization': 'التجويد',
      'can_export_reports': true,
      'center': {'id': 1, 'name': 'مركز الغفران'},
    });
    final student = StudentProfileSnapshot.fromJson({
      'id': 21,
      'student_number': 'ST-21',
      'full_name': 'الطالب الأول',
      'profile': {
        'status': 'active',
        'status_label': 'فعال',
        'birth_date': '2014-01-01',
        'progress': {
          'completed_juz': 3,
          'score': 88.5,
          'memorization_journey': {
            'has_progress': true,
            'completed_juz': 3,
            'remaining_juz': 27,
            'completed_percentage': 10,
            'frontier_surah_name': 'المجادلة',
            'frontier_ayah_number': 1,
          },
        },
        'achievements': [
          {'id': 4, 'title': 'إكمال ثلاثة أجزاء', 'type_label': 'إنجاز قرآني'},
        ],
        'recent_records': [
          {
            'id': 8,
            'record_date': '2026-09-01',
            'general_evaluation_label': 'ممتاز',
            'recitations': [
              {
                'type_label': 'حفظ جديد',
                'start': {'surah_name': 'الناس', 'ayah_number': 1},
                'end': {'surah_name': 'الناس', 'ayah_number': 6},
              },
            ],
          },
        ],
      },
    });

    expect(teacher.name, 'المحفظ الأول');
    expect(teacher.centerName, 'مركز الغفران');
    expect(teacher.specialization, 'التجويد');
    expect(teacher.canExportReports, isTrue);
    expect(student.statusLabel, 'فعال');
    expect(student.progress?.completedJuz, 3);
    expect(student.journey?.currentSurahName, 'المجادلة');
    expect(student.achievements.single.title, 'إكمال ثلاثة أجزاء');
    expect(
      student.recentRecords.single.recitations.single.startLabel,
      'سورة الناس · آية 1',
    );
  });

  test('parses report export resource envelope', () {
    final export = MobileReportExport.fromResponse({
      'data': {
        'uuid': 'd243655d-d582-4872-a655-6832edcb4db0',
        'report_type': 'student_comprehensive',
        'status': 'ready',
        'rows_count': 14,
      },
    });

    expect(export.isReady, isTrue);
    expect(export.rowsCount, 14);
    expect(export.reportType, 'student_comprehensive');
  });
}
