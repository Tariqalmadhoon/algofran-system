import 'dart:convert';

import 'package:drift/drift.dart';
import 'package:drift_flutter/drift_flutter.dart';

import 'profile_snapshots.dart';

part 'app_database.g.dart';

const studentStatusRecordDateSetting = 'student_status_record_date';
const quranAyahsSetting = 'quran_ayahs_compact_v1';

String recordDateKey(DateTime date) {
  final year = date.year.toString().padLeft(4, '0');
  final month = date.month.toString().padLeft(2, '0');
  final day = date.day.toString().padLeft(2, '0');
  return '$year-$month-$day';
}

bool isSameRecordDate(DateTime first, DateTime second) =>
    recordDateKey(first) == recordDateKey(second);

extension CachedStudentRecordStatus on CachedStudent {
  bool isRecordedOn(DateTime date, String? statusRecordDate) =>
      recordedToday && statusRecordDate == recordDateKey(date);
}

extension PendingDailyRecordDate on PendingDailyRecord {
  bool belongsToStudentOn(
    int? targetStudentId,
    String? targetClientUuid,
    DateTime date,
  ) =>
      ((targetStudentId != null && studentId == targetStudentId) ||
          (targetClientUuid != null &&
              studentClientUuid == targetClientUuid)) &&
      isSameRecordDate(recordDate, date);
}

class AppSettings extends Table {
  TextColumn get key => text()();
  TextColumn get value => text().nullable()();

  @override
  Set<Column<Object>> get primaryKey => {key};
}

class CachedHalaqas extends Table {
  IntColumn get id => integer()();
  TextColumn get name => text()();
  TextColumn get code => text().nullable()();
  IntColumn get centerId => integer()();
  TextColumn get centerName => text()();

  @override
  Set<Column<Object>> get primaryKey => {id};
}

class CachedStudents extends Table {
  IntColumn get id => integer()();
  IntColumn get halaqaId => integer()();
  TextColumn get studentNumber => text()();
  TextColumn get fullName => text()();
  BoolColumn get recordedToday =>
      boolean().withDefault(const Constant(false))();
  IntColumn get dailyRecordId => integer().nullable()();

  @override
  Set<Column<Object>> get primaryKey => {id};
}

class CachedSurahs extends Table {
  IntColumn get id => integer()();
  TextColumn get nameArabic => text()();
  IntColumn get versesCount => integer()();

  @override
  Set<Column<Object>> get primaryKey => {id};
}

class CachedAyahs extends Table {
  IntColumn get id => integer()();
  IntColumn get surahId => integer()();
  IntColumn get ayahNumber => integer()();
  IntColumn get globalOrder => integer()();
  IntColumn get juz => integer().nullable()();

  @override
  Set<Column<Object>> get primaryKey => {id};
}

class PendingDailyRecords extends Table {
  TextColumn get operationUuid => text()();
  IntColumn get studentId => integer()();
  TextColumn get studentClientUuid => text().nullable()();
  IntColumn get halaqaId => integer()();
  DateTimeColumn get recordDate => dateTime()();
  TextColumn get payloadJson => text()();
  TextColumn get status => text().withDefault(const Constant('pending'))();
  IntColumn get attempts => integer().withDefault(const Constant(0))();
  TextColumn get lastError => text().nullable()();
  IntColumn get serverRecordId => integer().nullable()();
  DateTimeColumn get clientCreatedAt => dateTime()();
  DateTimeColumn get updatedAt => dateTime()();

  @override
  Set<Column<Object>> get primaryKey => {operationUuid};
}

class CachedTeacherProfiles extends Table {
  IntColumn get id => integer()();
  TextColumn get payloadJson => text()();
  DateTimeColumn get updatedAt => dateTime()();

  @override
  Set<Column<Object>> get primaryKey => {id};
}

class CachedStudentProfiles extends Table {
  IntColumn get studentId => integer()();
  TextColumn get payloadJson => text()();
  DateTimeColumn get updatedAt => dateTime()();

  @override
  Set<Column<Object>> get primaryKey => {studentId};
}

class PendingStudentOperations extends Table {
  TextColumn get operationUuid => text()();
  TextColumn get operationType => text()();
  IntColumn get studentId => integer().nullable()();
  TextColumn get clientStudentUuid => text().nullable()();
  IntColumn get halaqaId => integer()();
  TextColumn get payloadJson => text()();
  TextColumn get status => text().withDefault(const Constant('pending'))();
  IntColumn get attempts => integer().withDefault(const Constant(0))();
  TextColumn get lastError => text().nullable()();
  IntColumn get serverStudentId => integer().nullable()();
  DateTimeColumn get clientCreatedAt => dateTime()();
  DateTimeColumn get updatedAt => dateTime()();

