import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../app/providers.dart';
import '../../../core/database/app_database.dart';
import '../../../core/network/api_client.dart';
import '../data/mobile_student.dart';
import '../data/student_management_repository.dart';

class StudentFormScreen extends ConsumerStatefulWidget {
  const StudentFormScreen({
    super.key,
    required this.halaqas,
    required this.initialHalaqaId,
    this.student,
  });

  final List<CachedHalaqa> halaqas;
  final int initialHalaqaId;
  final MobileStudent? student;

  bool get editing => student != null;

  @override
  ConsumerState<StudentFormScreen> createState() => _StudentFormScreenState();
}

class _StudentFormScreenState extends ConsumerState<StudentFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _firstName;
  late final TextEditingController _fatherName;
  late final TextEditingController _grandfatherName;
  late final TextEditingController _familyName;
  late final TextEditingController _phone;
  late final TextEditingController _notes;
  late int _halaqaId;
  String? _birthDate;
  late String _registrationDate;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final profile = widget.student?.profile;
    _firstName = TextEditingController(text: profile?.firstName);
    _fatherName = TextEditingController(text: profile?.fatherName);
    _grandfatherName = TextEditingController(text: profile?.grandfatherName);
    _familyName = TextEditingController(text: profile?.familyName);
    _phone = TextEditingController(text: profile?.contactPhone);
    _notes = TextEditingController(text: profile?.notes);
    _halaqaId = widget.student?.halaqaId ?? widget.initialHalaqaId;
    _birthDate = profile?.birthDate;
    _registrationDate =
        profile?.registrationDate ?? _formatDate(DateTime.now());
  }

  @override
  void dispose() {
    _firstName.dispose();
    _fatherName.dispose();
    _grandfatherName.dispose();
    _familyName.dispose();
    _phone.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _selectDate({required bool birth}) async {
    final current =
        DateTime.tryParse(
          birth ? _birthDate ?? '2015-01-01' : _registrationDate,
        ) ??
        DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: birth ? DateTime(1990) : DateTime(2020),
      lastDate: birth
          ? DateTime.now().subtract(const Duration(days: 1))
          : DateTime.now(),
    );
    if (picked == null) return;
    setState(() {
      if (birth) {
        _birthDate = _formatDate(picked);
      } else {
        _registrationDate = _formatDate(picked);
      }
    });
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate() || _saving) return;
    setState(() => _saving = true);
    final data = StudentFormData(
      firstName: _firstName.text,
      fatherName: _fatherName.text,
      grandfatherName: _grandfatherName.text,
      familyName: _familyName.text,
      birthDate: _birthDate,
      contactPhone: _phone.text,
      registrationDate: _registrationDate,
      notes: _notes.text,
    );
    try {
      final repository = ref.read(studentManagementRepositoryProvider);
      final student = widget.student;
      if (student == null) {
        await repository.create(halaqaId: _halaqaId, data: data);
      } else if (student.isLocalDraft) {
        await repository.updateDraft(
          clientUuid: student.clientUuid!,
          halaqaId: _halaqaId,
          data: data,
        );
      } else {
        final baseUpdatedAt = student.profile?.updatedAt;
        if (baseUpdatedAt == null) {
          throw const ApiFailure(
            'بيانات الطالب تحتاج إلى مزامنة حديثة قبل تعديلها.',
          );
        }
        await repository.updateServer(
          studentId: student.serverId!,
          halaqaId: student.halaqaId,
          baseUpdatedAt: baseUpdatedAt,
          data: data,
        );
      }
      await ref.read(appControllerProvider.notifier).syncNow(silent: true);
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      setState(() => _saving = false);
      final message = error is ApiFailure
          ? error.message
          : 'تعذر حفظ العملية محليًا. حاول مجددًا.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message), behavior: SnackBarBehavior.floating),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final editingServer = widget.student?.serverId != null;
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.editing ? 'تعديل ملف الطالب' : 'إضافة طالب جديد'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(18, 8, 18, 28),
          children: [
            const _PrivacyNotice(),
            const SizedBox(height: 14),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(18),
                child: Column(
                  children: [
                    _NameField(controller: _firstName, label: 'اسم الطالب'),
                    const SizedBox(height: 10),
                    _NameField(controller: _fatherName, label: 'اسم الأب'),
                    const SizedBox(height: 10),
                    _NameField(controller: _grandfatherName, label: 'اسم الجد'),
                    const SizedBox(height: 10),
                    _NameField(controller: _familyName, label: 'اسم العائلة'),
                    const SizedBox(height: 10),
                    DropdownButtonFormField<int>(
                      initialValue: _halaqaId,
                      decoration: const InputDecoration(labelText: 'الحلقة'),
                      items: widget.halaqas
                          .map(
                            (halaqa) => DropdownMenuItem(
                              value: halaqa.id,
                              child: Text(halaqa.name),
                            ),
                          )
                          .toList(),
                      onChanged: editingServer
                          ? null
                          : (value) {
                              if (value != null) {
                                setState(() => _halaqaId = value);
                              }
                            },
                    ),
                    if (editingServer)
                      const Padding(
                        padding: EdgeInsets.only(top: 6),
                        child: Align(
                          alignment: Alignment.centerRight,
                          child: Text(
                            'نقل الطالب بين الحلقات يتم من لوحة الإدارة.',
                            style: TextStyle(
                              fontSize: 11,
                              color: Color(0xFF71817A),
                            ),
                          ),
                        ),
                      ),
                    const SizedBox(height: 10),
                    TextFormField(
                      controller: _phone,
                      keyboardType: TextInputType.phone,
                      decoration: const InputDecoration(
                        labelText: 'رقم التواصل (اختياري)',
                        prefixIcon: Icon(Icons.phone_outlined),
                      ),
                      validator: (value) => value != null && value.length > 30
                          ? 'رقم التواصل طويل جدًا.'
                          : null,
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: _DateField(
                            label: 'تاريخ الميلاد',
                            value: _birthDate ?? 'غير محدد',
                            onTap: () => _selectDate(birth: true),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: _DateField(
                            label: 'تاريخ التسجيل',
                            value: _registrationDate,
                            enabled: !widget.editing,
                            onTap: () => _selectDate(birth: false),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    TextFormField(
                      controller: _notes,
                      maxLines: 4,
                      maxLength: 3000,
                      decoration: const InputDecoration(labelText: 'ملاحظات'),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(18, 8, 18, 14),
        child: FilledButton.icon(
          onPressed: _saving ? null : _save,
          icon: _saving
              ? const SizedBox.square(
                  dimension: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.save_rounded),
          label: Text(
            widget.editing
                ? 'حفظ التعديل على الجهاز'
                : 'إضافة الطالب على الجهاز',
          ),
        ),
      ),
    );
  }

  static String _formatDate(DateTime value) =>
      DateFormat('yyyy-MM-dd').format(value);
}

class _NameField extends StatelessWidget {
  const _NameField({required this.controller, required this.label});

  final TextEditingController controller;
  final String label;

  @override
  Widget build(BuildContext context) => TextFormField(
    controller: controller,
    textInputAction: TextInputAction.next,
    decoration: InputDecoration(labelText: label),
    validator: (value) {
      final normalized = value?.trim() ?? '';
      if (normalized.isEmpty) return '$label مطلوب.';
      if (normalized.length > 100) return '$label طويل جدًا.';
      return null;
    },
  );
}

class _DateField extends StatelessWidget {
  const _DateField({
    required this.label,
    required this.value,
    required this.onTap,
    this.enabled = true,
  });

  final String label;
  final String value;
  final VoidCallback onTap;
  final bool enabled;

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: enabled ? onTap : null,
    borderRadius: BorderRadius.circular(16),
    child: InputDecorator(
      decoration: InputDecoration(labelText: label, enabled: enabled),
      child: Text(value),
    ),
  );
}

class _PrivacyNotice extends StatelessWidget {
  const _PrivacyNotice();

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: const Color(0xFFEAF4FF),
      borderRadius: BorderRadius.circular(18),
    ),
    child: const Row(
      children: [
        Icon(Icons.shield_outlined, color: Color(0xFF2766A5)),
        SizedBox(width: 10),
        Expanded(
          child: Text(
            'لا تُحفظ أرقام الهوية أو الوثائق الحساسة في التطبيق؛ استكملها من لوحة الويب الآمنة.',
            style: TextStyle(fontSize: 12, height: 1.5),
          ),
        ),
      ],
    ),
  );
}
