import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import 'auth_provider.dart';

final customersProvider = FutureProvider<PaginatedResult<ApiCustomer>>((
  ref,
) async {
  return ref.read(v1ApiServiceProvider).customers(perPage: 50);
});