  @override
  Set<Column<Object>> get primaryKey => {operationUuid};
}

class LocalStudentDrafts extends Table {
  TextColumn get clientUuid => text()();
  IntColumn get halaqaId => integer()();
  TextColumn get payloadJson => text()();
  DateTimeColumn get createdAt => dateTime()();
  DateTimeColumn get updatedAt => dateTime()();

  @override
  Set<Column<Object>> get primaryKey => {clientUuid};
}

@DriftDatabase(
  tables: [
    AppSettings,
    CachedHalaqas,
    CachedStudents,
    CachedSurahs,
    CachedAyahs,
    PendingDailyRecords,
    CachedTeacherProfiles,
    CachedStudentProfiles,
    PendingStudentOperations,
    LocalStudentDrafts,
  ],
)
class AppDatabase extends _$AppDatabase {
  AppDatabase() : super(driftDatabase(name: 'gofran_mobile'));

  AppDatabase.forTesting(super.executor);

  @override
  int get schemaVersion => 3;

  @override
  MigrationStrategy get migration => MigrationStrategy(
    onCreate: (migrator) => migrator.createAll(),
    onUpgrade: (migrator, from, to) async {
      if (from < 2) {
        await migrator.createTable(cachedTeacherProfiles);
        await migrator.createTable(cachedStudentProfiles);
      }
      if (from < 3) {
        await migrator.addColumn(
          pendingDailyRecords,
          pendingDailyRecords.studentClientUuid,
        );
        await migrator.createTable(pendingStudentOperations);
        await migrator.createTable(localStudentDrafts);
      }
    },
  );

  Future<void> replaceBootstrap(Map<String, dynamic> data) async {
    await transaction(() async {
      final statusRecordDate = _recordDateFromBootstrap(data);
      await delete(cachedStudents).go();
      await delete(cachedHalaqas).go();
      await delete(cachedAyahs).go();
      await delete(cachedSurahs).go();
      await delete(cachedStudentProfiles).go();
      await delete(cachedTeacherProfiles).go();

      final updatedAt = DateTime.now().toUtc();
      final teacherPayload = data['teacher'];
      if (teacherPayload is Map) {
        final teacher = Map<String, dynamic>.from(teacherPayload);
        await into(cachedTeacherProfiles).insert(
          CachedTeacherProfilesCompanion.insert(
            id: Value((teacher['id'] as num?)?.toInt() ?? 0),
            payloadJson: jsonEncode(teacher),
            updatedAt: updatedAt,
          ),
        );
      }

      final halaqas = (data['halaqas'] as List<dynamic>? ?? const []);
      for (final rawHalaqa in halaqas) {
        final halaqa = Map<String, dynamic>.from(rawHalaqa as Map);
        final center = Map<String, dynamic>.from(halaqa['center'] as Map);
        await into(cachedHalaqas).insert(
          CachedHalaqasCompanion.insert(
            id: Value(halaqa['id'] as int),
            name: halaqa['name'] as String,
            code: Value(halaqa['code'] as String?),
            centerId: center['id'] as int,
            centerName: center['name'] as String? ?? 'مركز الغفران',
          ),
        );

        for (final rawStudent
            in halaqa['students'] as List<dynamic>? ?? const []) {
          final student = Map<String, dynamic>.from(rawStudent as Map);
          await into(cachedStudents).insert(
            CachedStudentsCompanion.insert(
              id: Value(student['id'] as int),
              halaqaId: halaqa['id'] as int,
              studentNumber: student['student_number'] as String,
              fullName: student['full_name'] as String,
              recordedToday: Value(
                statusRecordDate != null &&
                    (student['recorded_today'] as bool? ?? false),
              ),
              dailyRecordId: Value(student['daily_record_id'] as int?),
            ),
          );
          await into(cachedStudentProfiles).insert(
            CachedStudentProfilesCompanion.insert(
              studentId: Value(student['id'] as int),
              payloadJson: jsonEncode(student),
              updatedAt: updatedAt,
            ),
          );
        }
      }

      final quran = Map<String, dynamic>.from(data['quran'] as Map);
      final surahRows = (quran['surahs'] as List<dynamic>? ?? const [])
          .map((rawSurah) {
            final surah = Map<String, dynamic>.from(rawSurah as Map);
            return CachedSurahsCompanion.insert(
              id: Value(surah['id'] as int),
              nameArabic: surah['name_arabic'] as String,
              versesCount: surah['verses_count'] as int,
            );
          })
          .toList(growable: false);
      final compactAyahs = (quran['ayahs'] as List<dynamic>? ?? const [])
          .map((rawAyah) {
            final ayah = Map<String, dynamic>.from(rawAyah as Map);
            return [
              ayah['id'] as int,
              ayah['surah_id'] as int,
              ayah['ayah_number'] as int,
              ayah['global_order'] as int,
              ayah['juz'] as int?,
            ];
          })
          .toList(growable: false);
      await batch((writeBatch) {
        if (surahRows.isNotEmpty) {
          writeBatch.insertAll(cachedSurahs, surahRows);
        }
      });
      await setSetting(quranAyahsSetting, jsonEncode(compactAyahs));

      await setSetting('sync_cursor', data['sync_cursor'] as String?);
      await setSetting(studentStatusRecordDateSetting, statusRecordDate);
      await setSetting(
        'teacher_name',
        (data['teacher'] as Map?)?['name'] as String?,
      );
      await setSetting('last_bootstrap_at', data['server_time'] as String?);
      await _applyPendingStudentOperationsOverlay();
    });
  }

