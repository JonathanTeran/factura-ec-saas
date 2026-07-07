import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/v1_api_service.dart';
import 'auth_provider.dart';

/// Contador que se incrementa al crear/editar/enviar/eliminar un documento.
/// La lista de Documentos (paginada, con estado propio) lo escucha para
/// recargar y mostrar los cambios sin quedar desactualizada.
final documentsRefreshProvider = StateProvider<int>((ref) => 0);

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

/// Full detail of a single document, keyed by its id.
final documentDetailProvider = FutureProvider.family<ApiDocumentDetail, int>((
  ref,
  documentId,
) async {
  return ref.read(v1ApiServiceProvider).getDocument(documentId);
});
