import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/database/app_database.dart';
import '../core/network/api_client.dart';
import '../core/database/profile_snapshots.dart';
import '../features/auth/data/session_repository.dart';
import '../features/sync/data/sync_repository.dart';
import '../features/reports/data/report_export_repository.dart';
import '../features/students/data/student_management_repository.dart';
import '../features/update/data/app_update_repository.dart';
import '../features/update/presentation/app_update_controller.dart';
import 'app_controller.dart';

final secureStorageProvider = Provider<FlutterSecureStorage>(
  (ref) => const FlutterSecureStorage(),
);

final databaseProvider = Provider<AppDatabase>((ref) {
  final database = AppDatabase();
  ref.onDispose(database.close);
  return database;
});

final apiClientProvider = Provider<ApiClient>(
  (ref) => ApiClient(ref.watch(secureStorageProvider)),
);

final sessionRepositoryProvider = Provider<SessionRepository>(
  (ref) => SessionRepository(
    ref.watch(apiClientProvider),
    ref.watch(secureStorageProvider),
    ref.watch(databaseProvider),
  ),
);

final syncRepositoryProvider = Provider<SyncRepository>(
  (ref) => SyncRepository(
    ref.watch(apiClientProvider),
    ref.watch(secureStorageProvider),
    ref.watch(databaseProvider),
  ),
);

final reportExportRepositoryProvider = Provider<ReportExportRepository>(
  (ref) => ReportExportRepository(ref.watch(apiClientProvider)),
);

final studentManagementRepositoryProvider =
    Provider<StudentManagementRepository>(
      (ref) => StudentManagementRepository(ref.watch(databaseProvider)),
    );

final appUpdateRepositoryProvider = Provider<AppUpdateRepository>(
  (ref) => AppUpdateRepository(ref.watch(apiClientProvider)),
);

final appUpdateControllerProvider =
    StateNotifierProvider<AppUpdateController, AppUpdateState>(
      (ref) => AppUpdateController(
        ref.watch(appUpdateRepositoryProvider),
        ref.watch(databaseProvider),
      ),
    );

final connectivityProvider = Provider<Connectivity>((ref) => Connectivity());

final appControllerProvider = StateNotifierProvider<AppController, AppState>((
  ref,
) {
  final controller = AppController(
    ref.watch(sessionRepositoryProvider),
    ref.watch(syncRepositoryProvider),
    ref.watch(connectivityProvider),
  );
  controller.initialize();
  return controller;
});

final halaqasProvider = StreamProvider<List<CachedHalaqa>>(
  (ref) => ref.watch(databaseProvider).watchHalaqas(),
);

final studentsProvider = StreamProvider.family<List<CachedStudent>, int>(
  (ref, halaqaId) => ref.watch(databaseProvider).watchStudents(halaqaId),
);

final cachedStudentProvider = StreamProvider.family<CachedStudent?, int>(
  (ref, studentId) => ref.watch(databaseProvider).watchStudent(studentId),
);

final studentDraftsProvider =
    StreamProvider.family<List<LocalStudentDraft>, int>(
      (ref, halaqaId) =>
          ref.watch(databaseProvider).watchStudentDrafts(halaqaId),
    );

final studentDraftProvider = StreamProvider.family<LocalStudentDraft?, String>(
  (ref, clientUuid) =>
      ref.watch(databaseProvider).watchStudentDraft(clientUuid),
);

final studentOperationsProvider = StreamProvider<List<PendingStudentOperation>>(
  (ref) => ref.watch(databaseProvider).watchStudentOperations(),
);

final teacherProfileProvider = StreamProvider<TeacherProfileSnapshot?>(
  (ref) => ref.watch(databaseProvider).watchTeacherProfile(),
);

final studentProfileProvider =
    StreamProvider.family<StudentProfileSnapshot?, int>(
      (ref, studentId) =>
          ref.watch(databaseProvider).watchStudentProfile(studentId),
    );

final studentStatusRecordDateProvider = StreamProvider<String?>(
  (ref) => ref.watch(databaseProvider).watchStudentStatusRecordDate(),
);

final outboxProvider = StreamProvider<List<PendingDailyRecord>>(
  (ref) => ref.watch(databaseProvider).watchOutbox(),
);
