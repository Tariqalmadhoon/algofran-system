import 'dart:io';

import 'package:crypto/crypto.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:path_provider/path_provider.dart';

import '../../../core/network/api_client.dart';

class InstalledAppVersion {
  const InstalledAppVersion({required this.name, required this.code});

  final String name;
  final int code;
}

class AndroidAppRelease {
  const AndroidAppRelease({
    required this.versionName,
    required this.versionCode,
    required this.minimumVersionCode,
    required this.requiredUpdate,
    required this.downloadUri,
    required this.sizeBytes,
    required this.sha256Checksum,
    this.releaseNotes,
  });

  final String versionName;
  final int versionCode;
  final int minimumVersionCode;
  final bool requiredUpdate;
  final Uri downloadUri;
  final int sizeBytes;
  final String sha256Checksum;
  final String? releaseNotes;

  factory AndroidAppRelease.fromJson(Map<String, dynamic> json) {
    final checksum = (json['sha256'] as String? ?? '').toLowerCase();
    final downloadUri = Uri.tryParse(json['download_url'] as String? ?? '');
    final versionCode = json['version_code'];
    final minimumVersionCode = json['minimum_version_code'];
    final sizeBytes = json['size_bytes'];
    final versionName = json['version_name'];

    if (!RegExp(r'^[a-f0-9]{64}$').hasMatch(checksum) ||
        versionCode is! int ||
        versionCode < 1 ||
        minimumVersionCode is! int ||
        minimumVersionCode < 1 ||
        minimumVersionCode > versionCode ||
        sizeBytes is! int ||
        sizeBytes < 1 ||
        sizeBytes > 536870912 ||
        versionName is! String ||
        !RegExp(r'^\d+\.\d+\.\d+$').hasMatch(versionName) ||
        downloadUri == null ||
        !downloadUri.hasScheme ||
        downloadUri.host.isEmpty) {
      throw const FormatException('بيانات إصدار Android غير صالحة.');
    }

    return AndroidAppRelease(
      versionName: versionName,
      versionCode: versionCode,
      minimumVersionCode: minimumVersionCode,
      requiredUpdate: json['required'] == true,
      downloadUri: downloadUri,
      sizeBytes: sizeBytes,
      sha256Checksum: checksum,
      releaseNotes: json['release_notes'] as String?,
    );
  }
}

typedef InstalledVersionReader = Future<InstalledAppVersion> Function();
typedef InstallerOpener =
    Future<bool> Function(String path, AndroidAppRelease release);
typedef DownloadDirectoryProvider = Future<Directory> Function();
typedef PlatformChecker = bool Function();
typedef DownloadClientFactory = Dio Function();

class AppUpdateRepository {
  AppUpdateRepository(
    this.api, {
    InstalledVersionReader? installedVersionReader,
    InstallerOpener? installerOpener,
    DownloadDirectoryProvider? downloadDirectoryProvider,
    PlatformChecker? platformIsAndroid,
    DownloadClientFactory? downloadClientFactory,
  }) : _installedVersionReader =
           installedVersionReader ?? _readInstalledVersion,
       _installerOpener = installerOpener ?? _openInstaller,
       _downloadDirectoryProvider =
           downloadDirectoryProvider ?? getTemporaryDirectory,
       _platformIsAndroid = platformIsAndroid ?? _isAndroid,
       _downloadClientFactory = downloadClientFactory ?? Dio.new;

  final ApiClient api;
  final InstalledVersionReader _installedVersionReader;
  final InstallerOpener _installerOpener;
  final DownloadDirectoryProvider _downloadDirectoryProvider;
  final PlatformChecker _platformIsAndroid;
  final DownloadClientFactory _downloadClientFactory;

  Future<AndroidAppRelease?> check() async {
    if (!_platformIsAndroid()) return null;

    final installed = await _installedVersionReader();
    final response = await api.dio.get<Map<String, dynamic>>(
      '/mobile/releases/latest',
      queryParameters: {
        'platform': 'android',
        'current_version_code': installed.code,
      },
    );
    final data = Map<String, dynamic>.from(response.data!['data'] as Map);

    if (data['release_available'] != true || data['update_available'] != true) {
      return null;
    }

    final release = AndroidAppRelease.fromJson(data);
    if (release.versionCode <= installed.code) return null;
    if (!isAllowedDownloadUri(
      release.downloadUri,
      trustedApiUri: Uri.parse(api.dio.options.baseUrl),
      releaseMode: kReleaseMode,
    )) {
      throw const FormatException('رابط التحديث لا يتبع خادم المركز.');
    }

    return release;
  }

