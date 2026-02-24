import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import 'auth_provider.dart';

final companiesProvider = FutureProvider<List<ApiCompany>>((ref) async {
  return ref.read(v1ApiServiceProvider).companies();
});
