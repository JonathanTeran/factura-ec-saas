import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import 'auth_provider.dart';

final suppliersProvider = FutureProvider<PaginatedResult<ApiSupplier>>((
  ref,
) async {
  return ref.read(v1ApiServiceProvider).suppliers(perPage: 50);
});

final purchasesProvider = FutureProvider<PaginatedResult<ApiPurchase>>((
  ref,
) async {
  return ref.read(v1ApiServiceProvider).purchases(perPage: 25);
});