  Future<void> downloadAndOpenInstaller(
    AndroidAppRelease release, {
    ValueChanged<double>? onProgress,
    Future<bool> Function()? canInstall,
  }) async {
    if (!isAllowedDownloadUri(
      release.downloadUri,
      trustedApiUri: Uri.parse(api.dio.options.baseUrl),
      releaseMode: kReleaseMode,
    )) {
      throw const ApiFailure(
        'رابط التحديث غير آمن. يجب تنزيل النسخة الرسمية عبر HTTPS.',
        code: 'insecure_update_url',
      );
    }

    final cacheDirectory = await _downloadDirectoryProvider();
    final directory = await Directory(
      '${cacheDirectory.path}${Platform.pathSeparator}updates',
    ).create(recursive: true);
    final file = File(
      '${directory.path}${Platform.pathSeparator}gofran-mobile-${release.versionCode}.apk',
    );
    // The APK is private to active teachers. This separate client adds the
    // device token only after the URL has been pinned to the trusted API host.
    final token = await api.secureStorage.read(key: SessionKeys.token);
    if (token == null || token.isEmpty) {
      throw const ApiFailure(
        'يلزم تسجيل الدخول بحساب المحفّظ قبل تنزيل التحديث.',
        code: 'update_authentication_required',
        statusCode: 401,
      );
    }
    final downloadClient = _downloadClientFactory();
    downloadClient.options.connectTimeout = const Duration(seconds: 20);
    downloadClient.options.receiveTimeout = const Duration(minutes: 5);
    final cancelToken = CancelToken();

    try {
      if (!await _matchesRelease(file, release)) {
        await downloadClient.downloadUri(
          release.downloadUri,
          file.path,
          deleteOnError: true,
          cancelToken: cancelToken,
          options: Options(
            followRedirects: false,
            maxRedirects: 0,
            headers: {
              'Accept': 'application/vnd.android.package-archive',
              'Authorization': 'Bearer $token',
            },
            receiveTimeout: const Duration(minutes: 5),
          ),
          onReceiveProgress: (received, total) {
            if (received > release.sizeBytes) {
              cancelToken.cancel('APK exceeded its declared size.');
            }
            onProgress?.call((received / release.sizeBytes).clamp(0, 1));
          },
        );
      }

      if (!await _matchesRelease(file, release)) {
        if (await file.exists()) await file.delete();
        throw const ApiFailure(
          'تم إيقاف التحديث لأن بصمة ملف التثبيت غير مطابقة.',
          code: 'update_checksum_mismatch',
        );
      }

      if (canInstall != null && !await canInstall()) {
        throw const ApiFailure(
          'توجد سجلات جديدة غير متزامنة. أكمل المزامنة ثم أعد التثبيت.',
          code: 'update_pending_records',
        );
      }
      final opened = await _installerOpener(file.path, release);
      if (!opened) {
        throw const ApiFailure(
          'تعذر فتح شاشة التثبيت. اسمح للتطبيق بتثبيت التطبيقات من هذا المصدر ثم أعد المحاولة.',
          code: 'installer_unavailable',
        );
      }
    } on ApiFailure {
      rethrow;
    } catch (error) {
      throw ApiFailure.from(error);
    } finally {
      downloadClient.close(force: true);
    }
  }

  static bool isAllowedDownloadUri(
    Uri uri, {
    required Uri trustedApiUri,
    required bool releaseMode,
  }) {
    if (uri.userInfo.isNotEmpty ||
        uri.host.isEmpty ||
        uri.hasFragment ||
        uri.scheme != trustedApiUri.scheme ||
        uri.host != trustedApiUri.host ||
        uri.port != trustedApiUri.port) {
      return false;
    }
    if (releaseMode) return uri.scheme == 'https';

    return uri.scheme == 'https' || uri.scheme == 'http';
  }

  static Future<bool> _matchesRelease(
    File file,
    AndroidAppRelease release,
  ) async {
    return await file.exists() &&
        await file.length() == release.sizeBytes &&
        constantTimeEquals(await checksum(file), release.sha256Checksum);
  }

  static Future<String> checksum(File file) async {
    final digest = await sha256.bind(file.openRead()).first;

    return digest.toString();
  }

  static bool constantTimeEquals(String actual, String expected) {
    if (actual.length != expected.length) return false;

    var difference = 0;
    for (var index = 0; index < actual.length; index++) {
      difference |= actual.codeUnitAt(index) ^ expected.codeUnitAt(index);
    }

    return difference == 0;
  }

  static Future<InstalledAppVersion> _readInstalledVersion() async {
    final package = await PackageInfo.fromPlatform();

    return InstalledAppVersion(
      name: package.version,
      code: int.tryParse(package.buildNumber) ?? 1,
    );
  }

  static bool _isAndroid() => Platform.isAndroid;

  static Future<bool> _openInstaller(
    String path,
    AndroidAppRelease release,
  ) async {
    try {
      return await const MethodChannel('gofran/app_update').invokeMethod<bool>(
            'install',
            {'path': path, 'versionCode': release.versionCode},
          ) ??
          false;
    } on PlatformException catch (error) {
      throw ApiFailure(
        error.message ?? 'تعذر فتح مثبت التحديث.',
        code: error.code,
      );
    }
  }
}
