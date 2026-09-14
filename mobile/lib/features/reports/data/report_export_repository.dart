import 'dart:io';

import 'package:dio/dio.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../../../core/network/api_client.dart';

class ReportExportRepository {
  ReportExportRepository(this.api);

  final ApiClient api;

  Future<MobileReportExport> request({
    required String reportType,
    int? halaqaId,
    String? dateFrom,
    String? dateTo,
  }) async {
    try {
      final payload = <String, dynamic>{
        'report_type': reportType,
        'halaqa_id': halaqaId,
        'date_from': dateFrom,
        'date_to': dateTo,
      }..removeWhere((_, value) => value == null);
      final response = await api.dio.post<Map<String, dynamic>>(
        '/mobile/report-exports',
        data: payload,
      );
      return MobileReportExport.fromResponse(response.data);
    } catch (error) {
      throw ApiFailure.from(error);
    }
  }

  Future<MobileReportExport> status(String uuid) async {
    try {
      final response = await api.dio.get<Map<String, dynamic>>(
        '/mobile/report-exports/$uuid',
      );
      return MobileReportExport.fromResponse(response.data);
    } catch (error) {
      throw ApiFailure.from(error);
    }
  }

  Future<String> download(MobileReportExport export) async {
    try {
      final response = await api.dio.get<List<int>>(
        '/mobile/report-exports/${export.uuid}/download',
        options: Options(responseType: ResponseType.bytes),
      );
      final directory = await getApplicationDocumentsDirectory();
      final reports = Directory(
        '${directory.path}${Platform.pathSeparator}reports',
      );
      if (!await reports.exists()) await reports.create(recursive: true);
      final name = _safeFilename(
        export.filename ?? 'gofran-${export.reportType}-${export.uuid}.xlsx',
      );
      final file = File('${reports.path}${Platform.pathSeparator}$name');
      await file.writeAsBytes(response.data ?? const [], flush: true);
      return file.path;
    } catch (error) {
      throw ApiFailure.from(error);
    }
  }

  Future<void> open(String path) async {
    final result = await OpenFilex.open(path);
    if (result.type != ResultType.done) {
      throw ApiFailure(
        result.message.isEmpty
            ? 'تعذر فتح الملف. ثبّت تطبيقًا يدعم ملفات Excel.'
            : result.message,
      );
    }
  }

  String _safeFilename(String value) {
    final cleaned = value.replaceAll(RegExp(r'[\\/:*?"<>|]'), '-').trim();
    return cleaned.toLowerCase().endsWith('.xlsx') ? cleaned : '$cleaned.xlsx';
  }
}

class MobileReportExport {
  const MobileReportExport({
    required this.uuid,
    required this.reportType,
    required this.status,
    this.filename,
    this.rowsCount,
    this.failureMessage,
  });

  final String uuid;
  final String reportType;
  final String status;
  final String? filename;
  final int? rowsCount;
  final String? failureMessage;

  bool get isReady => status == 'ready';
  bool get isFailed => status == 'failed';
  bool get isPreparing => !isReady && !isFailed;

  factory MobileReportExport.fromResponse(Map<String, dynamic>? response) {
    final raw = response?['data'] ?? response ?? const <String, dynamic>{};
    final data = Map<String, dynamic>.from(raw as Map);
    final file = data['file'] is Map
        ? Map<String, dynamic>.from(data['file'] as Map)
        : const <String, dynamic>{};
    return MobileReportExport(
      uuid: data['uuid']?.toString() ?? '',
      reportType: data['report_type']?.toString() ?? 'student_comprehensive',
      status: data['status']?.toString() ?? 'preparing',
      filename: (data['filename'] ?? file['name'] ?? file['original_name'])
          ?.toString(),
      rowsCount: data['rows_count'] is num
          ? (data['rows_count'] as num).toInt()
          : int.tryParse(data['rows_count']?.toString() ?? ''),
      failureMessage: data['failure_message']?.toString(),
    );
  }
}
