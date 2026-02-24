import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:pretty_dio_logger/pretty_dio_logger.dart';
import '../constants/api_constants.dart';

class ApiClient {
  late final Dio _dio;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  ApiClient() {
    _dio = Dio(
      BaseOptions(
        baseUrl: ApiConstants.baseUrl + ApiConstants.apiPath,
        connectTimeout: ApiConstants.connectTimeout,
        receiveTimeout: ApiConstants.receiveTimeout,
        headers: {
          ApiConstants.contentType: ApiConstants.applicationJson,
          ApiConstants.accept: ApiConstants.applicationJson,
        },
      ),
    );

    _dio.interceptors.addAll([
      _AuthInterceptor(_storage),
      PrettyDioLogger(
        requestHeader: true,
        requestBody: true,
        responseHeader: true,
      ),
    ]);
  }

  Dio get dio => _dio;

  Future<bool> hasAccessToken() async {
    final token = await _storage.read(key: 'access_token');
    return token != null && token.isNotEmpty;
  }

  Future<bool> pingBackend() async {
    try {
      final response = await _dio.get<void>(
        '/',
        options: Options(
          validateStatus: (status) => status != null && status < 500,
        ),
      );
      return response.statusCode != null;
    } on DioException catch (e) {
      if (e.response != null) {
        return true;
      }
      return false;
    } catch (_) {
      return false;
    }
  }

  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    return _dio.get<T>(path, queryParameters: queryParameters, options: options);
  }

  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    return _dio.post<T>(path, data: data, queryParameters: queryParameters, options: options);
  }

  Future<Response<T>> put<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    return _dio.put<T>(path, data: data, queryParameters: queryParameters, options: options);
  }

  Future<Response<T>> delete<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    return _dio.delete<T>(path, data: data, queryParameters: queryParameters, options: options);
  }
}

class _AuthInterceptor extends Interceptor {
  final FlutterSecureStorage _storage;
  bool _isRefreshing = false;

  _AuthInterceptor(this._storage);

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await _storage.read(key: 'access_token');
    if (token != null) {
      options.headers[ApiConstants.authorization] = '${ApiConstants.bearer} $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401 && !_isRefreshing) {
      _isRefreshing = true;
      try {
        final currentToken = await _storage.read(key: 'access_token');
        if (currentToken == null) {
          await _clearAndReject(err, handler);
          return;
        }

        // Llamar al endpoint de refresh con el token actual
        final refreshDio = Dio(BaseOptions(
          baseUrl: ApiConstants.baseUrl + ApiConstants.apiPath,
          connectTimeout: ApiConstants.connectTimeout,
          receiveTimeout: ApiConstants.receiveTimeout,
          headers: {
            ApiConstants.contentType: ApiConstants.applicationJson,
            ApiConstants.accept: ApiConstants.applicationJson,
            ApiConstants.authorization: '${ApiConstants.bearer} $currentToken',
          },
        ));

        final response = await refreshDio.post<Map<String, dynamic>>(
          ApiConstants.refreshToken,
        );

        final data = response.data?['data'] as Map<String, dynamic>?;
        final newToken = data?['token'] as String?;

        if (newToken != null) {
          // Guardar el nuevo token
          await _storage.write(key: 'access_token', value: newToken);

          // Reintentar la solicitud original con el nuevo token
          final opts = err.requestOptions;
          opts.headers[ApiConstants.authorization] = '${ApiConstants.bearer} $newToken';

          final retryResponse = await refreshDio.fetch(opts);
          _isRefreshing = false;
          handler.resolve(retryResponse);
          return;
        }

        await _clearAndReject(err, handler);
      } on DioException {
        await _clearAndReject(err, handler);
      } catch (_) {
        await _clearAndReject(err, handler);
      }
    } else {
      handler.next(err);
    }
  }

  Future<void> _clearAndReject(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    _isRefreshing = false;
    await _storage.deleteAll();
    handler.next(err);
  }
}
