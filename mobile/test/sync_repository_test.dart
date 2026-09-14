import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:drift/native.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gofran_mobile/core/database/app_database.dart';
import 'package:gofran_mobile/core/network/api_client.dart';
import 'package:gofran_mobile/features/auth/data/session_repository.dart';
import 'package:gofran_mobile/features/sync/data/sync_repository.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late AppDatabase database;
  late FlutterSecureStorage storage;
  late ApiClient api;

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({
      SessionKeys.deviceUuid: '4c9d2ee1-f8e4-4778-94ee-2efc321b25df',
      SessionKeys.token: 'test-token',
      SessionKeys.expiresAt: '2099-01-01T00:00:00Z',
      SessionKeys.userName: 'المحفّظ الأول',
      SessionKeys.userId: '7',
      SessionKeys.userEmail: 'teacher1@gofran.com',
    });
    database = AppDatabase.forTesting(NativeDatabase.memory());
    storage = const FlutterSecureStorage();
    api = ApiClient(storage);
  });

  tearDown(() async {
    api.dio.close(force: true);
    await database.close();
  });

  test('one sync run drains more than the 50-record batch limit', () async {
    final adapter = _MobileSyncAdapter();
    api.dio.httpClientAdapter = adapter;
    final repository = SyncRepository(api, storage, database);

    for (var index = 0; index < 51; index++) {
      await database.queueDailyRecord(
        operationUuid:
            '00000000-0000-4000-8000-${index.toString().padLeft(12, '0')}',
        studentId: index + 1,
        halaqaId: 10,
        recordDate: DateTime(2026, 9, 1),
        payload: {
          'student_id': index + 1,
          'halaqa_id': 10,
          'record_date': '2026-09-01',
          'attendance_status': 'present',
          'items': const [],
        },
      );
    }

    final outcome = await repository.syncAll(bootstrapIfEmpty: false);

    expect(outcome.accepted, 51);
    expect(outcome.failed, 0);
    expect(adapter.pushedBatchSizes, [50, 1]);
    expect(await database.recordsToSync(), isEmpty);
    expect(
      (await database.watchOutbox().first).every(
        (record) => record.status == 'synced',
      ),
      isTrue,
    );
  });

  test('a 401 keeps the outbox and owner while credentials expire', () async {
    api.dio.httpClientAdapter = _MobileSyncAdapter(unauthorized: true);
    final repository = SyncRepository(api, storage, database);
    final session = SessionRepository(api, storage, database);
    await database.queueDailyRecord(
      operationUuid: '8b0ad959-e29c-45e3-a0b2-16601bb63c09',
      studentId: 21,
      halaqaId: 10,
      recordDate: DateTime(2026, 9, 1),
      payload: {'student_id': 21, 'record_date': '2026-09-01'},
    );

    await expectLater(
      repository.syncAll(bootstrapIfEmpty: false),
      throwsA(
        isA<DioException>().having(
          (error) => error.response?.statusCode,
          'status code',
          401,
        ),
      ),
    );
    await session.expireAuthentication();

    expect(await storage.read(key: SessionKeys.token), isNull);
    expect(await storage.read(key: SessionKeys.expiresAt), isNull);
    expect(await storage.read(key: SessionKeys.userName), isNull);
    expect(await storage.read(key: SessionKeys.userId), '7');
    expect(
      await storage.read(key: SessionKeys.userEmail),
      'teacher1@gofran.com',
    );
    expect(await database.hasUnresolvedOutbox(), isTrue);
    expect((await database.recordsToSync()).single.status, 'failed');
  });
}

class _MobileSyncAdapter implements HttpClientAdapter {
  _MobileSyncAdapter({this.unauthorized = false});

  final bool unauthorized;
  final List<int> pushedBatchSizes = [];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    if (options.path.endsWith('/mobile/sync/daily-records')) {
      if (unauthorized) {
        return _jsonResponse({
          'message': 'انتهت جلسة الدخول.',
          'error': {'code': 'unauthenticated'},
        }, 401);
      }

      final body = Map<String, dynamic>.from(options.data as Map);
      final operations = List<Map<String, dynamic>>.from(
        (body['operations'] as List).map(
          (operation) => Map<String, dynamic>.from(operation as Map),
        ),
      );
      pushedBatchSizes.add(operations.length);

      return _jsonResponse({
        'data': {
          'results': operations
              .map(
                (operation) => {
                  'operation_uuid': operation['operation_uuid'],
                  'status': 'accepted',
                  'record': {'id': pushedBatchSizes.length * 1000},
                },
              )
              .toList(),
          'summary': {
            'accepted': operations.length,
            'conflicts': 0,
            'rejected': 0,
            'failed': 0,
          },
        },
      });
    }

    if (options.path.endsWith('/mobile/sync/changes')) {
      return _jsonResponse({
        'data': {'records': const [], 'next_cursor': null, 'has_more': false},
      });
    }

    return _jsonResponse({
      'message': 'Not found',
      'error': {'code': 'not_found'},
    }, 404);
  }

  ResponseBody _jsonResponse(Map<String, dynamic> body, [int status = 200]) {
    return ResponseBody.fromString(
      jsonEncode(body),
      status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}
