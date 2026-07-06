import 'package:flutter/material.dart';

class AppColors {
  // Dirección "Editorial / Documento": papel + tinta, con UN azul profundo de
  // la bandera como acento usado con moderación (nada de degradados).
  static const Color primary = Color(0xFF0653C6); // azul bandera
  static const Color primaryLight = Color(0xFF4C86E0);
  static const Color primaryDark = Color(0xFF06429C);
  static const Color secondary = Color(0xFF33415C); // pizarra tinta
  static const Color secondaryLight = Color(0xFF5A6A85);
  static const Color secondaryDark = Color(0xFF232E44);

  // Papel cálido + tarjetas blancas nítidas (documentos sobre un escritorio).
  static const Color background = Color(0xFFF6F4EE);
  static const Color backgroundDark = Color(0xFFFFFFFF); // texto sobre acento
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceDark = Color(0xFFEFECE3); // relleno de inputs/chips
  static const Color surfaceRaised = Color(0xFFE6E2D6); // base del shimmer

  // Tinta cálida en vez de negro puro; grises con leve calidez.
  static const Color textPrimary = Color(0xFF1B1A17);
  static const Color textSecondary = Color(0xFF5C564C);
  static const Color textMuted = Color(0xFF948C7E);
  static const Color border = Color(0xFFE4DFD3); // hairline cálido

  static const Color success = Color(0xFF2F7D45);
  static const Color warning = Color(0xFFB07515);
  static const Color error = Color(0xFFBC3B2E);
  static const Color info = Color(0xFF0653C6);
}

/// Transición de página fluida (fade + leve deslizamiento) para toda la app.
/// Da una sensación premium y suave en cada navegación.
class FluidPageTransitionsBuilder extends PageTransitionsBuilder {
  const FluidPageTransitionsBuilder();

  @override
  Widget buildTransitions<T>(
    PageRoute<T> route,
    BuildContext context,
    Animation<double> animation,
    Animation<double> secondaryAnimation,
    Widget child,
  ) {
    // Ligera y rápida: solo la pantalla entrante hace fade + un desliz corto.
    // No tocamos la saliente (escalar/atenuar la saliente es caro y se siente
    // más lento). RepaintBoundary aísla el repintado durante la transición.
    final entering = CurvedAnimation(
      parent: animation,
      curve: Curves.easeOutCubic,
      reverseCurve: Curves.easeIn,
    );
    return FadeTransition(
      opacity: entering,
      child: SlideTransition(
        position: Tween<Offset>(
          begin: const Offset(0, 0.02),
          end: Offset.zero,
        ).animate(entering),
        child: RepaintBoundary(child: child),
      ),
    );
  }
}

class AppTheme {
  static const _radiusMd = 12.0;

  static TextTheme _textTheme(Color body, Color muted) {
    return TextTheme(
      displaySmall: TextStyle(
        fontFamily: 'Avenir Next',
        fontWeight: FontWeight.w700,
        letterSpacing: -0.8,
        color: body,
      ),
      headlineMedium: TextStyle(
        fontFamily: 'Avenir Next',
        fontWeight: FontWeight.w700,
        letterSpacing: -0.4,
        color: body,
      ),
      titleLarge: TextStyle(
        fontFamily: 'Avenir Next',
        fontWeight: FontWeight.w700,
        letterSpacing: 0.1,
        color: body,
      ),
      titleMedium: TextStyle(
        fontFamily: 'Avenir Next',
        fontWeight: FontWeight.w600,
        letterSpacing: 0.15,
        color: body,
      ),
      bodyLarge: TextStyle(
        fontFamily: 'Avenir Next',
        fontWeight: FontWeight.w500,
        letterSpacing: 0.1,
        color: body,
      ),
      bodyMedium: TextStyle(
        fontFamily: 'Avenir Next',
        fontWeight: FontWeight.w500,
        letterSpacing: 0.1,
        color: muted,
      ),
      labelLarge: TextStyle(
        fontFamily: 'Avenir Next',
        fontWeight: FontWeight.w700,
        letterSpacing: 0.3,
        color: body,
      ),
    );
  }

  static ThemeData get lightTheme {
    final colorScheme = const ColorScheme.light(
      brightness: Brightness.light,
      primary: AppColors.primary,
      onPrimary: Colors.white,
      secondary: AppColors.secondary,
      onSecondary: Colors.white,
      surface: AppColors.surface,
      onSurface: AppColors.textPrimary,
      error: AppColors.error,
    );

    final baseTheme = ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      colorScheme: colorScheme,
    );

    return baseTheme.copyWith(
      splashFactory: InkSparkle.splashFactory,
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.android: FluidPageTransitionsBuilder(),
          TargetPlatform.iOS: FluidPageTransitionsBuilder(),
          TargetPlatform.macOS: FluidPageTransitionsBuilder(),
        },
      ),
      scaffoldBackgroundColor: AppColors.background,
      textTheme: _textTheme(AppColors.textPrimary, AppColors.textSecondary),
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        centerTitle: false,
        scrolledUnderElevation: 0,
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(_radiusMd),
          side: const BorderSide(color: AppColors.border),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surfaceDark,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(_radiusMd),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(_radiusMd),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(_radiusMd),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(_radiusMd),
          borderSide: const BorderSide(color: AppColors.error),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(_radiusMd),
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.primary,
          side: const BorderSide(color: AppColors.primary),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(_radiusMd),
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.primary,
        ),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: AppColors.surfaceDark,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.textSecondary,
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: AppColors.surfaceDark.withValues(alpha: 0.92),
        indicatorColor: AppColors.primary.withValues(alpha: 0.18),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(
            color: selected ? AppColors.primary : AppColors.textSecondary,
            size: 24,
          );
        }),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return TextStyle(
            fontFamily: 'Avenir Next',
            fontWeight: FontWeight.w600,
            color: selected ? AppColors.textPrimary : AppColors.textSecondary,
          );
        }),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.surfaceDark,
        selectedColor: AppColors.primary.withValues(alpha: 0.18),
        side: const BorderSide(color: AppColors.border),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(999),
        ),
      ),
      dividerTheme: const DividerThemeData(
        color: AppColors.border,
        thickness: 1,
      ),
    );
  }

  static ThemeData get darkTheme {
    return lightTheme;
  }
}
