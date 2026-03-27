// mobile/lib/main.dart
import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'services/api_service.dart';
import 'theme/app_theme.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('tr_TR', null);
  await ApiService.init(); // Load token
  runApp(const BerberimApp());
}

class BerberimApp extends StatelessWidget {
  const BerberimApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KUAFÖR RANDEVU',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.darkTheme,
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: const [
        const Locale('tr', 'TR'),
      ],
      home: ApiService.token != null ? const HomeScreen() : const LoginScreen(),
    );
  }
}