  Stream<List<CachedHalaqa>> watchHalaqas() {
    return (select(
      cachedHalaqas,
    )..orderBy([(row) => OrderingTerm.asc(row.name)])).watch();
  }

  Stream<List<CachedStudent>> watchStudents(int halaqaId) {
    return (select(cachedStudents)
          ..where((row) => row.halaqaId.equals(halaqaId))
          ..orderBy([(row) => OrderingTerm.asc(row.fullName)]))
        .watch();
  }

  Stream<CachedStudent?> watchStudent(int studentId) {
    return (select(
      cachedStudents,
    )..where((row) => row.id.equals(studentId))).watchSingleOrNull();
  }

  Stream<TeacherProfileSnapshot?> watchTeacherProfile() {
    return (select(cachedTeacherProfiles)..limit(1)).watchSingleOrNull().map(
      (row) => row == null
          ? null
          : TeacherProfileSnapshot.fromEncoded(row.payloadJson),
    );
  }

  Stream<StudentProfileSnapshot?> watchStudentProfile(int studentId) {
    return (select(
      cachedStudentProfiles,
    )..where((row) => row.studentId.equals(studentId))).watchSingleOrNull().map(
      (row) => row == null
          ? null
          : StudentProfileSnapshot.fromEncoded(row.payloadJson),
    );
  }

  Stream<String?> watchStudentStatusRecordDate() {
    return (select(appSettings)
          ..where((row) => row.key.equals(studentStatusRecordDateSetting)))
        .watchSingleOrNull()
        .map((row) => row?.value);
  }

  Stream<List<PendingDailyRecord>> watchOutbox() {
    return (select(
      pendingDailyRecords,
    )..orderBy([(row) => OrderingTerm.desc(row.clientCreatedAt)])).watch();
  }

  Stream<List<PendingStudentOperation>> watchStudentOperations() {
    return (select(
      pendingStudentOperations,
    )..orderBy([(row) => OrderingTerm.desc(row.clientCreatedAt)])).watch();
  }

  Future<List<PendingStudentOperation>> studentOperationsToSync({
    int limit = 50,
  }) {
    return (select(pendingStudentOperations)
          ..where((row) => row.status.isIn(['pending', 'syncing', 'failed']))
          ..orderBy([(row) => OrderingTerm.asc(row.clientCreatedAt)])
          ..limit(limit))
        .get();
  }

  Stream<List<LocalStudentDraft>> watchStudentDrafts(int halaqaId) {
    return (select(localStudentDrafts)
          ..where((row) => row.halaqaId.equals(halaqaId))
          ..orderBy([(row) => OrderingTerm.asc(row.createdAt)]))
        .watch();
  }

  Stream<LocalStudentDraft?> watchStudentDraft(String clientUuid) {
    return (select(
      localStudentDrafts,
    )..where((row) => row.clientUuid.equals(clientUuid))).watchSingleOrNull();
  }

  Future<void> queueStudentCreate({
    required String operationUuid,
    required String clientUuid,
    required int halaqaId,
    required Map<String, dynamic> student,
  }) async {
    final now = DateTime.now().toUtc();
    await transaction(() async {
      await into(localStudentDrafts).insert(
        LocalStudentDraftsCompanion.insert(
          clientUuid: clientUuid,
          halaqaId: halaqaId,
          payloadJson: jsonEncode(student),
          createdAt: now,
          updatedAt: now,
        ),
      );
      await into(pendingStudentOperations).insert(
        PendingStudentOperationsCompanion.insert(
          operationUuid: operationUuid,
          operationType: 'create',
          clientStudentUuid: Value(clientUuid),
          halaqaId: halaqaId,
          payloadJson: jsonEncode(student),
          clientCreatedAt: now,
          updatedAt: now,
        ),
      );
    });
  }

