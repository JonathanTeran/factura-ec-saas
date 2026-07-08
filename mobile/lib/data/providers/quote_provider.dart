import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import 'auth_provider.dart';

/// Contador para forzar recarga de la lista de proformas tras crear/accionar.
final quotesRefreshProvider = StateProvider<int>((ref) => 0);

final quotesProvider =
    FutureProvider.autoDispose<PaginatedResult<ApiQuote>>((ref) async {
  ref.watch(quotesRefreshProvider);
  final api = ref.read(v1ApiServiceProvider);
  return api.quotes(perPage: 50);
});
