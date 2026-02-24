import '../../core/api/v1_api_service.dart';
import '../models/document_model.dart';
import '../models/paginated_result.dart';
import '../models/product_model.dart';

/// Repository that wraps document-related API calls.
class DocumentRepository {
  final V1ApiService _api;

  const DocumentRepository(this._api);

  /// Fetch a paginated list of documents with optional filters.
  Future<PaginatedResult<ApiDocument>> list({
    String? status,
    String? search,
    String? documentType,
    int perPage = 15,
    int page = 1,
  }) {
    return _api.documents(
      status: status,
      search: search,
      documentType: documentType,
      perPage: perPage,
      page: page,
    );
  }

  /// Create a new document from the given input.
  Future<ApiDocument> create(CreateDocumentInput input) {
    return _api.createDocument(input);
  }

  /// Send (emit) a document to the SRI.
  Future<ApiDocument> send(int documentId) {
    return _api.sendDocument(documentId);
  }

  /// Check the SRI authorization status of a document.
  Future<ApiDocumentStatus> checkStatus(int documentId) {
    return _api.checkDocumentStatus(documentId);
  }

  /// Fetch recent documents for the dashboard.
  Future<List<ApiDocument>> recent() {
    return _api.recentDocuments();
  }
}
