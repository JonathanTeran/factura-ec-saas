/// Shared JSON parsing helpers used by all API model classes.
///
/// These functions provide safe, null-tolerant parsing of dynamic values
/// coming from JSON API responses.

int intFrom(dynamic value) {
  if (value is int) return value;
  if (value is double) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

int? nullableIntFrom(dynamic value) {
  if (value == null) return null;
  final parsed = intFrom(value);
  return parsed == 0 && value.toString() != '0' ? null : parsed;
}

double doubleFrom(dynamic value) {
  if (value is double) return value;
  if (value is int) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? 0;
  return 0;
}

String stringFrom(dynamic value, {String fallback = ''}) {
  if (value == null) return fallback;
  final text = value.toString();
  return text.isEmpty ? fallback : text;
}

String? nullableStringFrom(dynamic value) {
  if (value == null) return null;
  final text = value.toString();
  return text.isEmpty ? null : text;
}

DateTime? dateFrom(dynamic value) {
  if (value is DateTime) return value;
  if (value is String && value.isNotEmpty) {
    return DateTime.tryParse(value);
  }
  return null;
}

Map<String, dynamic> mapFrom(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) {
    return value.map((key, val) => MapEntry(key.toString(), val));
  }
  return <String, dynamic>{};
}

Map<String, dynamic>? nullableMapFrom(dynamic value) {
  if (value == null) return null;
  final map = mapFrom(value);
  return map.isEmpty ? null : map;
}

List<dynamic> listFrom(dynamic value) {
  if (value is List) return value;
  return const [];
}

String taxCodeFromRate(double rate) {
  if (rate <= 0) return '0';
  if (rate <= 12.5) return '2';
  if (rate <= 15.5) return '4';
  return '6';
}

String dateOnly(DateTime date) {
  final month = date.month.toString().padLeft(2, '0');
  final day = date.day.toString().padLeft(2, '0');
  return '${date.year}-$month-$day';
}
