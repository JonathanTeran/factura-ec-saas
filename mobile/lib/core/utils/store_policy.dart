import 'package:flutter/foundation.dart';

/// Las tiendas no permiten vender la suscripción dentro de la app con un medio
/// de pago propio:
/// - App Store (guía 3.1.1) exige compras in-app. Facturón EC se publica en iOS
///   acogiéndose a la excepción 3.1.3(d) ("free standalone apps"), que pide cero
///   compras dentro y cero llamadas a comprar fuera.
/// - Google Play (política de Pagos) exige Google Play Billing para
///   suscripciones a software y servicios en la nube.
///
/// Por eso el flujo de planes + transferencia bancaria solo existe en la build
/// web; en iOS y Android la app es un complemento del servicio web.
///
/// Se usa [kIsWeb] y no `dart:io` porque `dart:io` no existe en web y el harness
/// de QA corre la app con `flutter run -d web-server`.
bool get billingFlowAvailable => kIsWeb;
