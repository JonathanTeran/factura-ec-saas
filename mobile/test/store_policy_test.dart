import 'package:factura_ec_app/core/utils/store_policy.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

/// Blinda las políticas de pago de las tiendas: si alguien vuelve a exponer el
/// flujo de planes/transferencia en iOS o Android, la app se rechaza (App Store
/// 3.1.1, política de Pagos de Google Play).
void main() {
  tearDown(() => debugDefaultTargetPlatformOverride = null);

  test('el flujo de pago queda oculto en iOS', () {
    debugDefaultTargetPlatformOverride = TargetPlatform.iOS;
    expect(billingFlowAvailable, isFalse);
  });

  test('el flujo de pago queda oculto en Android', () {
    debugDefaultTargetPlatformOverride = TargetPlatform.android;
    expect(billingFlowAvailable, isFalse);
  });
}
