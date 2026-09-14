import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../config/api_config.dart';

class ApiClient {
  ApiClient(this.secureStorage)
    : dio = Dio(
        BaseOptions(
          baseUrl: ApiConfig.baseUrl,
          connectTimeout: const Duration(seconds: 12),
          receiveTimeout: const Duration(seconds: 30),
          sendTimeout: const Duration(seconds: 30),
          headers: const {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        ),
      ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await secureStorage.read(key: SessionKeys.token);
          if (token != null &&
              token.isNotEmpty &&
              !options.headers.containsKey('Authorization')) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  final FlutterSecureStorage secureStorage;
  final Dio dio;
}

class SessionKeys {
  SessionKeys._();

  static const token = 'auth_token';
  static const expiresAt = 'auth_expires_at';
  static const deviceUuid = 'device_uuid';
  static const userName = 'user_name';
  static const userId = 'user_id';
  static const userEmail = 'user_email';
}

class ApiFailure implements Exception {
  const ApiFailure(this.message, {this.code, this.fields, this.statusCode});

  final String message;
  final String? code;
  final Map<String, dynamic>? fields;
  final int? statusCode;

  bool get isUnauthorized => statusCode == 401;

  factory ApiFailure.from(Object error) {
    if (error is ApiFailure) return error;

    if (error is DioException) {
      final body = error.response?.data;
      if (body is Map) {
        final errorBody = body['error'];
        return ApiFailure(
          body['message'] as String? ?? 'تعذر الاتصال بالخادم.',
          code: errorBody is Map ? errorBody['code'] as String? : null,
          fields: errorBody is Map && errorBody['fields'] is Map
              ? Map<String, dynamic>.from(errorBody['fields'] as Map)
              : null,
          statusCode: error.response?.statusCode,
        );
      }
      if (error.response?.statusCode != null) {
        return ApiFailure(
          'تعذر إكمال الطلب من الخادم.',
          statusCode: error.response?.statusCode,
        );
      }
      if (error.type == DioExceptionType.connectionError ||
          error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.receiveTimeout ||
          error.type == DioExceptionType.sendTimeout) {
        return const ApiFailure(
          'لا يوجد اتصال بالخادم حاليًا. ستبقى السجلات محفوظة على الجهاز.',
        );
      }
    }

    return const ApiFailure('حدث خطأ غير متوقع. لم تفقد أي بيانات محلية.');
  }

  @override
  String toString() => message;
}
