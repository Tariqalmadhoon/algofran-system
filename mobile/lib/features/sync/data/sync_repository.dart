import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:intl/intl.dart';
import 'package:uuid/uuid.dart';

import '../../../core/database/app_database.dart';
import '../../../core/network/api_client.dart';

class SyncRepository {
  SyncRepository(this.api, this.storage, this.database);

  final ApiClient api;
  final FlutterSecureStorage storage;
  final AppDatabase database;
  bool _running = false;

  Future<void> bootstrap() async {
    final deviceUuid = await _requiredDeviceUuid();
    try {
      final response = await api.dio.get<Map<String, dynamic>>(
        '/mobile/bootstrap',
        queryParameters: {'device_uuid': deviceUuid},
      );
      final data = Map<String, dynamic>.from(response.data!['data'] as Map);
      await database.replaceBootstrap(data);
    } catch (error) {
      throw ApiFailure.from(error);
    }
  }

  Future<String> queueDailyRecord({
    int? studentId,
    String? studentClientUuid,
    required int halaqaId,
    required DateTime recordDate,
    required String attendanceStatus,
    String? attendanceNotes,
    String? generalEvaluation,
    String? notes,
    required List<Map<String, dynamic>> items,
  }) async {
    final uuid = const Uuid().v4();
    await database.queueDailyRecord(
      operationUuid: uuid,
      studentId: studentId,
      studentClientUuid: studentClientUuid,
      halaqaId: halaqaId,
      recordDate: recordDate,
      payload: {
        'student_id': ?studentId,
        'student_client_uuid': ?studentClientUuid,
        'halaqa_id': halaqaId,
        'record_date': DateFormat('yyyy-MM-dd').format(recordDate),
        'attendance_status': attendanceStatus,
        'attendance_notes': _nullIfEmpty(attendanceNotes),
        'general_evaluation': _nullIfEmpty(generalEvaluation),
        'notes': _nullIfEmpty(notes),
        'items': items,
      },
    );
    return uuid;
  }

  Future<SyncOutcome> syncAll({bool bootstrapIfEmpty = true}) async {
    if (_running) return const SyncOutcome();
    _running = true;
    try {
      if (bootstrapIfEmpty && await database.setting('sync_cursor') == null) {
        await bootstrap();
      }
      var push = const SyncOutcome();
      while (true) {
        final batch = await _pushStudentOperationsBatch();
        push = push.merge(batch.outcome);
        if (batch.isEmpty || !batch.shouldContinue) break;
      }
      while (true) {
        final batch = await _pushPendingBatch();
        push = push.merge(batch.outcome);
        if (batch.isEmpty || !batch.shouldContinue) break;
      }
      await _pullChanges();
      if (push.accepted + push.conflicts + push.rejected > 0) {
        try {
          // An accepted record may create a progress snapshot or achievement.
          // A failed cache refresh must not mark that accepted record as failed.
          await bootstrap();
        } catch (_) {}
      }
      await database.setSetting(
        'last_sync_at',
        DateTime.now().toUtc().toIso8601String(),
      );
      return push;
    } finally {
      _running = false;
    }
  }

  Future<_PushBatchResult> _pushStudentOperationsBatch() async {
    final queued = await database.studentOperationsToSync();
    if (queued.isEmpty) return const _PushBatchResult.empty();
    await database.markStudentOperationsSending(
      queued.map((operation) => operation.operationUuid).toList(),
    );
    try {
      final response = await api.dio.post<Map<String, dynamic>>(
        '/mobile/sync/student-operations',
        data: {
          'device_uuid': await _requiredDeviceUuid(),
          'operations': queued
              .map(
                (operation) => {
                  'operation_uuid': operation.operationUuid,
                  'client_created_at': operation.clientCreatedAt
                      .toUtc()
                      .toIso8601String(),
                  'type': operation.operationType,
                  'student': jsonDecode(operation.payloadJson),
                },
              )
              .toList(),
        },
      );
      final data = Map<String, dynamic>.from(response.data!['data'] as Map);
      final results = List<Map<String, dynamic>>.from(
        (data['results'] as List? ?? const []).map(
          (item) => Map<String, dynamic>.from(item as Map),
        ),
      );
      for (final result in results) {
        await database.applyStudentSyncResult(
          result['operation_uuid'] as String,
          result,
        );
      }
      final summary = Map<String, dynamic>.from(data['summary'] as Map);
      final returned = results
          .map((result) => result['operation_uuid'])
          .whereType<String>()
          .toSet();
      final missing = queued
          .where((operation) => !returned.contains(operation.operationUuid))
          .length;
      if (missing > 0) {
        await database.returnStudentOperationsToPending(
          'لم يرجع الخادم نتيجة لبعض عمليات الطلاب. ستعاد المحاولة.',
        );
      }
      final failed = (summary['failed'] as int? ?? 0) + missing;
      return _PushBatchResult(
        outcome: SyncOutcome(
          accepted: summary['accepted'] as int? ?? 0,
          conflicts: summary['conflicts'] as int? ?? 0,
          rejected: summary['rejected'] as int? ?? 0,
          failed: failed,
        ),
        shouldContinue: failed == 0 && returned.isNotEmpty,
      );
    } catch (error) {
      final failure = ApiFailure.from(error);
      await database.returnStudentOperationsToPending(failure.message);
      if (error is DioException && error.response?.statusCode == 401) rethrow;
      return _PushBatchResult(
        outcome: SyncOutcome(failed: queued.length, message: failure.message),
        shouldContinue: false,
      );
    }
  }

