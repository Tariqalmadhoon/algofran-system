import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../app/app_theme.dart';
import '../../../app/providers.dart';
import '../../../core/database/app_database.dart';
import '../../students/data/mobile_student.dart';

class RecordDailyScreen extends ConsumerStatefulWidget {
  const RecordDailyScreen({
    super.key,
    required this.halaqa,
    required this.student,
  });

  final CachedHalaqa halaqa;
  final MobileStudent student;

  @override
  ConsumerState<RecordDailyScreen> createState() => _RecordDailyScreenState();
}

class _RecordDailyScreenState extends ConsumerState<RecordDailyScreen> {
  final _formKey = GlobalKey<FormState>();
  final _attendanceNotes = TextEditingController();
  final _sessionNotes = TextEditingController();
  late final Future<_QuranCache> _quran;
  DateTime _recordDate = DateTime.now();
  String _attendanceStatus = 'present';
  String _generalEvaluation = 'very_good';
  final List<_RecitationDraft> _items = [_RecitationDraft()];
  bool _saving = false;

  bool get _allowsRecitation =>
      _attendanceStatus == 'present' || _attendanceStatus == 'late';

  static const attendanceOptions = {
    'present': 'حاضر',
    'absent': 'غائب',
    'excused': 'بعذر',
    'late': 'متأخر',
  };
  static const evaluationOptions = {
    'poor': 'ضعيف',
    'good': 'جيد',
    'very_good': 'جيد جدًا',
    'excellent': 'ممتاز',
  };
  static const recitationOptions = {
    'new_memorization': 'حفظ جديد',
    'recent_revision': 'مراجعة قريبة',
    'old_revision': 'مراجعة قديمة',
    'recitation': 'سرد وتلاوة',
    'exam': 'اختبار',
    'tajweed': 'تجويد',
  };

  @override
  void initState() {
    super.initState();
    _quran = _loadQuran();
  }

  @override
  void dispose() {
    _attendanceNotes.dispose();
    _sessionNotes.dispose();
    super.dispose();
  }

