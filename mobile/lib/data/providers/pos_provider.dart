import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import 'auth_provider.dart';

final posActiveSessionProvider = FutureProvider<ApiPosSession?>((ref) async {
  return ref.read(v1ApiServiceProvider).posActiveSession();
});

final posSessionsProvider = FutureProvider<List<ApiPosSession>>((ref) async {
  return ref.read(v1ApiServiceProvider).posSessions();
});
