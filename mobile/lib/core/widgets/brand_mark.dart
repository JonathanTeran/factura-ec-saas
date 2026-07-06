import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Marca de Facturón EC: cuadrado con borde tricolor (bandera del Ecuador) y la
/// letra "F", con opción de mostrar el nombre debajo. Se usa en el splash y el
/// login para que la identidad sea consistente en toda la app.
class BrandMark extends StatelessWidget {
  /// Lado interior del cuadrado navy (el borde tricolor se dibuja alrededor).
  final double size;
  final bool showLabel;

  const BrandMark({super.key, this.size = 66, this.showLabel = true});

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          padding: EdgeInsets.all(size * 0.05),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(size * 0.33),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFFFFCE00), Color(0xFF0653C6), Color(0xFFEF3340)],
            ),
          ),
          child: Container(
            width: size,
            height: size,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: const Color(0xFF0B1424),
              borderRadius: BorderRadius.circular(size * 0.29),
            ),
            child: Text(
              'F',
              style: TextStyle(
                fontFamily: 'Avenir Next',
                fontWeight: FontWeight.w800,
                fontSize: size * 0.58,
                color: Colors.white,
              ),
            ),
          ),
        ),
        if (showLabel) ...[
          SizedBox(height: size * 0.18),
          Text(
            'Facturón EC',
            style: TextStyle(
              fontFamily: 'Avenir Next',
              fontWeight: FontWeight.w800,
              fontSize: size * 0.33,
              letterSpacing: -0.5,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ],
    );
  }
}