  Future<void> queueStudentUpdate({
    required String operationUuid,
    required int studentId,
    required int halaqaId,
    required Map<String, dynamic> student,
  }) async {
    final now = DateTime.now().toUtc();
    await transaction(() async {
      final cached = await (select(
        cachedStudents,
      )..where((row) => row.id.equals(studentId))).getSingleOrNull();
      if (cached == null) throw StateError('student_not_cached');
      final unresolved =
          await (select(pendingStudentOperations)
                ..where(
                  (row) =>
                      row.studentId.equals(studentId) &
                      row.status.isNotIn(['synced']),
                )
                ..limit(1))
              .getSingleOrNull();
      if (unresolved != null) {
        if (unresolved.operationType != 'update' ||
            !['pending', 'failed'].contains(unresolved.status)) {
          throw StateError('student_operation_in_progress');
        }
        final original = Map<String, dynamic>.from(
          jsonDecode(unresolved.payloadJson) as Map,
        );
        final baseUpdatedAt = original['base_updated_at'];
        original.addAll(student);
        original['base_updated_at'] = baseUpdatedAt;
        await _upsertServerStudent(
          id: studentId,
          halaqaId: halaqaId,
          student: original,
        );
        await (update(pendingStudentOperations)..where(
              (row) => row.operationUuid.equals(unresolved.operationUuid),
            ))
            .write(
              PendingStudentOperationsCompanion(
                payloadJson: Value(jsonEncode(original)),
                status: const Value('pending'),
                lastError: const Value(null),
                updatedAt: Value(now),
              ),
            );
        return;
      }
      await _upsertServerStudent(
        id: studentId,
        halaqaId: halaqaId,
        student: student,
      );
      await into(pendingStudentOperations).insert(
        PendingStudentOperationsCompanion.insert(
          operationUuid: operationUuid,
          operationType: 'update',
          studentId: Value(studentId),
          halaqaId: halaqaId,
          payloadJson: jsonEncode(student),
          clientCreatedAt: now,
          updatedAt: now,
        ),
      );
    });
  }

  Future<void> updateLocalStudentDraft({
    required String clientUuid,
    required int halaqaId,
    required Map<String, dynamic> student,
  }) async {
    final now = DateTime.now().toUtc();
    await transaction(() async {
      final draft = await (select(
        localStudentDrafts,
      )..where((row) => row.clientUuid.equals(clientUuid))).getSingleOrNull();
      if (draft == null) throw StateError('student_draft_not_found');
      final create =
          await (select(pendingStudentOperations)..where(
                (row) =>
                    row.clientStudentUuid.equals(clientUuid) &
                    row.operationType.equals('create'),
              ))
              .getSingleOrNull();
      if (create == null) throw StateError('student_create_not_queued');
      final merged = Map<String, dynamic>.from(
        jsonDecode(create.payloadJson) as Map,
      )..addAll(student);
      await (update(
        localStudentDrafts,
      )..where((row) => row.clientUuid.equals(clientUuid))).write(
        LocalStudentDraftsCompanion(
          halaqaId: Value(halaqaId),
          payloadJson: Value(jsonEncode(merged)),
          updatedAt: Value(now),
        ),
      );
      await (update(
        pendingStudentOperations,
      )..where((row) => row.operationUuid.equals(create.operationUuid))).write(
        PendingStudentOperationsCompanion(
          halaqaId: Value(halaqaId),
          payloadJson: Value(jsonEncode(merged)),
          status: const Value('pending'),
          lastError: const Value(null),
          updatedAt: Value(now),
        ),
      );
    });
  }

