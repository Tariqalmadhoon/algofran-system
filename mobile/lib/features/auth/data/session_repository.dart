import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';

import '../../../core/config/api_config.dart';
import '../../../core/database/app_database.dart';
import '../../../core/network/api_client.dart';

class SessionRepository {
  SessionRepository(this.api, this.storage, this.database);

  final ApiClient api;
  final FlutterSecureStorage storage;
  final AppDatabase database;

  Future<bool> isAuthenticated() async {
    final token = await storage.read(key: SessionKeys.token);
    final expiresAt = await storage.read(key: SessionKeys.expiresAt);
    if (token == null || token.isEmpty) return false;
    if (expiresAt != null) {
      final expiry = DateTime.tryParse(expiresAt);
      if (expiry != null && expiry.isBefore(DateTime.now().toUtc())) {
        await expireAuthentication();
        return false;
      }
    }
    return true;
  }

  Future<String> deviceUuid() async {
    final existing = await storage.read(key: SessionKeys.deviceUuid);
    if (existing != null && existing.isNotEmpty) return existing;
    final generated = const Uuid().v4();
    await storage.write(key: SessionKeys.deviceUuid, value: generated);
    return generated;
  }

  Future<void> login({required String email, required String password}) async {
    try {
      final normalizedEmail = email.trim().toLowerCase();
      final hasUnresolvedRecords = await database.hasUnresolvedOutbox();
      final ownerEmail = await storage.read(key: SessionKeys.userEmail);
      if (hasUnresolvedRecords &&
          ownerEmail != null &&
          ownerEmail.toLowerCase() != normalizedEmail) {
        throw const ApiFailure(
          'توجد سجلات غير متزامنة للحساب السابق. ادخل بالحساب نفسه وأكمل المزامنة قبل تبديل الحساب.',
        );
      }

      final uuid = await deviceUuid();
      final response = await api.dio.post<Map<String, dynamic>>(
        '/auth/login',
        data: {
          'email': normalizedEmail,
          'password': password,
          'device_name': 'تطبيق مركز الغفران',
          'device_uuid': uuid,
          'platform': Platform.isIOS ? 'ios' : 'android',
          'app_version': ApiConfig.appVersion,
        },
      );
      final data = Map<String, dynamic>.from(response.data!['data'] as Map);
      final abilities = List<String>.from(
        data['abilities'] as List? ?? const [],
      );
      if (!abilities.contains('mobile:sync')) {
        await _revokeIssuedToken(data['token'] as String);
        throw const ApiFailure(
          'هذا الحساب لا يملك صلاحية تسجيل الحضور والتسميع من الجوال.',
        );
      }
      final user = Map<String, dynamic>.from(data['user'] as Map);
      final userId = '${user['id']}';
      final ownerId = await storage.read(key: SessionKeys.userId);
      if (hasUnresolvedRecords && ownerId != null && ownerId != userId) {
        await _revokeIssuedToken(data['token'] as String);
        throw const ApiFailure(
          'هذه السجلات تخص حسابًا آخر. سجّل الدخول بالحساب الأصلي لمزامنتها أولًا.',
        );
      }
      if (!hasUnresolvedRecords && ownerId != null && ownerId != userId) {
        await database.clearSessionData();
      }
      await storage.write(
        key: SessionKeys.token,
        value: data['token'] as String,
      );
      await storage.write(
        key: SessionKeys.expiresAt,
        value: data['expires_at'] as String?,
      );
      await storage.write(
        key: SessionKeys.userName,
        value: user['name'] as String?,
      );
      await storage.write(key: SessionKeys.userId, value: userId);
      await storage.write(key: SessionKeys.userEmail, value: normalizedEmail);
    } catch (error) {
      if (error is ApiFailure) rethrow;
      throw ApiFailure.from(error);
    }
  }

  Future<void> logout({bool clearLocalData = true}) async {
    try {
      if (await storage.read(key: SessionKeys.token) != null) {
        await api.dio.post<void>('/auth/logout');
      }
    } catch (_) {
      // Local sign-out must remain available when the server is unreachable.
    }
    await storage.delete(key: SessionKeys.token);
    await storage.delete(key: SessionKeys.expiresAt);
    await storage.delete(key: SessionKeys.userName);
    if (clearLocalData) {
      await storage.delete(key: SessionKeys.userId);
      await storage.delete(key: SessionKeys.userEmail);
      await database.clearSessionData();
    }
  }

  Future<bool> hasUnresolvedRecords() => database.hasUnresolvedOutbox();

  /// Removes only the expired credentials. The device identity, cached data,
  /// outbox ownership and queued records must survive re-authentication.
  Future<void> expireAuthentication() async {
    await storage.delete(key: SessionKeys.token);
    await storage.delete(key: SessionKeys.expiresAt);
    await storage.delete(key: SessionKeys.userName);
  }

  Future<String> userName() async {
    return await storage.read(key: SessionKeys.userName) ?? 'المحفّظ';
  }

  Future<void> _revokeIssuedToken(String token) async {
    try {
      await api.dio.post<void>(
        '/auth/logout',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );
    } catch (_) {
      // The rejected account switch must never expose or store the new token.
    }
  }
}
