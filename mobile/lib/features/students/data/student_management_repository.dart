import 'package:uuid/uuid.dart';

import '../../../core/database/app_database.dart';
import '../../../core/database/profile_snapshots.dart';

class StudentManagementRepository {
  StudentManagementRepository(this.database);

  final AppDatabase database;

  Future<String> create({
    required int halaqaId,
    required StudentFormData data,
  }) async {
    final clientUuid = const Uuid().v4();
    await database.queueStudentCreate(
      operationUuid: const Uuid().v4(),
      clientUuid: clientUuid,
      halaqaId: halaqaId,
      student: {
        'client_uuid': clientUuid,
        'halaqa_id': halaqaId,
        ...data.toPayload(includeRegistrationDate: true),
      },
    );
    return clientUuid;
  }

  Future<void> updateServer({
    required int studentId,
    required int halaqaId,
    required String baseUpdatedAt,
    required StudentFormData data,
  }) {
    return database.queueStudentUpdate(
      operationUuid: const Uuid().v4(),
      studentId: studentId,
      halaqaId: halaqaId,
      student: {
        'id': studentId,
        'base_updated_at': baseUpdatedAt,
        ...data.toPayload(),
      },
    );
  }

  Future<void> updateDraft({
    required String clientUuid,
    required int halaqaId,
    required StudentFormData data,
  }) {
    return database.updateLocalStudentDraft(
      clientUuid: clientUuid,
      halaqaId: halaqaId,
      student: {
        'client_uuid': clientUuid,
        'halaqa_id': halaqaId,
        ...data.toPayload(includeRegistrationDate: true),
      },
    );
  }

  Future<void> archiveServer({
    required int studentId,
    required int halaqaId,
    required String baseUpdatedAt,
  }) {
    return database.queueStudentArchive(
      operationUuid: const Uuid().v4(),
      studentId: studentId,
      halaqaId: halaqaId,
      student: {'id': studentId, 'base_updated_at': baseUpdatedAt},
    );
  }

  Future<void> discardDraft(String clientUuid) =>
      database.discardLocalStudentDraft(clientUuid);
}

class StudentFormData {
  const StudentFormData({
    required this.firstName,
    required this.fatherName,
    required this.grandfatherName,
    required this.familyName,
    this.birthDate,
    this.contactPhone,
    required this.registrationDate,
    this.notes,
  });

  final String firstName;
  final String fatherName;
  final String grandfatherName;
  final String familyName;
  final String? birthDate;
  final String? contactPhone;
  final String registrationDate;
  final String? notes;

  factory StudentFormData.fromProfile(StudentProfileSnapshot profile) =>
      StudentFormData(
        firstName: profile.firstName ?? '',
        fatherName: profile.fatherName ?? '',
        grandfatherName: profile.grandfatherName ?? '',
        familyName: profile.familyName ?? '',
        birthDate: profile.birthDate,
        contactPhone: profile.contactPhone,
        registrationDate: profile.registrationDate ?? _today(),
        notes: profile.notes,
      );

  Map<String, dynamic> toPayload({bool includeRegistrationDate = false}) {
    final payload = <String, dynamic>{
      'first_name': firstName.trim(),
      'father_name': fatherName.trim(),
      'grandfather_name': grandfatherName.trim(),
      'family_name': familyName.trim(),
      'birth_date': _emptyToNull(birthDate),
      'contact_phone': _emptyToNull(contactPhone),
      'notes': _emptyToNull(notes),
      if (includeRegistrationDate) 'registration_date': registrationDate,
    };
    return payload;
  }

  static String _today() {
    final now = DateTime.now();
    return '${now.year.toString().padLeft(4, '0')}-'
        '${now.month.toString().padLeft(2, '0')}-'
        '${now.day.toString().padLeft(2, '0')}';
  }

  static String? _emptyToNull(String? value) {
    final normalized = value?.trim();
    return normalized == null || normalized.isEmpty ? null : normalized;
  }
}