  Future<void> queueStudentArchive({
    required String operationUuid,
    required int studentId,
    required int halaqaId,
    required Map<String, dynamic> student,
  }) async {
    final now = DateTime.now().toUtc();
    await transaction(() async {
      final cached = await (select(
        cachedStudents,
      )..where((row) => row.id.equals(studentId))).getSingleOrNull();
      if (cached == null) throw StateError('student_not_cached');
      final pendingDaily =
          await (select(pendingDailyRecords)
                ..where(
                  (row) =>
                      row.studentId.equals(studentId) &
                      row.status.isNotIn(['synced']),
                )
                ..limit(1))
              .getSingleOrNull();
      if (pendingDaily != null) throw StateError('student_has_daily_records');
      final unresolved =
          await (select(pendingStudentOperations)
                ..where(
                  (row) =>
                      row.studentId.equals(studentId) &
                      row.status.isNotIn(['synced']),
                )
                ..limit(1))
              .getSingleOrNull();
      if (unresolved != null) throw StateError('student_operation_in_progress');
      await into(pendingStudentOperations).insert(
        PendingStudentOperationsCompanion.insert(
          operationUuid: operationUuid,
          operationType: 'archive',
          studentId: Value(studentId),
          halaqaId: halaqaId,
          payloadJson: jsonEncode(student),
          clientCreatedAt: now,
          updatedAt: now,
        ),
      );
      await (delete(
        cachedStudentProfiles,
      )..where((row) => row.studentId.equals(studentId))).go();
      await (delete(
        cachedStudents,
      )..where((row) => row.id.equals(studentId))).go();
    });
  }

  Future<void> discardLocalStudentDraft(String clientUuid) async {
    await transaction(() async {
      final pendingDaily =
          await (select(pendingDailyRecords)
                ..where(
                  (row) =>
                      row.studentClientUuid.equals(clientUuid) &
                      row.status.isNotIn(['synced']),
                )
                ..limit(1))
              .getSingleOrNull();
      if (pendingDaily != null) {
        throw StateError('local_student_has_daily_records');
      }
      await (delete(
        pendingStudentOperations,
      )..where((row) => row.clientStudentUuid.equals(clientUuid))).go();
      await (delete(
        localStudentDrafts,
      )..where((row) => row.clientUuid.equals(clientUuid))).go();
    });
  }

  Future<void> retryStudentOperation(String operationUuid) async {
    await (update(
      pendingStudentOperations,
    )..where((row) => row.operationUuid.equals(operationUuid))).write(
      PendingStudentOperationsCompanion(
        status: const Value('pending'),
        lastError: const Value(null),
        updatedAt: Value(DateTime.now().toUtc()),
      ),
    );
  }

  Future<void> dismissStudentOperation(String operationUuid) async {
    final operation =
        await (select(pendingStudentOperations)
              ..where((row) => row.operationUuid.equals(operationUuid)))
            .getSingleOrNull();
    if (operation == null) return;
    if (operation.operationType == 'create' &&
        operation.clientStudentUuid != null) {
      await discardLocalStudentDraft(operation.clientStudentUuid!);
      return;
    }
    await (delete(
      pendingStudentOperations,
    )..where((row) => row.operationUuid.equals(operationUuid))).go();
  }

  Future<List<CachedSurah>> allSurahs() {
    return (select(
      cachedSurahs,
    )..orderBy([(row) => OrderingTerm.asc(row.id)])).get();
  }

  Future<List<CachedAyah>> allAyahs() async {
    final compact = await setting(quranAyahsSetting);
    if (compact != null) {
      try {
        final rows = jsonDecode(compact) as List<dynamic>;
        return rows
            .map((rawRow) {
              final row = rawRow as List<dynamic>;
              return CachedAyah(
                id: row[0] as int,
                surahId: row[1] as int,
                ayahNumber: row[2] as int,
                globalOrder: row[3] as int,
                juz: row[4] as int?,
              );
            })
            .toList(growable: false);
      } on FormatException {
        // Older/corrupted cache falls back to the normalized table safely.
      } on TypeError {
        // Older/corrupted cache falls back to the normalized table safely.
      }
    }

    return (select(
      cachedAyahs,
    )..orderBy([(row) => OrderingTerm.asc(row.globalOrder)])).get();
  }

  Future<void> queueDailyRecord({
    required String operationUuid,
    int? studentId,
    String? studentClientUuid,
    required int halaqaId,
    required DateTime recordDate,
    required Map<String, dynamic> payload,
  }) async {
    final now = DateTime.now().toUtc();
    await into(pendingDailyRecords).insert(
      PendingDailyRecordsCompanion.insert(
        operationUuid: operationUuid,
        studentId: studentId ?? 0,
        studentClientUuid: Value(studentClientUuid),
        halaqaId: halaqaId,
        recordDate: recordDate,
        payloadJson: jsonEncode(payload),
        clientCreatedAt: now,
        updatedAt: now,
      ),
    );
  }

  Future<List<PendingDailyRecord>> recordsToSync({int limit = 50}) {
    return (select(pendingDailyRecords)
          ..where(
            (row) =>
                row.status.isIn(['pending', 'syncing', 'failed']) &
                row.studentClientUuid.isNull(),
          )
          ..orderBy([(row) => OrderingTerm.asc(row.clientCreatedAt)])
          ..limit(limit))
        .get();
  }

