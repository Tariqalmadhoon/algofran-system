import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:drift/native.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gofran_mobile/core/database/app_database.dart';
import 'package:gofran_mobile/core/network/api_client.dart';
import 'package:gofran_mobile/features/update/data/app_update_repository.dart';
import 'package:gofran_mobile/features/update/presentation/app_update_controller.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late ApiClient api;
  late _ReleaseAdapter adapter;

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({
      SessionKeys.token: 'teacher-token',
    });
    api = ApiClient(const FlutterSecureStorage());
    api.dio.options.baseUrl = 'https://gofran.example/api/v1';
    adapter = _ReleaseAdapter();
    api.dio.httpClientAdapter = adapter;
  });

  tearDown(() => api.dio.close(force: true));

  test(
    'checks the installed build number and parses an available release',
    () async {
      final repository = AppUpdateRepository(
        api,
        platformIsAndroid: () => true,
        installedVersionReader: () async =>
            const InstalledAppVersion(name: '1.2.0', code: 3),
      );

      final release = await repository.check();

      expect(adapter.requestedVersionCode, 3);
      expect(release, isNotNull);
      expect(release!.versionName, '1.3.0');
      expect(release.versionCode, 4);
      expect(release.requiredUpdate, isFalse);
      expect(release.sha256Checksum, _checksumA);
    },
  );

  test('returns no release when the server says the app is current', () async {
    adapter.updateAvailable = false;
    final repository = AppUpdateRepository(
      api,
      platformIsAndroid: () => true,
      installedVersionReader: () async =>
          const InstalledAppVersion(name: '1.3.0', code: 4),
    );

    expect(await repository.check(), isNull);
  });

  test('accepts only secure production download URLs', () {
    expect(
      AppUpdateRepository.isAllowedDownloadUri(
        Uri.parse(
          'https://gofran.example/api/v1/mobile/releases/android/4/download',
        ),
        releaseMode: true,
        trustedApiUri: Uri.parse('https://gofran.example/api/v1'),
      ),
      isTrue,
    );
    expect(
      AppUpdateRepository.isAllowedDownloadUri(
        Uri.parse('http://gofran.example/update.apk'),
        releaseMode: true,
        trustedApiUri: Uri.parse('https://gofran.example/api/v1'),
      ),
      isFalse,
    );
    expect(
      AppUpdateRepository.isAllowedDownloadUri(
        Uri.parse('https://user:secret@gofran.example/update.apk'),
        releaseMode: true,
        trustedApiUri: Uri.parse('https://gofran.example/api/v1'),
      ),
      isFalse,
    );
  });

  test('computes and compares the downloaded APK checksum', () async {
    final directory = await Directory.systemTemp.createTemp(
      'gofran-update-test-',
    );
    final file = File('${directory.path}${Platform.pathSeparator}release.apk');
    addTearDown(() => directory.delete(recursive: true));
    await file.writeAsString('signed-release-apk');

    final checksum = await AppUpdateRepository.checksum(file);

    expect(checksum, sha256Of('signed-release-apk'));
    expect(AppUpdateRepository.constantTimeEquals(checksum, checksum), isTrue);
    expect(
      AppUpdateRepository.constantTimeEquals(checksum, _checksumB),
      isFalse,
    );
  });

  test('rejects a different host, port and insecure production origin', () {
    final origin = Uri.parse('https://gofran.example/api/v1');
    for (final url in [
      'https://untrusted.example/release.apk',
      'https://gofran.example:444/release.apk',
      'http://gofran.example/release.apk',
      'https://gofran.example/release.apk#fragment',
    ]) {
      expect(
        AppUpdateRepository.isAllowedDownloadUri(
          Uri.parse(url),
          trustedApiUri: origin,
          releaseMode: true,
        ),
        isFalse,
      );
    }
  });

  test(
    'verified installer can be reopened without downloading again',
    () async {
      final directory = await Directory.systemTemp.createTemp(
        'gofran-install-test-',
      );
      addTearDown(() => directory.delete(recursive: true));
      final apkAdapter = _ApkAdapter();
      var installed = 0;
      final repository = AppUpdateRepository(
        api,
        downloadDirectoryProvider: () async => directory,
        downloadClientFactory: () => Dio()..httpClientAdapter = apkAdapter,
        installerOpener: (path, release) async {
          installed++;
          expect(await File(path).readAsString(), 'signed-release-apk');
          return true;
        },
      );
      await repository.downloadAndOpenInstaller(_validRelease());
      await repository.downloadAndOpenInstaller(_validRelease());
      expect(installed, 2);
      expect(apkAdapter.requests, 1);
      expect(apkAdapter.authorization, 'Bearer teacher-token');
      expect(apkAdapter.followRedirects, isFalse);
    },
  );

  test('tampered download never reaches installer', () async {
    final directory = await Directory.systemTemp.createTemp(
      'gofran-tamper-test-',
    );
    addTearDown(() => directory.delete(recursive: true));
    var installed = false;
    final repository = AppUpdateRepository(
      api,
      downloadDirectoryProvider: () async => directory,
      downloadClientFactory: () =>
          Dio()..httpClientAdapter = _ApkAdapter('tampered-release!'),
      installerOpener: (path, release) async => installed = true,
    );
    await expectLater(
      repository.downloadAndOpenInstaller(_validRelease()),
      throwsA(isA<ApiFailure>()),
    );
    expect(installed, isFalse);
  });

  test('records created during download block installation', () async {
    final directory = await Directory.systemTemp.createTemp(
      'gofran-pending-test-',
    );
    addTearDown(() => directory.delete(recursive: true));
    var installed = false;
    final repository = AppUpdateRepository(
      api,
      downloadDirectoryProvider: () async => directory,
      downloadClientFactory: () => Dio()..httpClientAdapter = _ApkAdapter(),
      installerOpener: (path, release) async => installed = true,
    );
    await expectLater(
      repository.downloadAndOpenInstaller(
        _validRelease(),
        canInstall: () async => false,
      ),
      throwsA(
        isA<ApiFailure>().having(
          (error) => error.code,
          'code',
          'update_pending_records',
        ),
      ),
    );
    expect(installed, isFalse);
  });

  test(
    'does not start an update while offline records are unresolved',
    () async {
      final database = AppDatabase.forTesting(NativeDatabase.memory());
      addTearDown(database.close);
      await database.queueDailyRecord(
        operationUuid: '09c11e19-6b03-4eb7-9138-6f748c917137',
        studentId: 20,
        halaqaId: 3,
        recordDate: DateTime(2026, 9, 9),
        payload: const {
          'student_id': 20,
          'record_date': '2026-09-09',
          'attendance_status': 'present',
        },
      );
      final repository = AppUpdateRepository(
        api,
        platformIsAndroid: () => true,
        installedVersionReader: () async =>
            const InstalledAppVersion(name: '1.2.0', code: 3),
      );
      final controller = AppUpdateController(repository, database);
      addTearDown(controller.dispose);

      await controller.check();
      await controller.download();

      expect(controller.state.downloading, isFalse);
      expect(controller.state.error, contains('غير متزامنة'));
      expect(adapter.downloadRequests, 0);
    },
  );
}

