import '../../core/api/v1_api_service.dart';
import '../models/paginated_result.dart';
import '../models/product_model.dart';

/// Repository that wraps product-related API calls.
class ProductRepository {
  final V1ApiService _api;

  const ProductRepository(this._api);

  /// Fetch a paginated list of products with optional filters.
  Future<PaginatedResult<ApiProduct>> list({
    String? search,
    String? type,
    bool activeOnly = true,
    int perPage = 15,
    int page = 1,
  }) {
    return _api.products(
      search: search,
      type: type,
      activeOnly: activeOnly,
      perPage: perPage,
      page: page,
    );
  }

  /// Search products by query string (convenience method).
  Future<PaginatedResult<ApiProduct>> search(String query, {int perPage = 25}) {
    return _api.products(search: query, perPage: perPage);
  }
}