  Future<void> markSending(List<String> operationUuids) async {
    if (operationUuids.isEmpty) return;
    await (update(
      pendingDailyRecords,
    )..where((row) => row.operationUuid.isIn(operationUuids))).write(
      PendingDailyRecordsCompanion(
        status: const Value('syncing'),
        attempts: Value.absent(),
        lastError: const Value(null),
        updatedAt: Value(DateTime.now().toUtc()),
      ),
    );
    for (final uuid in operationUuids) {
      final row = await (select(
        pendingDailyRecords,
      )..where((item) => item.operationUuid.equals(uuid))).getSingle();
      await (update(
        pendingDailyRecords,
      )..where((item) => item.operationUuid.equals(uuid))).write(
        PendingDailyRecordsCompanion(attempts: Value(row.attempts + 1)),
      );
    }
  }

  Future<void> markStudentOperationsSending(List<String> operationUuids) async {
    if (operationUuids.isEmpty) return;
    final now = DateTime.now().toUtc();
    for (final uuid in operationUuids) {
      final row = await (select(
        pendingStudentOperations,
      )..where((item) => item.operationUuid.equals(uuid))).getSingle();
      await (update(
        pendingStudentOperations,
      )..where((item) => item.operationUuid.equals(uuid))).write(
        PendingStudentOperationsCompanion(
          status: const Value('syncing'),
          attempts: Value(row.attempts + 1),
          lastError: const Value(null),
          updatedAt: Value(now),
        ),
      );
    }
  }

  Future<void> applyStudentSyncResult(
    String operationUuid,
    Map<String, dynamic> result,
  ) async {
    final queued =
        await (select(pendingStudentOperations)
              ..where((row) => row.operationUuid.equals(operationUuid)))
            .getSingleOrNull();
    if (queued == null) return;
    final status = result['status'] as String? ?? 'failed';
    final student = result['student'] is Map
        ? Map<String, dynamic>.from(result['student'] as Map)
        : result['server_student'] is Map
        ? Map<String, dynamic>.from(result['server_student'] as Map)
        : null;
    final error = result['error'] is Map
        ? Map<String, dynamic>.from(result['error'] as Map)
        : const <String, dynamic>{};
    final localStatus = switch (status) {
      'accepted' || 'already_processed' => 'synced',
      'conflict' => 'conflict',
      'rejected' => 'rejected',
      _ => 'failed',
    };
    await transaction(() async {
      if (localStatus == 'synced' &&
          queued.operationType == 'create' &&
          student != null &&
          student['id'] is num &&
          queued.clientStudentUuid != null) {
        await _bindStudentDraft(
          clientUuid: queued.clientStudentUuid!,
          serverId: (student['id'] as num).toInt(),
          halaqaId: _studentHalaqaId(student) ?? queued.halaqaId,
          student: student,
        );
      } else if (localStatus == 'synced' &&
          queued.operationType == 'update' &&
          student != null &&
          queued.studentId != null) {
        await _upsertServerStudent(
          id: queued.studentId!,
          halaqaId: _studentHalaqaId(student) ?? queued.halaqaId,
          student: student,
        );
      }
      await (update(
        pendingStudentOperations,
      )..where((row) => row.operationUuid.equals(operationUuid))).write(
        PendingStudentOperationsCompanion(
          status: Value(localStatus),
          serverStudentId: Value(
            student?['id'] is num ? (student!['id'] as num).toInt() : null,
          ),
          lastError: Value(error['message']?.toString()),
          updatedAt: Value(DateTime.now().toUtc()),
        ),
      );
    });
  }

  Future<void> returnStudentOperationsToPending(String message) async {
    await (update(
      pendingStudentOperations,
    )..where((row) => row.status.equals('syncing'))).write(
      PendingStudentOperationsCompanion(
        status: const Value('failed'),
        lastError: Value(message),
        updatedAt: Value(DateTime.now().toUtc()),
      ),
    );
  }

  Future<void> applySyncResult(
    String operationUuid,
    Map<String, dynamic> result,
  ) async {
    final status = result['status'] as String? ?? 'failed';
    final record = (result['record'] ?? result['server_record']) as Map?;
    final error = result['error'] as Map?;
    final localStatus = switch (status) {
      'accepted' || 'already_processed' => 'synced',
      'conflict' => 'conflict',
      'rejected' => 'rejected',
      _ => 'failed',
    };
    await (update(
      pendingDailyRecords,
    )..where((row) => row.operationUuid.equals(operationUuid))).write(
      PendingDailyRecordsCompanion(
        status: Value(localStatus),
        serverRecordId: Value(record?['id'] as int?),
        lastError: Value(error?['message'] as String?),
        updatedAt: Value(DateTime.now().toUtc()),
      ),
    );

    if (localStatus == 'synced') {
      final queued = await (select(
        pendingDailyRecords,
      )..where((row) => row.operationUuid.equals(operationUuid))).getSingle();
      await markStudentRecorded(
        queued.studentId,
        record?['id'] as int?,
        queued.recordDate,
      );
    }
  }

