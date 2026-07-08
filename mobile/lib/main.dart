import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'core/theme/app_theme.dart';
import 'core/router/app_router.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Inicializar Hive para almacenamiento local
  await Hive.initFlutter();

  // SOLO builds locales de desarrollo/capturas: permite inyectar un token de
  // sesión con --dart-define=DEMO_TOKEN=... (vacío en builds normales; los
  // releases de tienda nunca lo definen).
  const demoToken = String.fromEnvironment('DEMO_TOKEN');
  if (demoToken.isNotEmpty) {
    const storage = FlutterSecureStorage();
    final existing = await storage.read(key: 'access_token');
    if (existing == null || existing.isEmpty) {
      await storage.write(key: 'access_token', value: demoToken);
    }
  }

  // Configurar orientación
  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  // Configurar barra de estado
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
    ),
  );

  runApp(
    const ProviderScope(
      child: FacturaEcApp(),
    ),
  );
}

class FacturaEcApp extends ConsumerWidget {
  const FacturaEcApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);

    return MaterialApp.router(
      title: 'Facturón EC',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.lightTheme,
      themeMode: ThemeMode.light,
      routerConfig: router,
      builder: (context, child) {
        return child ?? const SizedBox.shrink();
      },
    );
  }
}
