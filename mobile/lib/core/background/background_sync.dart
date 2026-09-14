import 'dart:io';

import 'package:flutter/widgets.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:workmanager/workmanager.dart';

import '../../features/sync/data/sync_repository.dart';
import '../database/app_database.dart';
import '../network/api_client.dart';

const backgroundSyncTask = 'gofran.daily-records.sync';

@pragma('vm:entry-point')
void backgroundCallbackDispatcher() {
  Workmanager().executeTask((task, inputData) async {
    WidgetsFlutterBinding.ensureInitialized();
    const storage = FlutterSecureStorage();
    final token = await storage.read(key: SessionKeys.token);
    if (token == null || token.isEmpty) return true;

    final database = AppDatabase();
    try {
      final repository = SyncRepository(ApiClient(storage), storage, database);
      await repository.syncAll(bootstrapIfEmpty: false);
      return true;
    } catch (_) {
      return false;
    } finally {
      await database.close();
    }
  });
}

Future<void> initializeBackgroundSync() async {
  if (!Platform.isAndroid && !Platform.isIOS) return;
  await Workmanager().initialize(backgroundCallbackDispatcher);
  if (Platform.isAndroid) {
    await Workmanager().registerPeriodicTask(
      'gofran-periodic-sync',
      backgroundSyncTask,
      frequency: const Duration(minutes: 15),
      constraints: Constraints(networkType: NetworkType.connected),
      existingWorkPolicy: ExistingPeriodicWorkPolicy.keep,
    );
  }
}