  Future<void> returnSendingToPending(String message) async {
    await (update(
      pendingDailyRecords,
    )..where((row) => row.status.equals('syncing'))).write(
      PendingDailyRecordsCompanion(
        status: const Value('failed'),
        lastError: Value(message),
        updatedAt: Value(DateTime.now().toUtc()),
      ),
    );
  }

  Future<void> markStudentRecorded(
    int studentId,
    int? recordId,
    DateTime recordDate,
  ) async {
    final targetDate = recordDateKey(recordDate);
    await transaction(() async {
      final cachedDate = await setting(studentStatusRecordDateSetting);

      // A delayed synchronization for an older day must never replace the
      // newer day represented by the student cache.
      if (cachedDate != null && cachedDate.compareTo(targetDate) > 0) return;

      if (cachedDate != targetDate) {
        await update(cachedStudents).write(
          const CachedStudentsCompanion(
            recordedToday: Value(false),
            dailyRecordId: Value(null),
          ),
        );
        await setSetting(studentStatusRecordDateSetting, targetDate);
      }

      await (update(
        cachedStudents,
      )..where((row) => row.id.equals(studentId))).write(
        CachedStudentsCompanion(
          recordedToday: const Value(true),
          dailyRecordId: Value(recordId),
        ),
      );
    });
  }

  Future<void> dismissOutboxRecord(String operationUuid) {
    return (delete(
      pendingDailyRecords,
    )..where((row) => row.operationUuid.equals(operationUuid))).go();
  }

  Future<bool> hasUnresolvedOutbox() async {
    final record =
        await (select(pendingDailyRecords)
              ..where((row) => row.status.isNotIn(['synced']))
              ..limit(1))
            .getSingleOrNull();

    if (record != null) return true;
    final studentOperation =
        await (select(pendingStudentOperations)
              ..where((row) => row.status.isNotIn(['synced']))
              ..limit(1))
            .getSingleOrNull();
    return studentOperation != null;
  }

  Future<String?> setting(String key) async {
    return (select(appSettings)..where((row) => row.key.equals(key)))
        .getSingleOrNull()
        .then((row) => row?.value);
  }

  Future<void> setSetting(String key, String? value) {
    return into(appSettings).insertOnConflictUpdate(
      AppSettingsCompanion.insert(key: key, value: Value(value)),
    );
  }

  Future<void> clearSessionData() async {
    await transaction(() async {
      await delete(pendingDailyRecords).go();
      await delete(cachedStudents).go();
      await delete(cachedHalaqas).go();
      await delete(cachedAyahs).go();
      await delete(cachedSurahs).go();
      await delete(cachedStudentProfiles).go();
      await delete(cachedTeacherProfiles).go();
      await delete(pendingStudentOperations).go();
      await delete(localStudentDrafts).go();
      await delete(appSettings).go();
    });
  }

  Future<void> _applyPendingStudentOperationsOverlay() async {
    final operations =
        await (select(pendingStudentOperations)
              ..where(
                (row) => row.status.isIn(['pending', 'syncing', 'failed']),
              )
              ..orderBy([(row) => OrderingTerm.asc(row.clientCreatedAt)]))
            .get();
    for (final operation in operations) {
      final student = Map<String, dynamic>.from(
        jsonDecode(operation.payloadJson) as Map,
      );
      if (operation.operationType == 'archive') {
        if (operation.studentId != null) {
          await (delete(
            cachedStudentProfiles,
          )..where((row) => row.studentId.equals(operation.studentId!))).go();
          await (delete(
            cachedStudents,
          )..where((row) => row.id.equals(operation.studentId!))).go();
        }
      } else if (operation.operationType == 'update' &&
          operation.studentId != null) {
        await _upsertServerStudent(
          id: operation.studentId!,
          halaqaId: operation.halaqaId,
          student: student,
        );
      }
    }
  }

