import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gofran_mobile/core/database/app_database.dart';

void main() {
  late AppDatabase database;

  setUp(() {
    database = AppDatabase.forTesting(NativeDatabase.memory());
  });

  tearDown(() async {
    await database.close();
  });

  test(
    'bootstrap cache and offline outbox preserve a daily record until server acceptance',
    () async {
      await database.replaceBootstrap({
        'server_time': '2026-08-30T08:00:00Z',
        'sync_cursor': 'cursor-1',
        'teacher': {'id': 7, 'name': 'المحفظ الأول'},
        'halaqas': [
          {
            'id': 10,
            'name': 'حلقة الإتقان',
            'code': 'H-10',
            'center': {'id': 1, 'name': 'مركز الغفران'},
            'students': [
              {
                'id': 21,
                'student_number': 'ST-21',
                'full_name': 'الطالب الأول',
                'recorded_today': false,
                'daily_record_id': null,
              },
            ],
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

      expect(await database.setting('sync_cursor'), 'cursor-1');
      expect(await database.allSurahs(), hasLength(1));
      expect(await database.allAyahs(), hasLength(2));

      await database.queueDailyRecord(
        operationUuid: '09fe6b56-b905-47ac-997a-dd856fa01a64',
        studentId: 21,
        halaqaId: 10,
        recordDate: DateTime(2026, 8, 30),
        payload: {
          'student_id': 21,
          'halaqa_id': 10,
          'record_date': '2026-08-30',
          'attendance_status': 'present',
          'items': const [],
        },
      );

      expect(await database.recordsToSync(), hasLength(1));
      await database.markSending(['09fe6b56-b905-47ac-997a-dd856fa01a64']);
      expect((await database.watchOutbox().first).single.status, 'syncing');
      expect(await database.recordsToSync(), hasLength(1));

      await database.applySyncResult('09fe6b56-b905-47ac-997a-dd856fa01a64', {
        'operation_uuid': '09fe6b56-b905-47ac-997a-dd856fa01a64',
        'status': 'accepted',
        'record': {
          'id': 88,
          'student': {'id': 21},
        },
      });

      final outbox = await database.watchOutbox().first;
      final students = await database.watchStudents(10).first;
      expect(outbox.single.status, 'synced');
      expect(outbox.single.serverRecordId, 88);
      expect(students.single.recordedToday, isTrue);
      expect(students.single.dailyRecordId, 88);
      expect(await database.recordsToSync(), isEmpty);
    },
  );

  test(
    'schema v3 caches profiles while a bootstrap refresh preserves outbox',
    () async {
      await database.queueDailyRecord(
        operationUuid: '4cad2261-bdb5-4f39-9400-4c767a51acd2',
        studentId: 21,
        halaqaId: 10,
        recordDate: DateTime(2026, 9, 2),
        payload: {'student_id': 21},
      );
      await database.replaceBootstrap({
        'server_time': '2026-09-02T08:00:00Z',
        'sync_cursor': 'profile-cursor',
        'teacher': {
          'id': 7,
          'name': 'المحفظ الأول',
          'can_export_reports': true,
        },
        'halaqas': [
          {
            'id': 10,
            'name': 'حلقة الإتقان',
            'code': 'H-10',
            'center': {'id': 1, 'name': 'مركز الغفران'},
            'students': [
              {
                'id': 21,
                'student_number': 'ST-21',
                'full_name': 'الطالب الأول',
                'recorded_today': false,
                'profile': {
                  'status': 'active',
                  'progress': {'completed_juz': 3},
                  'achievements': const [],
                  'recent_records': const [],
                },
              },
            ],
          },
        ],
        'quran': {'surahs': const [], 'ayahs': const []},
      });

      expect(
        (await database.watchTeacherProfile().first)?.canExportReports,
        isTrue,
      );
      expect(
        (await database.watchStudentProfile(21).first)?.progress?.completedJuz,
        3,
      );
      expect(await database.hasUnresolvedOutbox(), isTrue);
      expect(
        (await database.watchOutbox().first).single.operationUuid,
        '4cad2261-bdb5-4f39-9400-4c767a51acd2',
      );
    },
  );

  test(
    'accepted offline student create binds its pending daily record',
    () async {
      const clientUuid = '19f6fd1b-7c65-46be-a92e-f79ce4454c1e';
      const createUuid = '8ef0aa38-dbf6-4420-aa27-acd137b62314';
      const dailyUuid = 'd52a0356-f5e4-41d2-95eb-84fe5fa620d7';
      final student = <String, dynamic>{
        'client_uuid': clientUuid,
        'halaqa_id': 10,
        'first_name': 'أحمد',
        'father_name': 'محمد',
        'grandfather_name': 'علي',
        'family_name': 'الغفران',
        'registration_date': '2026-09-03',
      };
      await database.queueStudentCreate(
        operationUuid: createUuid,
        clientUuid: clientUuid,
        halaqaId: 10,
        student: student,
      );
      await database.queueDailyRecord(
        operationUuid: dailyUuid,
        studentClientUuid: clientUuid,
        halaqaId: 10,
        recordDate: DateTime(2026, 9, 3),
        payload: {
          'student_client_uuid': clientUuid,
          'halaqa_id': 10,
          'record_date': '2026-09-03',
        },
      );

      expect(await database.recordsToSync(), isEmpty);
      expect(await database.watchStudentDrafts(10).first, hasLength(1));

      await database.applyStudentSyncResult(createUuid, {
        'status': 'accepted',
        'student': {
          'id': 77,
          'student_number': 'STD-00077',
          'full_name': 'أحمد محمد علي الغفران',
          'halaqa': {'id': 10, 'name': 'حلقة الإتقان'},
          'updated_at': '2026-09-03T09:00:00Z',
        },
      });

      expect(await database.watchStudentDrafts(10).first, isEmpty);
      final daily = (await database.watchOutbox().first).single;
      expect(daily.studentId, 77);
      expect(daily.studentClientUuid, isNull);
      expect(daily.payloadJson, contains('"student_id":77'));
      expect(daily.payloadJson, isNot(contains('student_client_uuid')));
      expect(await database.recordsToSync(), hasLength(1));
      final cached = (await database.watchStudents(10).first).single;
      expect(cached.id, 77);
      expect(cached.studentNumber, 'STD-00077');
    },
  );

  test(
    'failed network attempt remains retryable while conflict is not pushed again',
    () async {
      Future<void> queue(String uuid) => database.queueDailyRecord(
        operationUuid: uuid,
        studentId: 1,
        halaqaId: 1,
        recordDate: DateTime(2026, 8, 30),
        payload: {'student_id': 1},
      );

      await queue('f1ba35da-13ce-492d-8fe5-92364e166b38');
      await database.markSending(['f1ba35da-13ce-492d-8fe5-92364e166b38']);
      await database.returnSendingToPending('لا يوجد اتصال');
      expect(await database.recordsToSync(), hasLength(1));

      await database.applySyncResult('f1ba35da-13ce-492d-8fe5-92364e166b38', {
        'operation_uuid': 'f1ba35da-13ce-492d-8fe5-92364e166b38',
        'status': 'conflict',
        'error': {'message': 'يوجد سجل رسمي'},
      });
      expect(await database.recordsToSync(), isEmpty);
      expect((await database.watchOutbox().first).single.status, 'conflict');
    },
  );

  test(
    'rejected records require review and are never retried automatically',
    () async {
      await database.queueDailyRecord(
        operationUuid: 'f08a7ee4-862e-4cd7-88bb-93d782d10afd',
        studentId: 7,
        halaqaId: 3,
        recordDate: DateTime(2026, 8, 30),
        payload: {'student_id': 7},
      );

      await database.applySyncResult('f08a7ee4-862e-4cd7-88bb-93d782d10afd', {
        'operation_uuid': 'f08a7ee4-862e-4cd7-88bb-93d782d10afd',
        'status': 'rejected',
        'error': {'message': 'الطالب خارج نطاق الحلقة'},
      });

      final rejected = (await database.watchOutbox().first).single;
      expect(rejected.status, 'rejected');
      expect(await database.recordsToSync(), isEmpty);
      expect(await database.hasUnresolvedOutbox(), isTrue);
    },
  );

  test(
    'a delayed older record never overwrites the current day and a new day resets student status',
    () async {
      await database.replaceBootstrap({
        'record_date': '2026-08-31',
        'server_time': '2026-08-31T08:00:00Z',
        'sync_cursor': 'cursor-date-boundary',
        'teacher': {'id': 7, 'name': 'المحفظ الأول'},
        'halaqas': [
          {
            'id': 10,
            'name': 'حلقة الإتقان',
            'code': 'H-10',
            'center': {'id': 1, 'name': 'مركز الغفران'},
            'students': [
              {
                'id': 21,
                'student_number': 'ST-21',
                'full_name': 'الطالب الأول',
                'recorded_today': true,
                'daily_record_id': 91,
              },
              {
                'id': 22,
                'student_number': 'ST-22',
                'full_name': 'الطالب الثاني',
                'recorded_today': false,
                'daily_record_id': null,
              },
            ],
          },
        ],
        'quran': {'surahs': const [], 'ayahs': const []},
      });

      await database.queueDailyRecord(
        operationUuid: '43839768-3f7d-47d6-95ea-dc1002042230',
        studentId: 22,
        halaqaId: 10,
        recordDate: DateTime(2026, 8, 30),
        payload: {'student_id': 22, 'record_date': '2026-08-30'},
      );
      final olderRecord = (await database.watchOutbox().first).single;
      expect(
        olderRecord.belongsToStudentOn(22, null, DateTime(2026, 8, 31)),
        false,
      );

      await database.applySyncResult('43839768-3f7d-47d6-95ea-dc1002042230', {
        'status': 'accepted',
        'record': {'id': 92},
      });
      var students = await database.watchStudents(10).first;
      expect(
        students.singleWhere((student) => student.id == 21).recordedToday,
        true,
      );
      expect(
        students.singleWhere((student) => student.id == 22).recordedToday,
        false,
      );
      expect(
        await database.setting(studentStatusRecordDateSetting),
        '2026-08-31',
      );

      await database.queueDailyRecord(
        operationUuid: '82f78062-4706-4f95-b4e1-b81d81188cb0',
        studentId: 22,
        halaqaId: 10,
        recordDate: DateTime(2026, 9, 1),
        payload: {'student_id': 22, 'record_date': '2026-09-01'},
      );
      await database.applySyncResult('82f78062-4706-4f95-b4e1-b81d81188cb0', {
        'status': 'accepted',
        'record': {'id': 93},
      });

      students = await database.watchStudents(10).first;
      expect(
        students.singleWhere((student) => student.id == 21).recordedToday,
        false,
      );
      expect(
        students.singleWhere((student) => student.id == 22).recordedToday,
        true,
      );
      expect(
        await database.setting(studentStatusRecordDateSetting),
        '2026-09-01',
      );
    },
  );
}
