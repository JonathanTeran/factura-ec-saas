import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import 'auth_provider.dart';

final productsProvider = FutureProvider<PaginatedResult<ApiProduct>>((
  ref,
) async {
  // La lista de gestión incluye inactivos (para editarlos/reactivarlos);
  // el flujo de facturación pide solo activos por separado.
  return ref
      .read(v1ApiServiceProvider)
      .products(perPage: 50, activeOnly: false);
});
