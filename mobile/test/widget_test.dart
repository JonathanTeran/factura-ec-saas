import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'package:factura_ec_app/main.dart';

void main() {
  testWidgets('App boots and renders splash', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: FacturaEcApp()));

    expect(find.text('Factura EC'), findsOneWidget);
    expect(find.byType(CircularProgressIndicator), findsOneWidget);

    await tester.pump(const Duration(milliseconds: 1000));
    await tester.pump();
  });
}
