import 'dart:convert';

import '../../../core/database/app_database.dart';
import '../../../core/database/profile_snapshots.dart';

class MobileStudent {
  const MobileStudent({
    this.serverId,
    this.clientUuid,
    required this.halaqaId,
    required this.studentNumber,
    required this.fullName,
    this.recordedToday = false,
    this.dailyRecordId,
    this.profile,
  });

  final int? serverId;
  final String? clientUuid;
  final int halaqaId;
  final String studentNumber;
  final String fullName;
  final bool recordedToday;
  final int? dailyRecordId;
  final StudentProfileSnapshot? profile;

  bool get isLocalDraft => serverId == null;

  MobileStudent withProfile(StudentProfileSnapshot? value) => MobileStudent(
    serverId: serverId,
    clientUuid: clientUuid,
    halaqaId: halaqaId,
    studentNumber: studentNumber,
    fullName: fullName,
    recordedToday: recordedToday,
    dailyRecordId: dailyRecordId,
    profile: value,
  );

  factory MobileStudent.fromCached(
    CachedStudent student, {
    StudentProfileSnapshot? profile,
  }) => MobileStudent(
    serverId: student.id,
    halaqaId: student.halaqaId,
    studentNumber: student.studentNumber,
    fullName: student.fullName,
    recordedToday: student.recordedToday,
    dailyRecordId: student.dailyRecordId,
    profile: profile,
  );

  factory MobileStudent.fromDraft(LocalStudentDraft draft) {
    final payload = Map<String, dynamic>.from(
      jsonDecode(draft.payloadJson) as Map,
    );
    final parts =
        [
              payload['first_name'],
              payload['father_name'],
              payload['grandfather_name'],
              payload['family_name'],
            ]
            .map((value) => value?.toString().trim())
            .whereType<String>()
            .where((value) => value.isNotEmpty);
    final fullName = parts.join(' ');
    final envelope = <String, dynamic>{
      'id': 0,
      'student_number': 'قيد الاعتماد',
      'full_name': fullName,
      'client_uuid': draft.clientUuid,
      'profile': <String, dynamic>{
        ...payload,
        'status': 'active',
        'status_label': 'محفوظ محليًا',
      },
    };
    return MobileStudent(
      clientUuid: draft.clientUuid,
      halaqaId: draft.halaqaId,
      studentNumber: 'قيد الاعتماد',
      fullName: fullName.isEmpty ? 'طالب جديد' : fullName,
      profile: StudentProfileSnapshot.fromJson(envelope),
    );
  }
}
