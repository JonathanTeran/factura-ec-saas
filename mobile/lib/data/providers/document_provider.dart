import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import 'auth_provider.dart';

final sentDocumentsProvider = FutureProvider<PaginatedResult<ApiDocument>>((
  ref,
) async {
  return ref.read(v1ApiServiceProvider).documents(perPage: 25);
});

final receivedDocumentsProvider = FutureProvider<PaginatedResult<ApiDocument>>((
  ref,
) async {
  return ref
      .read(v1ApiServiceProvider)
      .documents(status: 'authorized', perPage: 25);
});

final draftDocumentsProvider = FutureProvider<PaginatedResult<ApiDocument>>((
  ref,
) async {
  return ref.read(v1ApiServiceProvider).documents(status: 'draft', perPage: 25);
});