  Future<void> _upsertServerStudent({
    required int id,
    required int halaqaId,
    required Map<String, dynamic> student,
  }) async {
    final existing = await (select(
      cachedStudents,
    )..where((row) => row.id.equals(id))).getSingleOrNull();
    final existingProfile = await (select(
      cachedStudentProfiles,
    )..where((row) => row.studentId.equals(id))).getSingleOrNull();
    final existingEnvelope = existingProfile == null
        ? <String, dynamic>{}
        : Map<String, dynamic>.from(
            jsonDecode(existingProfile.payloadJson) as Map,
          );
    final oldProfile = existingEnvelope['profile'] is Map
        ? Map<String, dynamic>.from(existingEnvelope['profile'] as Map)
        : <String, dynamic>{};
    final incomingProfile = student['profile'] is Map
        ? Map<String, dynamic>.from(student['profile'] as Map)
        : Map<String, dynamic>.from(student);
    final profilePayload = <String, dynamic>{...oldProfile, ...incomingProfile};
    final fullName = _studentFullName(student, existing?.fullName);
    final studentNumber =
        student['student_number']?.toString() ??
        existing?.studentNumber ??
        'قيد الاعتماد';
    await into(cachedStudents).insertOnConflictUpdate(
      CachedStudentsCompanion.insert(
        id: Value(id),
        halaqaId: halaqaId,
        studentNumber: studentNumber,
        fullName: fullName,
        recordedToday: Value(existing?.recordedToday ?? false),
        dailyRecordId: Value(existing?.dailyRecordId),
      ),
    );
    final envelope = <String, dynamic>{
      'id': id,
      'student_number': studentNumber,
      'full_name': fullName,
      'updated_at': student['updated_at'],
      'profile': profilePayload,
    };
    await into(cachedStudentProfiles).insertOnConflictUpdate(
      CachedStudentProfilesCompanion.insert(
        studentId: Value(id),
        payloadJson: jsonEncode(envelope),
        updatedAt: DateTime.now().toUtc(),
      ),
    );
  }

  int? _studentHalaqaId(Map<String, dynamic> student) {
    final direct = student['halaqa_id'];
    if (direct is num) return direct.toInt();
    final halaqa = student['halaqa'];
    if (halaqa is Map && halaqa['id'] is num) {
      return (halaqa['id'] as num).toInt();
    }
    return null;
  }

  Future<void> _bindStudentDraft({
    required String clientUuid,
    required int serverId,
    required int halaqaId,
    required Map<String, dynamic> student,
  }) async {
    await _upsertServerStudent(
      id: serverId,
      halaqaId: halaqaId,
      student: student,
    );
    final dailyRecords = await (select(
      pendingDailyRecords,
    )..where((row) => row.studentClientUuid.equals(clientUuid))).get();
    for (final record in dailyRecords) {
      final payload = Map<String, dynamic>.from(
        jsonDecode(record.payloadJson) as Map,
      );
      payload.remove('student_client_uuid');
      payload['student_id'] = serverId;
      await (update(
        pendingDailyRecords,
      )..where((row) => row.operationUuid.equals(record.operationUuid))).write(
        PendingDailyRecordsCompanion(
          studentId: Value(serverId),
          studentClientUuid: const Value(null),
          halaqaId: Value(halaqaId),
          payloadJson: Value(jsonEncode(payload)),
          updatedAt: Value(DateTime.now().toUtc()),
        ),
      );
    }
    await (delete(
      localStudentDrafts,
    )..where((row) => row.clientUuid.equals(clientUuid))).go();
    await (update(
      pendingStudentOperations,
    )..where((row) => row.clientStudentUuid.equals(clientUuid))).write(
      PendingStudentOperationsCompanion(
        studentId: Value(serverId),
        halaqaId: Value(halaqaId),
        serverStudentId: Value(serverId),
      ),
    );
  }

  String _studentFullName(Map<String, dynamic> student, String? fallback) {
    final explicit = student['full_name']?.toString().trim();
    if (explicit != null && explicit.isNotEmpty) return explicit;
    final parts =
        [
              student['first_name'],
              student['father_name'],
              student['grandfather_name'],
              student['family_name'],
            ]
            .map((value) => value?.toString().trim())
            .whereType<String>()
            .where((value) => value.isNotEmpty);
    final fullName = parts.join(' ');
    return fullName.isEmpty ? fallback ?? 'طالب جديد' : fullName;
  }

  String? _recordDateFromBootstrap(Map<String, dynamic> data) {
    final explicitDate = _normalizeRecordDate(data['record_date']);
    if (explicitDate != null) return explicitDate;

    return _normalizeRecordDate(data['server_time']);
  }

  String? _normalizeRecordDate(dynamic raw) {
    if (raw is! String || raw.length < 10) return null;
    final candidate = raw.substring(0, 10);
    return DateTime.tryParse(candidate) == null ? null : candidate;
  }
}
