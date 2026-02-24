import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import '../models/subscription_model.dart';
import 'auth_provider.dart';

final plansProvider = FutureProvider<List<ApiPlan>>((ref) async {
  final api = ref.read(v1ApiServiceProvider);
  return api.plans();
});

final currentSubscriptionProvider = FutureProvider<ApiSubscription?>((ref) async {
  final api = ref.read(v1ApiServiceProvider);
  return api.currentSubscription();
});

final bankAccountsProvider = FutureProvider<List<ApiBankAccount>>((ref) async {
  final api = ref.read(v1ApiServiceProvider);
  return api.bankAccounts();
});