  Future<_PushBatchResult> _pushPendingBatch() async {
    final queued = await database.recordsToSync();
    if (queued.isEmpty) return const _PushBatchResult.empty();
    await database.markSending(
      queued.map((record) => record.operationUuid).toList(),
    );

    try {
      final response = await api.dio.post<Map<String, dynamic>>(
        '/mobile/sync/daily-records',
        data: {
          'device_uuid': await _requiredDeviceUuid(),
          'operations': queued
              .map(
                (record) => {
                  'operation_uuid': record.operationUuid,
                  'client_created_at': record.clientCreatedAt
                      .toUtc()
                      .toIso8601String(),
                  'daily_record': jsonDecode(record.payloadJson),
                },
              )
              .toList(),
        },
      );
      final data = Map<String, dynamic>.from(response.data!['data'] as Map);
      final results = List<Map<String, dynamic>>.from(
        (data['results'] as List).map(
          (item) => Map<String, dynamic>.from(item as Map),
        ),
      );
      for (final result in results) {
        await database.applySyncResult(
          result['operation_uuid'] as String,
          result,
        );
      }
      final summary = Map<String, dynamic>.from(data['summary'] as Map);
      final returnedUuids = results
          .map((result) => result['operation_uuid'])
          .whereType<String>()
          .toSet();
      final missingResults = queued
          .where((record) => !returnedUuids.contains(record.operationUuid))
          .length;
      final failed = (summary['failed'] as int? ?? 0) + missingResults;
      String? message;
      if (missingResults > 0) {
        message =
            'لم يُرجع الخادم نتيجة لبعض السجلات. ستبقى محفوظة لإعادة المحاولة.';
        await database.returnSendingToPending(message);
      }
      final outcome = SyncOutcome(
        accepted: summary['accepted'] as int? ?? 0,
        conflicts: summary['conflicts'] as int? ?? 0,
        rejected: summary['rejected'] as int? ?? 0,
        failed: failed,
        message: message,
      );
      return _PushBatchResult(
        outcome: outcome,
        shouldContinue: failed == 0 && returnedUuids.isNotEmpty,
      );
    } catch (error) {
      final failure = ApiFailure.from(error);
      await database.returnSendingToPending(failure.message);
      if (error is DioException && error.response?.statusCode == 401) rethrow;
      return _PushBatchResult(
        outcome: SyncOutcome(failed: queued.length, message: failure.message),
        shouldContinue: false,
      );
    }
  }

  Future<void> _pullChanges() async {
    var cursor = await database.setting('sync_cursor');
    var hasMore = true;
    while (hasMore) {
      final response = await api.dio.get<Map<String, dynamic>>(
        '/mobile/sync/changes',
        queryParameters: {
          'device_uuid': await _requiredDeviceUuid(),
          'cursor': ?cursor,
          'limit': 100,
        },
      );
      final data = Map<String, dynamic>.from(response.data!['data'] as Map);
      final records = List<dynamic>.from(data['records'] as List? ?? const []);
      final today = DateFormat('yyyy-MM-dd').format(DateTime.now());
      for (final raw in records) {
        final record = Map<String, dynamic>.from(raw as Map);
        final student = Map<String, dynamic>.from(record['student'] as Map);
        if (record['record_date'] == today) {
          final recordDate = DateTime.tryParse(record['record_date'] as String);
          if (recordDate == null) continue;
          await database.markStudentRecorded(
            student['id'] as int,
            record['id'] as int?,
            recordDate,
          );
        }
      }
      cursor = data['next_cursor'] as String? ?? cursor;
      if (cursor != null) await database.setSetting('sync_cursor', cursor);
      hasMore = data['has_more'] as bool? ?? false;
    }
  }

  Future<String> _requiredDeviceUuid() async {
    final uuid = await storage.read(key: SessionKeys.deviceUuid);
    if (uuid == null || uuid.isEmpty) {
      throw const ApiFailure('هوية الجهاز غير موجودة. سجّل الدخول مجددًا.');
    }
    return uuid;
  }

  String? _nullIfEmpty(String? value) {
    final normalized = value?.trim();
    return normalized == null || normalized.isEmpty ? null : normalized;
  }
}

class SyncOutcome {
  const SyncOutcome({
    this.accepted = 0,
    this.conflicts = 0,
    this.rejected = 0,
    this.failed = 0,
    this.message,
  });

  final int accepted;
  final int conflicts;
  final int rejected;
  final int failed;
  final String? message;

  SyncOutcome merge(SyncOutcome other) {
    return SyncOutcome(
      accepted: accepted + other.accepted,
      conflicts: conflicts + other.conflicts,
      rejected: rejected + other.rejected,
      failed: failed + other.failed,
      message: message ?? other.message,
    );
  }
}

class _PushBatchResult {
  const _PushBatchResult({required this.outcome, required this.shouldContinue})
    : isEmpty = false;

  const _PushBatchResult.empty()
    : outcome = const SyncOutcome(),
      shouldContinue = false,
      isEmpty = true;

  final SyncOutcome outcome;
  final bool shouldContinue;
  final bool isEmpty;
}
