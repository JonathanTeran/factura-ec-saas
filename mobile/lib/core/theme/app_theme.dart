import 'package:flutter/material.dart';

class AppColors {
  static const Color primary = Color(0xFF44D8FF);
  static const Color primaryLight = Color(0xFF7AE6FF);
  static const Color primaryDark = Color(0xFF0EA5CF);
  static const Color secondary = Color(0xFF2DE1AE);
  static const Color secondaryLight = Color(0xFF7AF0CD);
  static const Color secondaryDark = Color(0xFF11B48A);

  static const Color background = Color(0xFF070B14);
  static const Color backgroundDark = Color(0xFF050812);
  static const Color surface = Color(0xFF111A2B);
  static const Color surfaceDark = Color(0xFF0D1524);
  static const Color surfaceRaised = Color(0xFF182335);

  static const Color textPrimary = Color(0xFFEAF1FF);
  static const Color textSecondary = Color(0xFFA3B3CE);
  static const Color textMuted = Color(0xFF71829F);
  static const Color border = Color(0xFF263651);

  static const Color success = Color(0xFF47D36F);
  static const Color warning = Color(0xFFFFC857);
  static const Color error = Color(0xFFFF6C72);
  static const Color info = Color(0xFF3F8CFF);
}

class AppTheme {
  static const _radiusMd = 16.0;

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
    final colorScheme = const ColorScheme.dark(
      brightness: Brightness.dark,
      primary: AppColors.primary,
      secondary: AppColors.secondary,
      surface: AppColors.surface,
      error: AppColors.error,
    );

    final baseTheme = ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: colorScheme,
    );

    return baseTheme.copyWith(
      splashFactory: NoSplash.splashFactory,
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
          foregroundColor: AppColors.backgroundDark,
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
