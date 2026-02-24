import '../../core/api/v1_api_service.dart';
import '../models/customer_model.dart';
import '../models/paginated_result.dart';

/// Repository that wraps customer-related API calls.
class CustomerRepository {
  final V1ApiService _api;

  const CustomerRepository(this._api);

  /// Fetch a paginated list of customers with optional search.
  Future<PaginatedResult<ApiCustomer>> list({
    String? search,
    int perPage = 15,
    int page = 1,
  }) {
    return _api.customers(
      search: search,
      perPage: perPage,
      page: page,
    );
  }

  /// Search customers by query string (convenience method).
  Future<PaginatedResult<ApiCustomer>> search(String query, {int perPage = 25}) {
    return _api.customers(search: query, perPage: perPage);
  }
}
