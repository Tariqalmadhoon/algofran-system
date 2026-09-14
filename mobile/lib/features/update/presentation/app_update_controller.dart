import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/database/app_database.dart';
import '../../../core/network/api_client.dart';
import '../data/app_update_repository.dart';

class AppUpdateState {
  const AppUpdateState({
    this.release,
    this.checking = false,
    this.downloading = false,
    this.progress = 0,
    this.dismissed = false,
    this.installerOpened = false,
    this.error,
  });

  final AndroidAppRelease? release;
  final bool checking;
  final bool downloading;
  final double progress;
  final bool dismissed;
  final bool installerOpened;
  final String? error;

  AppUpdateState copyWith({
    AndroidAppRelease? release,
    bool? checking,
    bool? downloading,
    double? progress,
    bool? dismissed,
    bool? installerOpened,
    String? error,
    bool clearError = false,
  }) {
    return AppUpdateState(
      release: release ?? this.release,
      checking: checking ?? this.checking,
      downloading: downloading ?? this.downloading,
      progress: progress ?? this.progress,
      dismissed: dismissed ?? this.dismissed,
      installerOpened: installerOpened ?? this.installerOpened,
      error: clearError ? null : (error ?? this.error),
    );
  }
}

class AppUpdateController extends StateNotifier<AppUpdateState> {
  AppUpdateController(this.repository, this.database)
    : super(const AppUpdateState());

  final AppUpdateRepository repository;
  final AppDatabase database;
  DateTime? _lastCheckAt;

  Future<void> check({bool force = false}) async {
    if (state.checking || state.downloading) return;
    if (!force &&
        _lastCheckAt != null &&
        DateTime.now().difference(_lastCheckAt!) < const Duration(minutes: 5)) {
      return;
    }
    _lastCheckAt = DateTime.now();
    state = state.copyWith(checking: true, clearError: true);

    try {
      final release = await repository.check();
      if (!mounted) return;
      state = AppUpdateState(release: release);
    } catch (_) {
      if (!mounted) return;
      _lastCheckAt = null;
      // An unavailable update server must never interrupt offline work.
      state = const AppUpdateState();
    }
  }

  Future<void> download() async {
    final release = state.release;
    if (release == null || state.downloading) return;

    state = state.copyWith(downloading: true, clearError: true);
    if (await database.hasUnresolvedOutbox()) {
      if (!mounted) return;
      state = state.copyWith(
        downloading: false,
        error:
            'توجد سجلات غير متزامنة. صِل الهاتف بالإنترنت واضغط مزامنة قبل تثبيت التحديث حفاظًا على البيانات.',
      );
      return;
    }
    if (!mounted) return;

    state = state.copyWith(
      downloading: true,
      progress: 0,
      installerOpened: false,
      clearError: true,
    );
    try {
      await repository.downloadAndOpenInstaller(
        release,
        canInstall: () async =>
            mounted && !await database.hasUnresolvedOutbox(),
        onProgress: (progress) {
          if (mounted) state = state.copyWith(progress: progress);
        },
      );
      if (!mounted) return;
      state = state.copyWith(
        downloading: false,
        progress: 1,
        installerOpened: true,
      );
    } catch (error) {
      if (!mounted) return;
      final failure = error is ApiFailure ? error : ApiFailure.from(error);
      state = state.copyWith(downloading: false, error: failure.message);
    }
  }

  void dismiss() {
    if (state.release?.requiredUpdate == true) return;
    state = state.copyWith(dismissed: true, clearError: true);
  }
}
