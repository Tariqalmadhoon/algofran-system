import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app/app_controller.dart';
import 'app/app_theme.dart';
import 'app/providers.dart';
import 'core/background/background_sync.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/home/presentation/home_screen.dart';
import 'shared/widgets/brand_logo.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const ProviderScope(child: GofranApp()));
  unawaited(_initializeBackgroundServices());
}

Future<void> _initializeBackgroundServices() async {
  try {
    await initializeBackgroundSync();
  } catch (_) {
    // Foreground and connectivity-triggered synchronization remain available.
  }
}

class GofranApp extends ConsumerStatefulWidget {
  const GofranApp({super.key});

  @override
  ConsumerState<GofranApp> createState() => _GofranAppState();
}

class _GofranAppState extends ConsumerState<GofranApp>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.read(appControllerProvider.notifier).syncNow(silent: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(appControllerProvider);
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'مركز الغفران لتحفيظ القرآن الكريم',
      theme: AppTheme.light(),
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      builder: (context, child) =>
          Directionality(textDirection: TextDirection.rtl, child: child!),
      home: switch (state.status) {
        AppStatus.initializing => const _SplashScreen(),
        AppStatus.signedOut => const LoginScreen(),
        AppStatus.ready => const HomeScreen(),
      },
    );
  }
}

class _SplashScreen extends StatelessWidget {
  const _SplashScreen();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            BrandLogo(size: 94),
            SizedBox(height: 22),
            Text(
              'مركز الغفران',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
            ),
            SizedBox(height: 18),
            SizedBox.square(
              dimension: 24,
              child: CircularProgressIndicator(strokeWidth: 2.5),
            ),
          ],
        ),
      ),
    );
  }
}
