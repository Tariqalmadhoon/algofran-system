import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../app/app_theme.dart';
import '../../../app/providers.dart';
import '../../../core/database/app_database.dart';
import '../../../core/database/profile_snapshots.dart';
import '../../../core/network/api_client.dart';
import '../../reports/data/report_export_repository.dart';

class TeacherProfileScreen extends ConsumerStatefulWidget {
  const TeacherProfileScreen({super.key});

  @override
  ConsumerState<TeacherProfileScreen> createState() =>
      _TeacherProfileScreenState();
}

class _TeacherProfileScreenState extends ConsumerState<TeacherProfileScreen> {
  String _reportType = 'student_comprehensive';
  int? _halaqaId;
  late String _dateFrom;
  late String _dateTo;
  bool _busy = false;
  MobileReportExport? _export;
  String? _downloadedPath;
  Timer? _pollTimer;
  int _pollAttempts = 0;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _dateFrom = DateFormat('yyyy-MM-dd').format(DateTime(now.year, now.month));
    _dateTo = DateFormat('yyyy-MM-dd').format(now);
    _loadLastDownloadedReport();
  }

  Future<void> _loadLastDownloadedReport() async {
    final path = await ref.read(databaseProvider).setting('last_report_path');
    if (path != null && await File(path).exists() && mounted) {
      setState(() => _downloadedPath = path);
    }
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  Future<void> _pickDate({required bool from}) async {
    final current =
        DateTime.tryParse(from ? _dateFrom : _dateTo) ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (picked == null) return;
    setState(() {
      if (from) {
        _dateFrom = DateFormat('yyyy-MM-dd').format(picked);
      } else {
        _dateTo = DateFormat('yyyy-MM-dd').format(picked);
      }
    });
  }

  Future<void> _requestExport() async {
    if (_busy) return;
    if (_reportType == 'memorization_records' &&
        DateTime.parse(_dateFrom).isAfter(DateTime.parse(_dateTo))) {
      _show('تاريخ البداية يجب أن يكون قبل تاريخ النهاية.', error: true);
      return;
    }
    setState(() {
      _busy = true;
      _export = null;
      _downloadedPath = null;
    });
    try {
      final result = await ref
          .read(reportExportRepositoryProvider)
          .request(
            reportType: _reportType,
            halaqaId: _halaqaId,
            dateFrom: _reportType == 'memorization_records' ? _dateFrom : null,
            dateTo: _reportType == 'memorization_records' ? _dateTo : null,
          );
      if (!mounted) return;
      setState(() {
        _export = result;
        _busy = false;
      });
      if (result.isPreparing) {
        _show('تم إرسال الطلب، ويجري إعداد ملف Excel.');
        _startPolling(result.uuid);
      } else if (result.isFailed) {
        _show(result.failureMessage ?? 'تعذر إعداد التقرير.', error: true);
      } else {
        _show('أصبح التقرير جاهزًا للتنزيل.');
      }
    } catch (error) {
      if (!mounted) return;
      setState(() => _busy = false);
      _showFailure(error);
    }
  }

  void _startPolling(String uuid) {
    _pollTimer?.cancel();
    _pollAttempts = 0;
    _pollTimer = Timer.periodic(const Duration(seconds: 3), (_) async {
      _pollAttempts++;
      if (_pollAttempts > 40) {
        _pollTimer?.cancel();
        _show('لا يزال التقرير قيد الإعداد. افتح «ملفي» لاحقًا للتحقق منه.');
        return;
      }
      try {
        final result = await ref
            .read(reportExportRepositoryProvider)
            .status(uuid);
        if (!mounted) return;
        setState(() => _export = result);
        if (!result.isPreparing) {
          _pollTimer?.cancel();
          _show(
            result.isReady
                ? 'اكتمل إعداد التقرير وأصبح جاهزًا.'
                : result.failureMessage ?? 'تعذر إعداد التقرير.',
            error: result.isFailed,
          );
        }
      } catch (_) {
        _pollTimer?.cancel();
        _show(
          'توقفت متابعة التقرير لعدم توفر الاتصال. الطلب محفوظ على الخادم، ويمكن طلبه مجددًا عند عودة الإنترنت.',
          error: true,
        );
      }
    });
  }

  Future<void> _downloadAndOpen() async {
    final export = _export;
    if (export == null || !export.isReady || _busy) return;
    setState(() => _busy = true);
    try {
      final repository = ref.read(reportExportRepositoryProvider);
      final path = await repository.download(export);
      if (!mounted) return;
      setState(() {
        _busy = false;
        _downloadedPath = path;
      });
      await ref.read(databaseProvider).setSetting('last_report_path', path);
      await repository.open(path);
    } catch (error) {
      if (!mounted) return;
      setState(() => _busy = false);
      _showFailure(error);
    }
  }

  Future<void> _openDownloaded() async {
    final path = _downloadedPath;
    if (path == null) return;
    try {
      await ref.read(reportExportRepositoryProvider).open(path);
    } catch (error) {
      _showFailure(error);
    }
  }

  void _showFailure(Object error) {
    if (error is ApiFailure && error.statusCode == 403) {
      _show(
        'صلاحية التصدير غير موجودة في جلسة هذا الجهاز. اتصل بالإنترنت ثم سجّل الخروج والدخول مرة واحدة لتحديث صلاحيات التطبيق.',
        error: true,
      );
      return;
    }
    final message = error is ApiFailure
        ? error.message
        : 'تعذر إكمال العملية. تحقق من الإنترنت وحاول مجددًا.';
    _show(
      '$message\nإنشاء التقرير وتنزيله أول مرة يحتاجان إلى الإنترنت، وبعد التنزيل يمكن فتح الملف دون اتصال.',
      error: true,
    );
  }

  void _show(String text, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(text),
          backgroundColor: error ? const Color(0xFF9A2D25) : AppTheme.emerald,
          behavior: SnackBarBehavior.floating,
        ),
      );
  }

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(teacherProfileProvider);
    final canExportReports = profile.valueOrNull?.canExportReports ?? false;
    final halaqas =
        ref.watch(halaqasProvider).valueOrNull ?? const <CachedHalaqa>[];
    final outbox =
        ref.watch(outboxProvider).valueOrNull ?? const <PendingDailyRecord>[];
    final unresolved = outbox.where((item) => item.status != 'synced').length;
    var studentsCount = 0;
    for (final halaqa in halaqas) {
      studentsCount +=
          ref.watch(studentsProvider(halaqa.id)).valueOrNull?.length ?? 0;
    }

    return RefreshIndicator(
      onRefresh: () async {
        try {
          await ref.read(syncRepositoryProvider).bootstrap();
        } catch (error) {
          _showFailure(error);
        }
      },
      child: ListView(
        padding: const EdgeInsets.fromLTRB(18, 8, 18, 28),
        children: [
          profile.when(
            loading: () => const Card(
              child: Padding(
                padding: EdgeInsets.all(36),
                child: Center(child: CircularProgressIndicator()),
              ),
            ),
            error: (_, _) => _TeacherHeader(profile: null),
            data: (data) => _TeacherHeader(profile: data),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _TeacherMetric(
                  icon: Icons.mosque_rounded,
                  value: '${halaqas.length}',
                  label: 'الحلقات',
                ),
              ),
              const SizedBox(width: 9),
              Expanded(
                child: _TeacherMetric(
                  icon: Icons.groups_rounded,
                  value: '$studentsCount',
                  label: 'الطلاب',
                ),
              ),
              const SizedBox(width: 9),
              Expanded(
                child: _TeacherMetric(
                  icon: unresolved == 0
                      ? Icons.cloud_done_rounded
                      : Icons.cloud_upload_rounded,
                  value: '$unresolved',
                  label: 'غير متزامن',
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _AssignedHalaqas(halaqas: halaqas),
          const SizedBox(height: 16),
          if (canExportReports)
            _ExportCard(
              reportType: _reportType,
              halaqaId: _halaqaId,
              halaqas: halaqas,
              dateFrom: _dateFrom,
              dateTo: _dateTo,
              busy: _busy,
              export: _export,
              hasDownloadedFile: _downloadedPath != null,
              onReportTypeChanged: (value) =>
                  setState(() => _reportType = value),
              onHalaqaChanged: (value) => setState(() => _halaqaId = value),
              onPickFrom: () => _pickDate(from: true),
              onPickTo: () => _pickDate(from: false),
              onRequest: _requestExport,
              onDownload: _downloadAndOpen,
              onOpenDownloaded: _openDownloaded,
            )
          else
            const Card(
              child: Padding(
                padding: EdgeInsets.all(18),
                child: Row(
                  children: [
                    Icon(Icons.lock_outline_rounded, color: Color(0xFF71817A)),
                    SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'صلاحية تصدير كشوفات المحفّظ غير مفعّلة لهذا الحساب.',
                      ),
                    ),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 12),
          const Card(
            color: Color(0xFFF3F7F5),
            child: Padding(
              padding: EdgeInsets.all(15),
              child: Row(
                children: [
                  Icon(Icons.offline_bolt_rounded, color: AppTheme.emerald),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'ملفك وملفات الطلاب المحفوظة متاحة دون إنترنت. إنشاء ملف Excel وتنزيله أول مرة يحتاجان إلى الاتصال.',
                      style: TextStyle(fontSize: 12, height: 1.55),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TeacherHeader extends StatelessWidget {
  const _TeacherHeader({required this.profile});

  final TeacherProfileSnapshot? profile;

  @override
  Widget build(BuildContext context) {
    final name = profile?.name ?? 'المحفّظ';
    final details = [
      profile?.employeeNumber == null
          ? null
          : 'الرقم الوظيفي: ${profile!.employeeNumber}',
      profile?.specialization,
      profile?.centerName,
    ].whereType<String>().toList();
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        gradient: const LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [Color(0xFF07563F), Color(0xFF0E8A61)],
        ),
        boxShadow: const [
          BoxShadow(
            color: Color(0x30086B4B),
            blurRadius: 24,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 35,
                backgroundColor: Colors.white.withValues(alpha: .16),
                child: Text(
                  name.trim().isEmpty ? 'م' : name.trim()[0],
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 27,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'ملف المحفّظ',
                      style: TextStyle(color: Color(0xFFBEE8D5), fontSize: 12),
                    ),
                    Text(
                      name,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    if (details.isNotEmpty)
                      Text(
                        details.join(' · '),
                        style: const TextStyle(
                          color: Color(0xFFDDF4E9),
                          height: 1.5,
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
          if (profile?.email != null || profile?.phone != null) ...[
            const Divider(color: Color(0x30FFFFFF), height: 26),
            Row(
              children: [
                if (profile?.email != null)
                  Expanded(
                    child: Text(
                      profile!.email!,
                      style: const TextStyle(color: Colors.white, fontSize: 12),
                    ),
                  ),
                if (profile?.phone != null)
                  Text(
                    profile!.phone!,
                    style: const TextStyle(color: Colors.white, fontSize: 12),
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _TeacherMetric extends StatelessWidget {
  const _TeacherMetric({
    required this.icon,
    required this.value,
    required this.label,
  });

  final IconData icon;
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) => Card(
    margin: EdgeInsets.zero,
    child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 14),
      child: Column(
        children: [
          Icon(icon, color: AppTheme.emerald),
          const SizedBox(height: 5),
          Text(
            value,
            style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800),
          ),
          Text(
            label,
            style: const TextStyle(fontSize: 10, color: Color(0xFF71817A)),
          ),
        ],
      ),
    ),
  );
}

class _AssignedHalaqas extends ConsumerWidget {
  const _AssignedHalaqas({required this.halaqas});

  final List<CachedHalaqa> halaqas;

  @override
  Widget build(BuildContext context, WidgetRef ref) => Card(
    child: Padding(
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.mosque_rounded, color: AppTheme.emerald),
              SizedBox(width: 8),
              Text(
                'حلقاتي المسندة',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
              ),
            ],
          ),
          const SizedBox(height: 10),
          if (halaqas.isEmpty)
            const Text(
              'لا توجد حلقات مسندة محفوظة على الجهاز.',
              style: TextStyle(color: Color(0xFF71817A)),
            )
          else
            ...halaqas.map((halaqa) {
              final count =
                  ref.watch(studentsProvider(halaqa.id)).valueOrNull?.length ??
                  0;
              return Container(
                margin: const EdgeInsets.only(top: 8),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF3F7F5),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    const CircleAvatar(
                      backgroundColor: Color(0xFFE0F1E8),
                      child: Icon(
                        Icons.auto_stories_rounded,
                        color: AppTheme.emerald,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            halaqa.name,
                            style: const TextStyle(fontWeight: FontWeight.w800),
                          ),
                          Text(
                            halaqa.centerName,
                            style: const TextStyle(
                              color: Color(0xFF71817A),
                              fontSize: 11,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Text(
                      '$count طالب',
                      style: const TextStyle(
                        color: AppTheme.emerald,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              );
            }),
        ],
      ),
    ),
  );
}

class _ExportCard extends StatelessWidget {
  const _ExportCard({
    required this.reportType,
    required this.halaqaId,
    required this.halaqas,
    required this.dateFrom,
    required this.dateTo,
    required this.busy,
    required this.export,
    required this.hasDownloadedFile,
    required this.onReportTypeChanged,
    required this.onHalaqaChanged,
    required this.onPickFrom,
    required this.onPickTo,
    required this.onRequest,
    required this.onDownload,
    required this.onOpenDownloaded,
  });

  final String reportType;
  final int? halaqaId;
  final List<CachedHalaqa> halaqas;
  final String dateFrom;
  final String dateTo;
  final bool busy;
  final MobileReportExport? export;
  final bool hasDownloadedFile;
  final ValueChanged<String> onReportTypeChanged;
  final ValueChanged<int?> onHalaqaChanged;
  final VoidCallback onPickFrom;
  final VoidCallback onPickTo;
  final VoidCallback onRequest;
  final VoidCallback onDownload;
  final VoidCallback onOpenDownloaded;

  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.file_download_outlined, color: AppTheme.emerald),
              SizedBox(width: 8),
              Text(
                'تصدير كشوفاتي',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
              ),
            ],
          ),
          const SizedBox(height: 14),
          DropdownButtonFormField<String>(
            initialValue: reportType,
            decoration: const InputDecoration(labelText: 'نوع الكشف'),
            items: const [
              DropdownMenuItem(
                value: 'student_comprehensive',
                child: Text('الكشف الشامل للطلاب'),
              ),
              DropdownMenuItem(
                value: 'memorization_records',
                child: Text('سجلات الحفظ والتسميع'),
              ),
            ],
            onChanged: busy
                ? null
                : (value) {
                    if (value != null) onReportTypeChanged(value);
                  },
          ),
          const SizedBox(height: 10),
          DropdownButtonFormField<int?>(
            initialValue: halaqaId,
            decoration: const InputDecoration(labelText: 'الحلقة'),
            items: [
              const DropdownMenuItem<int?>(
                value: null,
                child: Text('جميع حلقاتي'),
              ),
              ...halaqas.map(
                (halaqa) => DropdownMenuItem<int?>(
                  value: halaqa.id,
                  child: Text(halaqa.name),
                ),
              ),
            ],
            onChanged: busy ? null : onHalaqaChanged,
          ),
          if (reportType == 'memorization_records') ...[
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: _DateButton(
                    label: 'من تاريخ',
                    value: dateFrom,
                    onTap: onPickFrom,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _DateButton(
                    label: 'إلى تاريخ',
                    value: dateTo,
                    onTap: onPickTo,
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: 13),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: busy ? null : onRequest,
              icon: busy
                  ? const SizedBox.square(
                      dimension: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.add_to_drive_outlined),
              label: const Text('إنشاء ملف Excel'),
            ),
          ),
          if (export != null) ...[
            const SizedBox(height: 12),
            _ExportStatus(export: export!),
            if (export!.isReady) ...[
              const SizedBox(height: 9),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: busy ? null : onDownload,
                  icon: const Icon(Icons.download_done_rounded),
                  label: const Text('تنزيل وفتح الملف'),
                ),
              ),
            ],
          ],
          if (hasDownloadedFile) ...[
            const SizedBox(height: 8),
            TextButton.icon(
              onPressed: onOpenDownloaded,
              icon: const Icon(Icons.offline_bolt_rounded),
              label: const Text('فتح النسخة المنزلة دون إنترنت'),
            ),
          ],
        ],
      ),
    ),
  );
}

class _DateButton extends StatelessWidget {
  const _DateButton({
    required this.label,
    required this.value,
    required this.onTap,
  });

  final String label;
  final String value;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(16),
    child: InputDecorator(
      decoration: InputDecoration(labelText: label),
      child: Row(
        children: [
          const Icon(Icons.calendar_month_rounded, size: 18),
          const SizedBox(width: 7),
          Text(value),
        ],
      ),
    ),
  );
}

class _ExportStatus extends StatelessWidget {
  const _ExportStatus({required this.export});

  final MobileReportExport export;

  @override
  Widget build(BuildContext context) {
    final status = export.isReady
        ? ('جاهز للتنزيل', Icons.verified_rounded, const Color(0xFF167A57))
        : export.isFailed
        ? ('فشل الإعداد', Icons.error_outline_rounded, const Color(0xFF9A2D25))
        : (
            'يجري إعداد الملف',
            Icons.hourglass_top_rounded,
            const Color(0xFF9B6500),
          );
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: status.$3.withValues(alpha: .08),
        borderRadius: BorderRadius.circular(15),
      ),
      child: Row(
        children: [
          Icon(status.$2, color: status.$3),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              status.$1,
              style: TextStyle(color: status.$3, fontWeight: FontWeight.w800),
            ),
          ),
          if (export.rowsCount != null) Text('${export.rowsCount} صف'),
        ],
      ),
    );
  }
}
