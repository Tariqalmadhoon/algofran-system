import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/network/api_client.dart';
import '../features/auth/data/session_repository.dart';
import '../features/sync/data/sync_repository.dart';

enum AppStatus { initializing, signedOut, ready }

class AppState {
  const AppState({
    this.status = AppStatus.initializing,
    this.busy = false,
    this.syncing = false,
    this.userName = '',
    this.message,
    this.error,
  });

  final AppStatus status;
  final bool busy;
  final bool syncing;
  final String userName;
  final String? message;
  final String? error;

  AppState copyWith({
    AppStatus? status,
    bool? busy,
    bool? syncing,
    String? userName,
    String? message,
    String? error,
    bool clearMessage = false,
    bool clearError = false,
  }) {
    return AppState(
      status: status ?? this.status,
      busy: busy ?? this.busy,
      syncing: syncing ?? this.syncing,
      userName: userName ?? this.userName,
      message: clearMessage ? null : (message ?? this.message),
      error: clearError ? null : (error ?? this.error),
    );
  }
}

class AppController extends StateNotifier<AppState> {
  AppController(this.session, this.sync, this.connectivity)
    : super(const AppState()) {
    _connectivitySubscription = connectivity.onConnectivityChanged.listen((
      results,
    ) {
      if (results.any((result) => result != ConnectivityResult.none) &&
          state.status == AppStatus.ready) {
        syncNow(silent: true);
      }
    });
  }

  final SessionRepository session;
  final SyncRepository sync;
  final Connectivity connectivity;
  StreamSubscription<List<ConnectivityResult>>? _connectivitySubscription;

  Future<void> initialize() async {
    try {
      final authenticated = await session.isAuthenticated();
      if (!authenticated) {
        state = const AppState(status: AppStatus.signedOut);
        return;
      }

      state = AppState(
        status: AppStatus.ready,
        userName: await session.userName(),
      );
      try {
        await sync.bootstrap();
      } catch (_) {
        // Cached data remains usable when the application starts offline.
      }
      await syncNow(silent: true);
    } catch (error) {
      final failure = error is ApiFailure ? error : ApiFailure.from(error);
      state = AppState(status: AppStatus.signedOut, error: failure.message);
    }
  }

  Future<bool> login(String email, String password) async {
    state = state.copyWith(busy: true, clearError: true, clearMessage: true);
    try {
      await session.login(email: email, password: password);
      await sync.bootstrap();
      state = AppState(
        status: AppStatus.ready,
        userName: await session.userName(),
      );
      await syncNow(silent: true);
      return true;
    } catch (error) {
      final failure = error is ApiFailure ? error : ApiFailure.from(error);
      if (failure.isUnauthorized) {
        await session.expireAuthentication();
        state = AppState(
          status: AppStatus.signedOut,
          error: _reauthenticationMessage,
        );
        return false;
      }
      state = state.copyWith(busy: false, error: failure.message);
      return false;
    }
  }

  Future<void> syncNow({bool silent = false}) async {
    if (state.syncing || state.status != AppStatus.ready) return;
    state = state.copyWith(
      syncing: true,
      clearError: true,
      clearMessage: silent,
    );
    try {
      final outcome = await sync.syncAll();
      final hasIssues =
          outcome.conflicts + outcome.rejected + outcome.failed > 0;
      state = state.copyWith(
        syncing: false,
        message: silent
            ? null
            : outcome.accepted > 0
            ? 'تم اعتماد ${outcome.accepted} سجل بنجاح.'
            : 'البيانات متزامنة.',
        error: hasIssues
            ? outcome.message ?? 'توجد سجلات تحتاج إلى مراجعة.'
            : null,
        clearMessage: silent && outcome.accepted == 0,
        clearError: !hasIssues,
      );
    } catch (error) {
      final failure = ApiFailure.from(error);
      if (failure.isUnauthorized) {
        await session.expireAuthentication();
        state = const AppState(
          status: AppStatus.signedOut,
          error: _reauthenticationMessage,
        );
        return;
      }
      state = state.copyWith(
        syncing: false,
        error: silent ? null : failure.message,
      );
    }
  }

  Future<void> logout() async {
    if (await session.hasUnresolvedRecords()) {
      state = state.copyWith(
        error:
            'توجد سجلات غير متزامنة. أكمل المزامنة أو راجع السجلات المرفوضة قبل تسجيل الخروج.',
        clearMessage: true,
      );
      return;
    }
    state = state.copyWith(busy: true);
    await session.logout();
    state = const AppState(status: AppStatus.signedOut);
  }

  void clearFeedback() {
    state = state.copyWith(clearError: true, clearMessage: true);
  }

  @override
  void dispose() {
    _connectivitySubscription?.cancel();
    super.dispose();
  }
}

const _reauthenticationMessage =
    'انتهت جلسة الدخول. سجلاتك المحلية محفوظة؛ سجّل الدخول بالحساب نفسه لإكمال المزامنة.';