AndroidAppRelease _validRelease() => AndroidAppRelease(
  versionName: '1.3.0',
  versionCode: 4,
  minimumVersionCode: 1,
  requiredUpdate: false,
  downloadUri: Uri.parse(
    'https://gofran.example/api/v1/mobile/releases/android/4/download',
  ),
  sizeBytes: utf8.encode('signed-release-apk').length,
  sha256Checksum: sha256Of('signed-release-apk'),
);

class _ApkAdapter implements HttpClientAdapter {
  _ApkAdapter([this.contents = 'signed-release-apk']);
  final String contents;
  int requests = 0;
  bool? followRedirects;
  String? authorization;

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests++;
    followRedirects = options.followRedirects;
    authorization = options.headers['Authorization'] as String?;
    return ResponseBody.fromBytes(
      utf8.encode(contents),
      200,
      headers: {
        Headers.contentLengthHeader: ['${utf8.encode(contents).length}'],
        Headers.contentTypeHeader: ['application/vnd.android.package-archive'],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

String sha256Of(String value) {
  // Fixed expected digest without coupling the test to the repository method.
  if (value == 'signed-release-apk') {
    return '8f0070f3a2cbf8dc372cf03b09ed0878a6286f9fe48268144b19eee2910cf20c';
  }

  throw ArgumentError.value(value);
}

class _ReleaseAdapter implements HttpClientAdapter {
  bool updateAvailable = true;
  int? requestedVersionCode;
  int downloadRequests = 0;

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    if (options.path.contains('/download')) downloadRequests++;
    requestedVersionCode =
        options.queryParameters['current_version_code'] as int?;

    return ResponseBody.fromString(
      jsonEncode({
        'data': {
          'platform': 'android',
          'release_available': true,
          'update_available': updateAvailable,
          'required': false,
          'version_name': '1.3.0',
          'version_code': 4,
          'minimum_version_code': 1,
          'download_url':
              'https://gofran.example/api/v1/mobile/releases/android/4/download',
          'size_bytes': 1024,
          'sha256': _checksumA,
          'release_notes': 'تحسينات عامة',
        },
      }),
      200,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

const _checksumA =
    'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
const _checksumB =
    'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