  Future<_QuranCache> _loadQuran() async {
    final database = ref.read(databaseProvider);
    final surahs = await database.allSurahs();
    final ayahs = await database.allAyahs();
    return _QuranCache(surahs, ayahs);
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _recordDate,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now(),
    );
    if (picked != null) setState(() => _recordDate = picked);
  }

  Future<void> _save(_QuranCache quran) async {
    if (!_formKey.currentState!.validate()) return;
    final activeItems = _allowsRecitation ? _items : const <_RecitationDraft>[];
    for (var index = 0; index < activeItems.length; index++) {
      final item = activeItems[index];
      if (!item.isComplete) {
        _showError('أكمل السورة والآيات في بند التسميع رقم ${index + 1}.');
        return;
      }
      if (quran.globalOrder(item.endAyahId!) <
          quran.globalOrder(item.startAyahId!)) {
        _showError('نهاية النطاق في البند رقم ${index + 1} تسبق بدايته.');
        return;
      }
    }

    setState(() => _saving = true);
    await ref
        .read(syncRepositoryProvider)
        .queueDailyRecord(
          studentId: widget.student.serverId,
          studentClientUuid: widget.student.clientUuid,
          halaqaId: widget.halaqa.id,
          recordDate: _recordDate,
          attendanceStatus: _attendanceStatus,
          attendanceNotes: _attendanceNotes.text,
          generalEvaluation: _allowsRecitation ? _generalEvaluation : null,
          notes: _sessionNotes.text,
          items: activeItems.map((item) => item.toPayload()).toList(),
        );
    if (!mounted) return;
    setState(() => _saving = false);
    ref.read(appControllerProvider.notifier).syncNow(silent: true);
    Navigator.of(context).pop();
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text(
          'تم حفظ السجل على الجهاز وسيُعتمد تلقائيًا عند توفر الإنترنت.',
        ),
        backgroundColor: AppTheme.emerald,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: const Color(0xFF9A2D25),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('التسجيل اليومي')),
      body: FutureBuilder<_QuranCache>(
        future: _quran,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final quran = snapshot.data!;
          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(18, 4, 18, 110),
              children: [
                _StudentHeader(student: widget.student, halaqa: widget.halaqa),
                const SizedBox(height: 14),
                _SectionCard(
                  title: 'الحضور والجلسة',
                  icon: Icons.fact_check_outlined,
                  child: Column(
                    children: [
                      InkWell(
                        onTap: _pickDate,
                        borderRadius: BorderRadius.circular(16),
                        child: InputDecorator(
                          decoration: const InputDecoration(
                            labelText: 'تاريخ التسجيل',
                            prefixIcon: Icon(Icons.calendar_today_rounded),
                          ),
                          child: Text(
                            DateFormat('yyyy/MM/dd').format(_recordDate),
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<String>(
                        initialValue: _attendanceStatus,
                        decoration: const InputDecoration(
                          labelText: 'حالة الحضور',
                          prefixIcon: Icon(Icons.how_to_reg_rounded),
                        ),
                        items: attendanceOptions.entries
                            .map(
                              (entry) => DropdownMenuItem(
                                value: entry.key,
                                child: Text(entry.value),
                              ),
                            )
                            .toList(),
                        onChanged: (value) =>
                            setState(() => _attendanceStatus = value!),
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _attendanceNotes,
                        maxLines: 2,
                        decoration: const InputDecoration(
                          labelText: 'ملاحظة الحضور — اختياري',
                        ),
                      ),
                      const SizedBox(height: 12),
                      AnimatedSwitcher(
                        duration: const Duration(milliseconds: 220),
                        child: _allowsRecitation
                            ? DropdownButtonFormField<String>(
                                key: const ValueKey('general-evaluation'),
                                initialValue: _generalEvaluation,
                                decoration: const InputDecoration(
                                  labelText: 'التقييم العام',
                                  prefixIcon: Icon(Icons.stars_rounded),
                                ),
                                items: evaluationOptions.entries
                                    .map(
                                      (entry) => DropdownMenuItem(
                                        value: entry.key,
                                        child: Text(entry.value),
                                      ),
                                    )
                                    .toList(),
                                onChanged: (value) =>
                                    setState(() => _generalEvaluation = value!),
                              )
                            : const _NoEvaluationNotice(
                                key: ValueKey('no-evaluation'),
                              ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                AnimatedSwitcher(
                  duration: const Duration(milliseconds: 280),
                  child: !_allowsRecitation
                      ? _AbsenceNotice(
                          key: ValueKey(_attendanceStatus),
                          excused: _attendanceStatus == 'excused',
                        )
                      : Column(
                          key: const ValueKey('recitations'),
                          children: [
                            for (var index = 0; index < _items.length; index++)
                              Padding(
                                padding: const EdgeInsets.only(bottom: 12),
                                child: _RecitationCard(
                                  index: index,
                                  draft: _items[index],
                                  quran: quran,
                                  canRemove: _items.length > 1,
                                  onChanged: () => setState(() {}),
                                  onRemove: () =>
                                      setState(() => _items.removeAt(index)),
                                ),
                              ),
                            OutlinedButton.icon(
                              onPressed: _items.length >= 6
                                  ? null
                                  : () => setState(
                                      () => _items.add(_RecitationDraft()),
                                    ),
                              icon: const Icon(
                                Icons.add_circle_outline_rounded,
                              ),
                              label: const Text('إضافة بند تسميع آخر'),
                            ),
                          ],
                        ),
                ),
                const SizedBox(height: 14),
                _SectionCard(
                  title: 'ملاحظات المحفّظ',
                  icon: Icons.notes_rounded,
                  child: TextFormField(
                    controller: _sessionNotes,
                    maxLines: 3,
                    decoration: const InputDecoration(
                      hintText: 'ملاحظات عامة حول أداء الطالب اليوم...',
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(18, 10, 18, 14),
        child: FutureBuilder<_QuranCache>(
          future: _quran,
          builder: (context, snapshot) => FilledButton.icon(
            onPressed: _saving || !snapshot.hasData
                ? null
                : () => _save(snapshot.data!),
            icon: _saving
                ? const SizedBox.square(
                    dimension: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.offline_pin_rounded),
            label: Text(_saving ? 'جارٍ الحفظ...' : 'حفظ السجل بأمان'),
          ),
        ),
      ),
    );
  }
}

class _StudentHeader extends StatelessWidget {
  const _StudentHeader({required this.student, required this.halaqa});

  final MobileStudent student;
  final CachedHalaqa halaqa;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(17),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        gradient: const LinearGradient(
          colors: [Color(0xFF086B4B), Color(0xFF14916A)],
        ),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 27,
            backgroundColor: Colors.white,
            child: Text(
              student.fullName[0],
              style: const TextStyle(
                color: AppTheme.emerald,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  student.fullName,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 17,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                Text(
                  '${halaqa.name} • ${student.studentNumber}',
                  style: const TextStyle(
                    color: Color(0xFFD8F2E6),
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.title,
    required this.icon,
    required this.child,
  });

  final String title;
  final IconData icon;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: AppTheme.emerald),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 15),
            child,
          ],
        ),
      ),
    );
  }
}

class _NoEvaluationNotice extends StatelessWidget {
  const _NoEvaluationNotice({super.key});

  @override
  Widget build(BuildContext context) {
    return const ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(Icons.info_outline_rounded, color: Color(0xFF1672A3)),
      title: Text(
        'لا يوجد تقييم أكاديمي للطالب الغائب.',
        style: TextStyle(fontWeight: FontWeight.w700),
      ),
    );
  }
}

class _AbsenceNotice extends StatelessWidget {
  const _AbsenceNotice({super.key, required this.excused});

  final bool excused;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: excused ? const Color(0xFFEAF7FF) : const Color(0xFFFFF2E0),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        children: [
          Icon(
            Icons.person_off_outlined,
            color: excused ? const Color(0xFF1672A3) : const Color(0xFF9B6500),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              excused
                  ? 'الطالب غائب بعذر؛ سيُحفظ الحضور دون تسميع أو تقييم.'
                  : 'الطالب غائب؛ سيُحفظ الحضور دون تسميع أو تقييم.',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );
  }
}

class _RecitationCard extends StatelessWidget {
  const _RecitationCard({
    required this.index,
    required this.draft,
    required this.quran,
    required this.canRemove,
    required this.onChanged,
    required this.onRemove,
  });

  final int index;
  final _RecitationDraft draft;
  final _QuranCache quran;
  final bool canRemove;
  final VoidCallback onChanged;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    final startAyahs = quran.ayahsFor(draft.startSurahId);
    final endAyahs = quran.ayahsFor(draft.endSurahId);
    return _SectionCard(
      title: 'بند تسميع ${index + 1}',
      icon: Icons.menu_book_rounded,
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: draft.type,
                  decoration: const InputDecoration(labelText: 'نوع البند'),
                  items: _RecordDailyScreenState.recitationOptions.entries
                      .map(
                        (entry) => DropdownMenuItem(
                          value: entry.key,
                          child: Text(entry.value),
                        ),
                      )
                      .toList(),
                  onChanged: (value) {
                    draft.type = value!;
                    onChanged();
                  },
                ),
              ),
              if (canRemove) ...[
                const SizedBox(width: 6),
                IconButton(
                  onPressed: onRemove,
                  icon: const Icon(
                    Icons.delete_outline_rounded,
                    color: Color(0xFF9A2D25),
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<int>(
            key: ValueKey('start-surah-${draft.startSurahId}'),
            initialValue: draft.startSurahId,
            isExpanded: true,
            decoration: const InputDecoration(
              labelText: 'سورة البداية',
              prefixIcon: Icon(Icons.first_page_rounded),
            ),
            items: quran.surahs
                .map(
                  (surah) => DropdownMenuItem(
                    value: surah.id,
                    child: Text('${surah.id}. ${surah.nameArabic}'),
                  ),
                )
                .toList(),
            onChanged: (value) {
              draft.startSurahId = value;
              draft.startAyahId = null;
              draft.endSurahId = value;
              draft.endAyahId = null;
              onChanged();
            },
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<int>(
            key: ValueKey(
              'start-ayah-${draft.startSurahId}-${draft.startAyahId}',
            ),
            initialValue: draft.startAyahId,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'آية البداية'),
            items: startAyahs
                .map(
                  (ayah) => DropdownMenuItem(
                    value: ayah.id,
                    child: Text('الآية ${ayah.ayahNumber}'),
                  ),
                )
                .toList(),
            onChanged: draft.startSurahId == null
                ? null
                : (value) {
                    draft.startAyahId = value;
                    if (draft.endSurahId == draft.startSurahId) {
                      draft.endAyahId ??= value;
                    }
                    onChanged();
                  },
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 8),
            child: Icon(Icons.arrow_downward_rounded, color: AppTheme.gold),
          ),
          DropdownButtonFormField<int>(
            key: ValueKey('end-surah-${draft.endSurahId}'),
            initialValue: draft.endSurahId,
            isExpanded: true,
            decoration: const InputDecoration(
              labelText: 'سورة النهاية',
              prefixIcon: Icon(Icons.last_page_rounded),
            ),
            items: quran.surahs
                .where(
                  (surah) =>
                      draft.startSurahId == null ||
                      surah.id >= draft.startSurahId!,
                )
                .map(
                  (surah) => DropdownMenuItem(
                    value: surah.id,
                    child: Text('${surah.id}. ${surah.nameArabic}'),
                  ),
                )
                .toList(),
            onChanged: draft.startSurahId == null
                ? null
                : (value) {
                    draft.endSurahId = value;
                    draft.endAyahId = null;
                    onChanged();
                  },
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<int>(
            key: ValueKey('end-ayah-${draft.endSurahId}-${draft.endAyahId}'),
            initialValue: draft.endAyahId,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'آية النهاية'),
            items: endAyahs
                .where(
                  (ayah) =>
                      draft.startSurahId != draft.endSurahId ||
                      draft.startAyahId == null ||
                      quran.globalOrder(ayah.id) >=
                          quran.globalOrder(draft.startAyahId!),
                )
                .map(
                  (ayah) => DropdownMenuItem(
                    value: ayah.id,
                    child: Text('الآية ${ayah.ayahNumber}'),
                  ),
                )
                .toList(),
            onChanged: draft.endSurahId == null
                ? null
                : (value) {
                    draft.endAyahId = value;
                    onChanged();
                  },
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            initialValue: draft.evaluation,
            decoration: const InputDecoration(
              labelText: 'تقييم البند',
              prefixIcon: Icon(Icons.grade_outlined),
            ),
            items: _RecordDailyScreenState.evaluationOptions.entries
                .map(
                  (entry) => DropdownMenuItem(
                    value: entry.key,
                    child: Text(entry.value),
                  ),
                )
                .toList(),
            onChanged: (value) {
              draft.evaluation = value!;
              onChanged();
            },
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _Counter(
                label: 'أخطاء الحفظ',
                value: draft.memorizationErrors,
                onChanged: (value) => draft.memorizationErrors = value,
              ),
              _Counter(
                label: 'أخطاء التجويد',
                value: draft.tajweedErrors,
                onChanged: (value) => draft.tajweedErrors = value,
              ),
              _Counter(
                label: 'التردد',
                value: draft.hesitationCount,
                onChanged: (value) => draft.hesitationCount = value,
              ),
              _Counter(
                label: 'تلقين المعلم',
                value: draft.teacherPromptCount,
                onChanged: (value) => draft.teacherPromptCount = value,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Counter extends StatefulWidget {
  const _Counter({
    required this.label,
    required this.value,
    required this.onChanged,
  });

  final String label;
  final int value;
  final ValueChanged<int> onChanged;

  @override
  State<_Counter> createState() => _CounterState();
}

class _CounterState extends State<_Counter> {
  late int value = widget.value;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 150,
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 7),
      decoration: BoxDecoration(
        color: const Color(0xFFF4F8F5),
        borderRadius: BorderRadius.circular(15),
      ),
      child: Column(
        children: [
          Text(
            widget.label,
            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              IconButton(
                visualDensity: VisualDensity.compact,
                onPressed: value == 0
                    ? null
                    : () {
                        setState(() => value--);
                        widget.onChanged(value);
                      },
                icon: const Icon(Icons.remove_circle_outline_rounded),
              ),
              Text(
                '$value',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
              IconButton(
                visualDensity: VisualDensity.compact,
                onPressed: value >= 999
                    ? null
                    : () {
                        setState(() => value++);
                        widget.onChanged(value);
                      },
                icon: const Icon(Icons.add_circle_outline_rounded),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _RecitationDraft {
  String type = 'new_memorization';
  int? startSurahId;
  int? startAyahId;
  int? endSurahId;
  int? endAyahId;
  String evaluation = 'very_good';
  int memorizationErrors = 0;
  int tajweedErrors = 0;
  int hesitationCount = 0;
  int teacherPromptCount = 0;

  bool get isComplete =>
      startSurahId != null &&
      startAyahId != null &&
      endSurahId != null &&
      endAyahId != null;

  Map<String, dynamic> toPayload() => {
    'type': type,
    'start_ayah_id': startAyahId,
    'end_ayah_id': endAyahId,
    'evaluation': evaluation,
    'memorization_errors': memorizationErrors,
    'tajweed_errors': tajweedErrors,
    'hesitation_count': hesitationCount,
    'teacher_prompt_count': teacherPromptCount,
    'notes': null,
  };
}

class _QuranCache {
  _QuranCache(this.surahs, this.ayahs)
    : _ayahById = {for (final ayah in ayahs) ayah.id: ayah};

  final List<CachedSurah> surahs;
  final List<CachedAyah> ayahs;
  final Map<int, CachedAyah> _ayahById;

  List<CachedAyah> ayahsFor(int? surahId) {
    if (surahId == null) return const [];
    return ayahs.where((ayah) => ayah.surahId == surahId).toList();
  }

  int globalOrder(int ayahId) => _ayahById[ayahId]?.globalOrder ?? 0;
}
